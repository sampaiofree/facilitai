<?php

namespace App\Jobs;

use App\Jobs\ProcessIncomingMessageJob;
use App\Models\ClienteLead;
use App\Models\Conexao;
use App\Services\MediaDecryptService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class UazapiJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private const MAX_INLINE_BYTES_DEFAULT = 300000;
    private const MAX_INLINE_BYTES_AUDIO = 500000;
    private const DEDUP_TTL_MINUTES = 10;

    public int $tries = 3;
    public int $timeout = 60;
    public int $backoff = 30;

    protected array $payload;

    public function __construct(array $payload)
    {
        $this->payload = $payload;
    }

    public function handle(): void
    {
        $evento = (string) ($this->payload['evento'] ?? '');
        if ($evento !== 'messages') {
            return;
        }

        $tipoMensagem = (string) ($this->payload['tipo'] ?? '');
        $payload = is_array($this->payload['payload'] ?? null) ? $this->payload['payload'] : [];

        $chat = Arr::get($payload, 'chat', []);
        $message = Arr::get($payload, 'message', []);

        $token = Arr::get($payload, 'token');
        $conexao = $token ? Conexao::with('cliente')->where('whatsapp_api_key', $token)->first() : null;
        if (!$conexao) {
            return;
        }

        if (!$this->userStatus($conexao)) {
            return;
        }

        $phone = $this->resolveWhatsappNumber($message, $chat);
        if (!$phone) {
            return;
        }

        $textCandidate = Arr::get($message, 'text');
        if (!is_string($textCandidate) || trim($textCandidate) === '') {
            $textCandidate = Arr::get($message, 'content.caption')
                ?? Arr::get($message, 'content.text')
                ?? (is_string(Arr::get($message, 'content')) ? Arr::get($message, 'content') : null);
        }
        $text = $textCandidate ?? '';
        $eventId = Arr::get($message, 'id') ?? Arr::get($message, 'messageid');
        $messageTimestamp = Arr::get($message, 'messageTimestamp') ?? Arr::get($chat, 'wa_lastMsgTimestamp');
        $messageType = Arr::get($message, 'messageType') ?? Arr::get($message, 'type');
        $fromMe = Arr::get($message, 'fromMe') === true;

        if (!$this->deduplicateEvent($conexao, $phone, $eventId, $messageType, $messageTimestamp, $text)) {
            return;
        }

        $isGroup = Arr::get($message, 'isGroup');
        if ($isGroup === true) {
            return;
        }

        $leadName = $this->resolveLeadName($message, $chat, $phone);
        $clienteLead = ClienteLead::where('cliente_id', $conexao->cliente_id)
            ->where('phone', $phone)
            ->first();

        $tipoNormalizado = $this->normalizeTipo($tipoMensagem, $message);
        $media = $this->normalizeMediaPayload($message, $tipoNormalizado, $conexao);

        $normalized = [
            'phone' => $phone,
            'text' => is_string($text) ? trim($text) : '',
            'tipo' => $tipoNormalizado,
            'from_me' => $fromMe,
            'is_group' => $isGroup === true,
            'event_id' => $eventId,
            'message_timestamp' => $messageTimestamp,
            'message_type' => $messageType,
            'lead_name' => $leadName,
            'received_at' => $this->payload['received_at'] ?? null,
            'media' => $media,
        ];

        ProcessIncomingMessageJob::dispatch($conexao->id, $clienteLead?->id, $normalized);
    }

    private function resolveLeadName(array $message, array $chat, string $fallback): string
    {
        $candidate = Arr::get($message, 'senderName')
            ?? Arr::get($message, 'sender')
            ?? Arr::get($chat, 'wa_lastMessageSender')
            ?? Arr::get($chat, 'senderName')
            ?? $fallback;

        $normalized = trim((string) $candidate);
        return $normalized !== '' ? $normalized : $fallback;
    }

    private function resolveWhatsappNumber(array $message, array $chat): ?string
    {
        $candidates = [
            Arr::get($message, 'sender_pn'),
            Arr::get($message, 'chatid'),
            Arr::get($message, 'sender'),
            Arr::get($chat, 'wa_chatid'),
            Arr::get($chat, 'wa_lastMessageSender'),
        ];

        foreach ($candidates as $candidate) {
            $normalized = $this->normalizeWhatsappNumber($candidate);
            if ($normalized) {
                return $normalized;
            }
        }

        return null;
    }

    private function normalizeWhatsappNumber(?string $value): ?string
    {
        if (empty($value)) {
            return null;
        }

        $value = trim($value);
        $lower = Str::lower($value);

        if (!Str::endsWith($lower, '@s.whatsapp.net')) {
            return null;
        }

        $number = str_replace('@s.whatsapp.net', '', $lower);
        $digits = preg_replace('/\D/', '', $number);

        if (strlen($digits) < 11 || strlen($digits) > 14) {
            return null;
        }

        return $digits;
    }

    private function normalizeTipo(string $tipoMensagem, array $message): string
    {
        $value = trim($tipoMensagem);
        if ($value === '') {
            $value = (string) (Arr::get($message, 'messageType') ?? Arr::get($message, 'type'));
        }

        $lower = Str::lower($value);
        if (in_array($lower, ['text', 'conversation'], true)) {
            return 'text';
        }

        return $value !== '' ? $value : 'unknown';
    }

    private function extractMediaPayload(array $message): array
    {
        $content = Arr::get($message, 'content', []);
        if (!is_array($content)) {
            return [];
        }

        return array_filter([
            'url' => $content['URL'] ?? null,
            'mimetype' => $content['mimetype'] ?? null,
            'file_sha256' => $content['fileSHA256'] ?? null,
            'file_enc_sha256' => $content['fileEncSHA256'] ?? null,
            'media_key' => $content['mediaKey'] ?? null,
            'direct_path' => $content['directPath'] ?? null,
            'thumbnail' => $content['JPEGThumbnail'] ?? null,
            'filename' => $content['fileName'] ?? null,
        ], function ($value) {
            return $value !== null && $value !== '';
        });
    }

    private function normalizeMediaPayload(array $message, string $tipoNormalizado, Conexao $conexao): array
    {
        if (!in_array($tipoNormalizado, ['audio', 'image', 'document', 'video'], true)) {
            return [];
        }

        $raw = $this->extractMediaPayload($message);
        if (empty($raw)) {
            return [];
        }

        $mimetype = $raw['mimetype'] ?? null;
        $filename = $raw['filename'] ?? null;
        $type = $tipoNormalizado;

        if ($type === 'document' && !$this->isAllowedDocument($mimetype, $filename)) {
            Log::channel('uazapijob')->warning('Documento não permitido na whitelist.', [
                'mimetype' => $mimetype,
                'filename' => $filename,
            ]);
            return [];
        }

        $media = [
            'type' => $type,
            'mimetype' => $mimetype ?: $this->defaultMimetypeForType($type),
            'filename' => $filename ?: $this->defaultFilenameForType($type, $mimetype),
        ];

        $mediaPayload = $this->decryptMediaBase64($message, $tipoNormalizado, $raw);
        if ($mediaPayload === null) {
            return [];
        }

        $base64 = $mediaPayload['base64'] ?? null;
        if (!is_string($base64) || $base64 === '') {
            return [];
        }

        if (!empty($mediaPayload['mimetype'])) {
            $media['mimetype'] = $mediaPayload['mimetype'];
        }

        $sizeBytes = $this->base64DecodedSize($base64);
        if ($sizeBytes === null) {
            Log::channel('uazapijob')->warning('Base64 inválido após descriptografia.', [
                'type' => $type,
                'mimetype' => $media['mimetype'],
                'filename' => $media['filename'],
            ]);
            return [];
        }
        $media['size_bytes'] = $sizeBytes;

        $limit = $this->base64LimitForType($type);
        if ($type === 'video' || $sizeBytes > $limit) {
            $storageKey = $this->storeBase64AsBinary($base64, $media['filename']);
            if ($storageKey) {
                $media['storage_key'] = $storageKey;
            }
        } else {
            $media['base64'] = $base64;
        }

        if ($this->shouldIncludeMediaRaw()) {
            $media['raw'] = $raw;
        }

        return $media;
    }

    private function decryptMediaBase64(array $message, string $tipoMensagem, array $raw): ?array
    {
        $hasUrl = !empty($raw['url']) || !empty($raw['direct_path']);
        $hasKey = !empty($raw['media_key']);

        if (!$hasUrl || !$hasKey) {
            Log::channel('uazapijob')->warning('Mídia incompleta para descriptografia.', [
                'has_url' => $hasUrl,
                'has_key' => $hasKey,
            ]);
            return null;
        }

        $mediaDecrypt = new MediaDecryptService();
        $mediaPayload = $mediaDecrypt->decrypt($message, $tipoMensagem);
        if (!$mediaPayload) {
            Log::channel('uazapijob')->warning('Falha ao descriptografar mídia.');
            return null;
        }

        return $mediaPayload;
    }

    private function base64LimitForType(string $type): int
    {
        if ($type === 'audio') {
            return self::MAX_INLINE_BYTES_AUDIO;
        }

        return self::MAX_INLINE_BYTES_DEFAULT;
    }

    private function storeBase64AsBinary(string $base64, string $filename): ?string
    {
        $binary = base64_decode($base64, true);
        if ($binary === false) {
            Log::channel('uazapijob')->warning('Falha ao decodificar base64 para storage.');
            throw new \RuntimeException('Falha ao decodificar base64.');
        }

        $extension = pathinfo($filename, PATHINFO_EXTENSION);
        $extension = $extension !== '' ? ".{$extension}" : '';
        $key = 'uazapi_media/' . (string) Str::uuid() . $extension;

        $diskName = config('media.disk', 'local');
        $disk = $this->mediaDisk();
        if (!$disk->put($key, $binary)) {
            Log::channel('uazapijob')->warning('Falha ao salvar mídia no storage.', [
                'disk' => $diskName,
                'path' => $key,
            ]);
            throw new \RuntimeException('Falha ao salvar mídia.');
        }

        return $key;
    }

    private function base64DecodedSize(string $base64): ?int
    {
        $decoded = base64_decode($base64, true);
        if ($decoded === false) {
            return null;
        }

        return strlen($decoded);
    }

    private function shouldIncludeMediaRaw(): bool
    {
        if (config('app.debug')) {
            return true;
        }

        return (bool) config('media.raw_enabled', false);
    }

    private function mediaDisk()
    {
        return Storage::disk(config('media.disk', 'local'));
    }

    private function defaultMimetypeForType(string $type): string
    {
        return match ($type) {
            'audio' => 'audio/ogg',
            'image' => 'image/jpeg',
            'video' => 'video/mp4',
            default => 'application/octet-stream',
        };
    }

    private function defaultFilenameForType(string $type, ?string $mimetype): string
    {
        $ext = $this->extensionFromMimetype($mimetype);
        $suffix = $ext ? ".{$ext}" : '';

        return match ($type) {
            'audio' => 'audio' . $suffix,
            'image' => 'imagem' . $suffix,
            'video' => 'video' . $suffix,
            default => 'documento' . $suffix,
        };
    }

    private function extensionFromMimetype(?string $mimetype): ?string
    {
        if (!$mimetype) {
            return null;
        }

        return match (strtolower($mimetype)) {
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'image/webp' => 'webp',
            'audio/ogg' => 'ogg',
            'audio/mpeg' => 'mp3',
            'video/mp4' => 'mp4',
            'application/pdf' => 'pdf',
            'application/msword' => 'doc',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => 'docx',
            'application/vnd.ms-excel' => 'xls',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' => 'xlsx',
            'text/plain' => 'txt',
            default => null,
        };
    }

    private function isAllowedDocument(?string $mimetype, ?string $filename): bool
    {
        $allowedMimetypes = [
            'application/pdf',
            'application/msword',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'application/vnd.ms-excel',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'text/plain',
        ];

        if ($mimetype && in_array(strtolower($mimetype), $allowedMimetypes, true)) {
            return true;
        }

        if ($filename) {
            $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
            return in_array($ext, ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'txt'], true);
        }

        return false;
    }

    private function userStatus(Conexao $conexao): bool
    {
        return !empty($conexao->cliente) && !empty($conexao->cliente->user_id);
    }

    private function deduplicateEvent(Conexao $conexao, string $phone, ?string $eventId, ?string $messageType, $messageTimestamp, string $text): bool
    {
        $eventId = is_string($eventId) ? trim($eventId) : '';
        if ($eventId !== '') {
            $dedupKey = "dedup:uazapi:{$conexao->id}:{$eventId}";
        } else {
            $fallback = hash('sha256', implode('|', [
                $conexao->id,
                $phone,
                (string) ($messageType ?? ''),
                (string) ($messageTimestamp ?? ''),
                trim($text),
            ]));
            $dedupKey = "dedup:uazapi:{$conexao->id}:fallback:{$fallback}";
        }

        return Cache::add($dedupKey, true, now()->addMinutes(self::DEDUP_TTL_MINUTES));
    }
}
