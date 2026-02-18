<?php

namespace App\Jobs;

use App\Models\ClienteLead;
use App\Models\Conexao;
use App\Support\LogContext;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class EvolutionApiOficialJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private const MAX_INLINE_BYTES_DEFAULT = 300000;
    private const MAX_INLINE_BYTES_AUDIO = 500000;
    private const MAX_DOWNLOAD_BYTES = 15000000;
    private const DEDUP_TTL_MINUTES = 10;

    public int $tries = 1;
    public int $timeout = 60;

    protected array $payload;
    protected array $logContextBase = [];

    public function __construct(array $payload)
    {
        $this->payload = $payload;
    }

    public function handle(): void
    {
        $status = 'success';
        $reason = null;

        try {
            $event = $this->normalizeEvent((string) ($this->payload['event'] ?? ''));
            $payload = is_array($this->payload['payload'] ?? null) ? $this->payload['payload'] : [];
            $instance = Arr::get($payload, 'instance') ?? Arr::get($payload, 'instanceName');

            $this->logContextBase = LogContext::merge(
                LogContext::jobContext($this),
                [
                    'provider' => 'api_oficial',
                    'event' => $event,
                    'instance' => $instance,
                ]
            );

            if ($event !== 'messages.upsert') {
                $status = 'ignored';
                $reason = 'evento_nao_messages_upsert';
                return;
            }

            $conexaoId = $this->normalizeConexaoId($instance);
            if (!$conexaoId) {
                $status = 'ignored';
                $reason = 'instance_invalida';
                return;
            }

            $conexao = Conexao::with(['cliente', 'whatsappApi', 'credential.iaplataforma'])->find($conexaoId);
            if (!$conexao) {
                $status = 'ignored';
                $reason = 'conexao_nao_encontrada';
                return;
            }

            if ($conexao->whatsappApi?->slug !== 'api_oficial') {
                $status = 'ignored';
                $reason = 'provider_nao_api_oficial';
                return;
            }

            if (!$this->userStatus($conexao)) {
                $status = 'ignored';
                $reason = 'usuario_invalido';
                return;
            }

            $data = is_array(Arr::get($payload, 'data')) ? Arr::get($payload, 'data') : [];
            $key = is_array(Arr::get($data, 'key')) ? Arr::get($data, 'key') : [];
            $message = is_array(Arr::get($data, 'message')) ? Arr::get($data, 'message') : [];

            $remoteJid = (string) ($key['remoteJid'] ?? '');
            if ($this->isGroupJid($remoteJid)) {
                $status = 'ignored';
                $reason = 'mensagem_grupo';
                return;
            }

            $phone = $this->normalizeWhatsappNumberFromRemoteJid($remoteJid);
            if (!$phone) {
                $status = 'ignored';
                $reason = 'telefone_invalido';
                return;
            }

            $messageType = (string) ($data['messageType'] ?? '');
            $tipoNormalizado = $this->normalizeTipo($messageType, $message);
            if ($tipoNormalizado === null) {
                $status = 'ignored';
                $reason = 'tipo_nao_suportado';
                return;
            }

            $text = $this->resolveText($message);
            if ($tipoNormalizado === 'text' && $text === '') {
                $status = 'ignored';
                $reason = 'texto_vazio';
                return;
            }

            $eventIdRaw = trim((string) ($key['id'] ?? ''));
            $eventId = $eventIdRaw !== ''
                ? 'evo:' . $eventIdRaw
                : 'evo:fallback:' . hash('sha256', implode('|', [
                    $conexao->id,
                    $phone,
                    $messageType,
                    (string) ($data['messageTimestamp'] ?? ''),
                    $text,
                ]));

            $this->logContextBase = LogContext::merge(
                LogContext::jobContext($this),
                LogContext::base([
                    'conexao_id' => $conexao->id,
                    'phone' => $phone,
                    'event_id' => $eventId,
                    'message_type' => $messageType,
                    'provider' => 'api_oficial',
                ], $conexao)
            );

            if (!$this->deduplicateEvent($conexao, $phone, $eventIdRaw, $messageType, Arr::get($data, 'messageTimestamp'), $text)) {
                $status = 'ignored';
                $reason = 'duplicado';
                return;
            }

            $media = $this->normalizeMediaPayload($message, $tipoNormalizado, $conexao);
            if (in_array($tipoNormalizado, ['audio', 'image', 'document'], true) && empty($media)) {
                $status = 'ignored';
                $reason = 'media_nao_processada';
                return;
            }

            $normalized = [
                'phone' => $phone,
                'text' => $text,
                'tipo' => $tipoNormalizado,
                'from_me' => Arr::get($key, 'fromMe') === true,
                'is_group' => false,
                'event_id' => $eventId,
                'message_timestamp' => Arr::get($data, 'messageTimestamp'),
                'message_type' => $messageType,
                'lead_name' => $this->resolveLeadName((string) (Arr::get($data, 'pushName') ?? ''), $phone),
                'received_at' => $this->payload['received_at'] ?? null,
                'media' => $media,
                'provider' => 'api_oficial',
                'provider_event' => $event,
                'provider_instance' => (string) $instance,
                'provider_instance_id' => Arr::get($data, 'instanceId'),
            ];

            $clienteLead = ClienteLead::where('cliente_id', $conexao->cliente_id)
                ->where('phone', $phone)
                ->first();

            ProcessIncomingMessageJob::dispatch($conexao->id, $clienteLead?->id, $normalized)
                ->onQueue('processarconversa');
        } catch (\Throwable $exception) {
            $status = 'failed';
            $reason = 'exception';
            throw $exception;
        } finally {
            Log::channel('evolution_oficial_job')->info('EvolutionApiOficialJob finalizado.', $this->logContext([
                'status' => $status,
                'reason' => $reason,
            ]));
        }
    }

    private function normalizeConexaoId(mixed $instance): ?int
    {
        if (is_int($instance) && $instance > 0) {
            return $instance;
        }

        if (!is_string($instance)) {
            return null;
        }

        $instance = trim($instance);
        if ($instance === '' || !preg_match('/^\d+$/', $instance)) {
            return null;
        }

        $id = (int) $instance;
        return $id > 0 ? $id : null;
    }

    private function normalizeEvent(string $event): string
    {
        if ($event === '') {
            return '';
        }

        return (string) Str::of(trim($event))
            ->lower()
            ->replace('-', '.');
    }

    private function normalizeTipo(string $messageType, array $message): ?string
    {
        $candidate = Str::lower(trim($messageType));

        if ($candidate === '') {
            if (array_key_exists('conversation', $message)) {
                $candidate = 'conversation';
            } elseif (array_key_exists('audioMessage', $message)) {
                $candidate = 'audiomessage';
            } elseif (array_key_exists('imageMessage', $message)) {
                $candidate = 'imagemessage';
            } elseif (array_key_exists('documentMessage', $message)) {
                $candidate = 'documentmessage';
            } elseif (array_key_exists('videoMessage', $message)) {
                $candidate = 'videomessage';
            }
        }

        return match ($candidate) {
            'conversation', 'extendedtextmessage' => 'text',
            'audiomessage' => 'audio',
            'imagemessage' => 'image',
            'documentmessage' => 'document',
            'videomessage' => 'video',
            default => null,
        };
    }

    private function resolveText(array $message): string
    {
        $candidates = [
            Arr::get($message, 'conversation'),
            Arr::get($message, 'extendedTextMessage.text'),
            Arr::get($message, 'imageMessage.caption'),
            Arr::get($message, 'documentMessage.caption'),
            Arr::get($message, 'videoMessage.caption'),
        ];

        foreach ($candidates as $candidate) {
            if (is_string($candidate) && trim($candidate) !== '') {
                return trim($candidate);
            }
        }

        return '';
    }

    private function resolveLeadName(string $candidate, string $fallback): string
    {
        $name = trim($candidate);
        return $name !== '' ? $name : $fallback;
    }

    private function isGroupJid(string $remoteJid): bool
    {
        $jid = Str::lower(trim($remoteJid));
        return Str::endsWith($jid, '@g.us');
    }

    private function normalizeWhatsappNumberFromRemoteJid(string $remoteJid): ?string
    {
        $jid = Str::lower(trim($remoteJid));
        if ($jid === '' || !str_contains($jid, '@')) {
            return null;
        }

        [$number] = explode('@', $jid, 2);
        $digits = preg_replace('/\D/', '', $number);

        if (strlen($digits) < 11 || strlen($digits) > 14) {
            return null;
        }

        return $digits;
    }

    private function extractMediaPayload(array $message, string $tipoNormalizado): array
    {
        $bucket = match ($tipoNormalizado) {
            'audio' => Arr::get($message, 'audioMessage'),
            'image' => Arr::get($message, 'imageMessage'),
            'document' => Arr::get($message, 'documentMessage'),
            'video' => Arr::get($message, 'videoMessage'),
            default => null,
        };

        if (!is_array($bucket)) {
            return [];
        }

        return array_filter([
            'url' => $bucket['url'] ?? null,
            'mimetype' => $bucket['mime_type'] ?? $bucket['mimetype'] ?? null,
            'file_sha256' => $bucket['sha256'] ?? null,
            'id' => $bucket['id'] ?? null,
            'filename' => $bucket['filename'] ?? $bucket['fileName'] ?? null,
            'caption' => $bucket['caption'] ?? null,
        ], function ($value) {
            return $value !== null && $value !== '';
        });
    }

    private function normalizeMediaPayload(array $message, string $tipoNormalizado, Conexao $conexao): array
    {
        if (!in_array($tipoNormalizado, ['audio', 'image', 'document', 'video'], true)) {
            return [];
        }

        $raw = $this->extractMediaPayload($message, $tipoNormalizado);
        if (empty($raw)) {
            return [];
        }

        $mimetype = $raw['mimetype'] ?? null;
        $filename = $raw['filename'] ?? null;
        $type = $tipoNormalizado;

        if ($type === 'document' && !$this->isAllowedDocument($mimetype, $filename)) {
            Log::channel('evolution_oficial_job')->warning('Documento não permitido na whitelist.', $this->logContext([
                'mimetype' => $mimetype,
                'filename' => $filename,
                'conexao_id' => $conexao->id,
            ]));
            return [];
        }

        $media = [
            'type' => $type,
            'mimetype' => $mimetype ?: $this->defaultMimetypeForType($type),
            'filename' => $filename ?: $this->defaultFilenameForType($type, $mimetype),
            'url' => $raw['url'] ?? null,
            'file_id' => $raw['id'] ?? null,
            'file_sha256' => $raw['file_sha256'] ?? null,
        ];

        if ($type === 'video') {
            if ($this->shouldIncludeMediaRaw()) {
                $media['raw'] = $raw;
            }
            return array_filter($media, function ($value) {
                return $value !== null && $value !== '';
            });
        }

        $url = $raw['url'] ?? null;
        if (!is_string($url) || trim($url) === '') {
            Log::channel('evolution_oficial_job')->warning('URL de mídia ausente.', $this->logContext([
                'type' => $type,
                'conexao_id' => $conexao->id,
            ]));
            return [];
        }

        $binary = $this->downloadMediaBinary($url, $conexao, $type);
        if ($binary === null) {
            return [];
        }

        $sizeBytes = strlen($binary);
        if ($sizeBytes <= 0) {
            Log::channel('evolution_oficial_job')->warning('Mídia vazia após download.', $this->logContext([
                'type' => $type,
                'url' => $url,
                'conexao_id' => $conexao->id,
            ]));
            return [];
        }

        $media['size_bytes'] = $sizeBytes;
        $limit = $this->base64LimitForType($type);
        if ($sizeBytes > $limit) {
            $storageKey = $this->storeBinaryMedia($binary, $media['filename'], $conexao, $type);
            if (!$storageKey) {
                Log::channel('evolution_oficial_job')->warning('Falha ao persistir mídia no storage para payload normalizado.', $this->logContext([
                    'type' => $type,
                    'conexao_id' => $conexao->id,
                    'size_bytes' => $sizeBytes,
                ]));
                return [];
            }

            $media['storage_key'] = $storageKey;
        } else {
            $media['base64'] = base64_encode($binary);
        }

        if ($this->shouldIncludeMediaRaw()) {
            $media['raw'] = $raw;
        }

        return array_filter($media, function ($value) {
            return $value !== null && $value !== '';
        });
    }

    private function downloadMediaBinary(string $url, Conexao $conexao, string $type): ?string
    {
        $proxy = $this->resolveConexaoProxy($conexao);

        try {
            $response = $this->makeMediaDownloadRequest($url, $proxy['url'] ?? null);
        } catch (\Throwable $exception) {
            if (!empty($proxy['used'])) {
                try {
                    $response = $this->makeMediaDownloadRequest($url, null);
                } catch (\Throwable $fallbackException) {
                    Log::channel('evolution_oficial_job')->warning('Falha ao baixar mídia (proxy e fallback).', $this->logContext([
                        'type' => $type,
                        'url' => $url,
                        'conexao_id' => $conexao->id,
                        'proxy_host' => $proxy['host'] ?? null,
                        'error' => $fallbackException->getMessage(),
                    ]));
                    return null;
                }
            } else {
                Log::channel('evolution_oficial_job')->warning('Falha ao baixar mídia (exception).', $this->logContext([
                    'type' => $type,
                    'url' => $url,
                    'conexao_id' => $conexao->id,
                    'error' => $exception->getMessage(),
                ]));
                return null;
            }
        }

        if ($response->failed()) {
            if (!empty($proxy['used'])) {
                try {
                    $response = $this->makeMediaDownloadRequest($url, null);
                } catch (\Throwable $fallbackException) {
                    Log::channel('evolution_oficial_job')->warning('Falha ao baixar mídia (proxy status failed + fallback exception).', $this->logContext([
                        'type' => $type,
                        'url' => $url,
                        'conexao_id' => $conexao->id,
                        'proxy_host' => $proxy['host'] ?? null,
                        'status' => $response->status(),
                        'error' => $fallbackException->getMessage(),
                    ]));
                    return null;
                }
            }

            if ($response->failed()) {
                Log::channel('evolution_oficial_job')->warning('Falha ao baixar mídia.', $this->logContext([
                    'type' => $type,
                    'url' => $url,
                    'conexao_id' => $conexao->id,
                    'status' => $response->status(),
                    'proxy_used' => $proxy['used'] ?? false,
                    'proxy_host' => $proxy['host'] ?? null,
                ]));
                return null;
            }
        }

        $responseContentType = strtolower(trim((string) ($response->header('Content-Type') ?? '')));
        if (
            $this->configBool('media.evolution_oficial.validate_response_content_type', true)
            && !$this->isExpectedResponseContentType($type, $responseContentType)
        ) {
            Log::channel('evolution_oficial_job')->warning('Content-Type inesperado ao baixar mídia.', $this->logContext([
                'type' => $type,
                'url' => $url,
                'conexao_id' => $conexao->id,
                'content_type' => $responseContentType,
                'status' => $response->status(),
                'proxy_used' => $proxy['used'] ?? false,
                'proxy_host' => $proxy['host'] ?? null,
            ]));
            return null;
        }

        $binary = $response->body();
        if (!is_string($binary) || $binary === '') {
            Log::channel('evolution_oficial_job')->warning('Falha ao baixar mídia.', $this->logContext([
                'type' => $type,
                'url' => $url,
                'conexao_id' => $conexao->id,
                'status' => $response->status(),
                'proxy_used' => $proxy['used'] ?? false,
                'proxy_host' => $proxy['host'] ?? null,
            ]));
            return null;
        }

        $maxBytes = max(1, (int) config('media.evolution_oficial.max_download_bytes', self::MAX_DOWNLOAD_BYTES));
        if (strlen($binary) > $maxBytes) {
            Log::channel('evolution_oficial_job')->warning('Mídia excede limite máximo de download.', $this->logContext([
                'type' => $type,
                'url' => $url,
                'conexao_id' => $conexao->id,
                'size_bytes' => strlen($binary),
                'max_bytes' => $maxBytes,
            ]));
            return null;
        }

        return $binary;
    }

    private function makeMediaDownloadRequest(string $url, ?string $proxyUrl): \Illuminate\Http\Client\Response
    {
        $timeout = max(1, (int) config('media.evolution_oficial.download_timeout_seconds', 30));
        $retryTimes = max(0, (int) config('media.evolution_oficial.download_retry_times', 1));
        $retrySleepMs = max(0, (int) config('media.evolution_oficial.download_retry_sleep_ms', 500));

        $request = Http::timeout($timeout)
            ->retry(
                $retryTimes,
                $retrySleepMs
            );

        if ($proxyUrl) {
            $request = $request->withOptions([
                'proxy' => $proxyUrl,
            ]);
        }

        return $request->get($url);
    }

    private function isExpectedResponseContentType(string $type, string $contentType): bool
    {
        if ($contentType === '') {
            return true;
        }

        $baseType = strtolower(trim(explode(';', $contentType)[0]));
        if ($baseType === 'application/octet-stream') {
            return true;
        }

        return match ($type) {
            'audio' => str_starts_with($baseType, 'audio/'),
            'image' => str_starts_with($baseType, 'image/'),
            'video' => str_starts_with($baseType, 'video/'),
            'document' => in_array($baseType, [
                'application/pdf',
                'application/msword',
                'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                'application/vnd.ms-excel',
                'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                'text/plain',
            ], true),
            default => true,
        };
    }

    private function resolveConexaoProxy(Conexao $conexao): array
    {
        $useConexaoProxy = $this->configBool('media.evolution_oficial.use_conexao_proxy', true);
        if (!$useConexaoProxy) {
            return [
                'used' => false,
                'url' => null,
                'host' => null,
            ];
        }

        $host = trim((string) ($conexao->proxy_ip ?? ''));
        $port = (int) ($conexao->proxy_port ?? 0);
        if ($host === '' || $port <= 0) {
            return [
                'used' => false,
                'url' => null,
                'host' => null,
            ];
        }

        $username = trim((string) ($conexao->proxy_username ?? ''));
        $password = trim((string) ($conexao->proxy_password ?? ''));

        if ($username !== '') {
            $auth = rawurlencode($username);
            if ($password !== '') {
                $auth .= ':' . rawurlencode($password);
            }

            return [
                'used' => true,
                'url' => "http://{$auth}@{$host}:{$port}",
                'host' => $host,
            ];
        }

        return [
            'used' => true,
            'url' => "http://{$host}:{$port}",
            'host' => $host,
        ];
    }

    private function base64LimitForType(string $type): int
    {
        if ($type === 'audio') {
            return self::MAX_INLINE_BYTES_AUDIO;
        }

        return self::MAX_INLINE_BYTES_DEFAULT;
    }

    private function storeBinaryMedia(string $binary, string $filename, Conexao $conexao, string $type): ?string
    {
        $extension = pathinfo($filename, PATHINFO_EXTENSION);
        $extension = $extension !== '' ? ".{$extension}" : '';
        $key = 'evolution_oficial_media/' . (string) Str::uuid() . $extension;

        $diskName = config('media.disk', 'local');
        $disk = Storage::disk($diskName);
        if (!$disk->put($key, $binary)) {
            Log::channel('evolution_oficial_job')->warning('Falha ao salvar mídia no storage.', $this->logContext([
                'type' => $type,
                'disk' => $diskName,
                'path' => $key,
                'conexao_id' => $conexao->id,
            ]));
            return null;
        }

        return $key;
    }

    private function shouldIncludeMediaRaw(): bool
    {
        if (config('app.debug')) {
            return true;
        }

        return $this->configBool('media.raw_enabled', false);
    }

    private function configBool(string $key, bool $default): bool
    {
        $value = config($key);

        if (is_bool($value)) {
            return $value;
        }

        if ($value === null || $value === '') {
            return $default;
        }

        if (is_int($value) || is_float($value)) {
            return (bool) $value;
        }

        if (is_string($value)) {
            $normalized = trim(strtolower($value));
            if ($normalized === '') {
                return $default;
            }

            $parsed = filter_var($normalized, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
            return $parsed ?? $default;
        }

        return $default;
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

        $base = strtolower(trim(explode(';', $mimetype)[0]));

        return match ($base) {
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

        if ($mimetype) {
            $normalized = strtolower(trim(explode(';', $mimetype)[0]));
            if (in_array($normalized, $allowedMimetypes, true)) {
                return true;
            }
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

    private function deduplicateEvent(
        Conexao $conexao,
        string $phone,
        ?string $eventId,
        ?string $messageType,
        mixed $messageTimestamp,
        string $text
    ): bool {
        $eventId = is_string($eventId) ? trim($eventId) : '';
        if ($eventId !== '') {
            $dedupKey = "dedup:evo:{$conexao->id}:{$eventId}";
        } else {
            $fallback = hash('sha256', implode('|', [
                $conexao->id,
                $phone,
                (string) ($messageType ?? ''),
                (string) ($messageTimestamp ?? ''),
                trim($text),
            ]));
            $dedupKey = "dedup:evo:{$conexao->id}:fallback:{$fallback}";
        }

        return Cache::add($dedupKey, true, now()->addMinutes(self::DEDUP_TTL_MINUTES));
    }

    private function logContext(array $extra = []): array
    {
        return LogContext::merge($this->logContextBase, $extra);
    }

    public function failed(\Throwable $exception): void
    {
        Log::channel('evolution_oficial_job')->error('EvolutionApiOficialJob failed', $this->logContext([
            'error' => $exception->getMessage(),
            'exception' => get_class($exception),
        ]));
    }
}
