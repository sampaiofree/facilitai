<?php

namespace App\Jobs;

use App\Models\ClienteLead;
use App\Models\Conexao;
use App\Models\WhatsappCloudAccount;
use App\Services\WhatsappCloudConversationWindowService;
use App\Support\LogContext;
use App\Support\PhoneNumberNormalizer;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Carbon;
use Illuminate\Support\Arr;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class WhatsappCloudWebhookJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private const MAX_INLINE_BYTES_DEFAULT = 300000;
    private const MAX_INLINE_BYTES_AUDIO = 500000;
    private const MAX_DOWNLOAD_BYTES = 15000000;
    private const DEDUP_TTL_MINUTES = 10;

    public int $tries = 1;
    public int $timeout = 120;

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
        $processedMessages = 0;
        $ignoredMessages = 0;

        try {
            $userId = (int) ($this->payload['user_id'] ?? 0);
            if ($userId <= 0) {
                $status = 'ignored';
                $reason = 'user_id_invalido';
                return;
            }

            $validatedAccountIds = array_values(array_filter(array_map(
                fn ($id) => is_numeric($id) ? (int) $id : null,
                (array) ($this->payload['validated_account_ids'] ?? [])
            )));

            $payload = is_array($this->payload['payload'] ?? null) ? $this->payload['payload'] : [];
            if ((string) ($payload['object'] ?? '') !== 'whatsapp_business_account') {
                $status = 'ignored';
                $reason = 'objeto_invalido';
                return;
            }

            $entries = Arr::get($payload, 'entry', []);
            if (!is_array($entries) || empty($entries)) {
                $status = 'ignored';
                $reason = 'entry_ausente';
                return;
            }

            $this->logContextBase = LogContext::merge(
                LogContext::jobContext($this),
                [
                    'provider' => 'whatsapp_cloud',
                    'user_id' => $userId,
                ]
            );

            $accountCache = [];
            $conexaoCache = [];
            $conversationWindowService = app(WhatsappCloudConversationWindowService::class);

            foreach ($entries as $entry) {
                if (!is_array($entry)) {
                    continue;
                }

                $changes = Arr::get($entry, 'changes', []);
                if (!is_array($changes) || empty($changes)) {
                    continue;
                }

                foreach ($changes as $change) {
                    if (!is_array($change)) {
                        continue;
                    }

                    if ((string) ($change['field'] ?? '') !== 'messages') {
                        continue;
                    }

                    $value = Arr::get($change, 'value', []);
                    if (!is_array($value)) {
                        continue;
                    }

                    // Decisão da primeira versão: ignorar eventos de status.
                    $messages = Arr::get($value, 'messages', []);
                    if (!is_array($messages) || empty($messages)) {
                        continue;
                    }

                    $contacts = Arr::get($value, 'contacts', []);
                    if (!is_array($contacts)) {
                        $contacts = [];
                    }

                    foreach ($messages as $message) {
                        if (!is_array($message)) {
                            $ignoredMessages++;
                            continue;
                        }

                        $account = $this->resolveAccountFromValue(
                            $value,
                            $userId,
                            $validatedAccountIds,
                            $accountCache
                        );
                        if (!$account) {
                            $ignoredMessages++;
                            continue;
                        }

                        $conexao = $this->resolveConexao($account, $conexaoCache);
                        if (!$conexao) {
                            $ignoredMessages++;
                            continue;
                        }

                        if (!$this->userStatus($conexao)) {
                            $ignoredMessages++;
                            continue;
                        }

                        $normalized = $this->normalizeIncomingMessage($message, $value, $contacts, $conexao, $account);
                        if ($normalized === null) {
                            $ignoredMessages++;
                            continue;
                        }

                        if (!$this->deduplicateEvent($account->id, $normalized)) {
                            $ignoredMessages++;
                            continue;
                        }

                        $clienteLead = $this->resolveLeadForWindow($conexao, $normalized);
                        if ($clienteLead) {
                            $this->touchInboundWindow($conversationWindowService, $clienteLead, $conexao, $normalized);
                        }

                        ProcessIncomingMessageJob::dispatch($conexao->id, $clienteLead?->id, $normalized)
                            ->onQueue('processarconversa');

                        $processedMessages++;
                    }
                }
            }

            if ($processedMessages === 0) {
                $status = 'ignored';
                $reason = $ignoredMessages > 0 ? 'nenhuma_mensagem_processada' : 'sem_mensagens';
            }
        } catch (\Throwable $exception) {
            $status = 'failed';
            $reason = 'exception';
            throw $exception;
        } finally {
            Log::channel('whatsapp_cloud_job')->info('WhatsappCloudWebhookJob finalizado.', $this->logContext([
                'status' => $status,
                'reason' => $reason,
                'processed_messages' => $processedMessages,
                'ignored_messages' => $ignoredMessages,
            ]));
        }
    }

    private function resolveAccountFromValue(
        array $value,
        int $userId,
        array $validatedAccountIds,
        array &$accountCache
    ): ?WhatsappCloudAccount {
        $metadataPhoneNumberId = trim((string) (Arr::get($value, 'metadata.phone_number_id') ?? ''));
        if ($metadataPhoneNumberId === '') {
            return null;
        }

        if (array_key_exists($metadataPhoneNumberId, $accountCache)) {
            return $accountCache[$metadataPhoneNumberId];
        }

        $query = WhatsappCloudAccount::query()
            ->where('user_id', $userId)
            ->where('phone_number_id', $metadataPhoneNumberId);

        if (!empty($validatedAccountIds)) {
            $query->whereIn('id', $validatedAccountIds);
        }

        $account = $query->first();
        $accountCache[$metadataPhoneNumberId] = $account;

        return $account;
    }

    private function resolveConexao(WhatsappCloudAccount $account, array &$conexaoCache): ?Conexao
    {
        if (array_key_exists($account->id, $conexaoCache)) {
            return $conexaoCache[$account->id];
        }

        $conexao = Conexao::query()
            ->with(['cliente', 'whatsappApi'])
            ->where('whatsapp_cloud_account_id', $account->id)
            ->whereHas('whatsappApi', fn ($query) => $query->where('slug', 'whatsapp_cloud'))
            ->first();

        $conexaoCache[$account->id] = $conexao;

        return $conexao;
    }

    private function normalizeIncomingMessage(
        array $message,
        array $value,
        array $contacts,
        Conexao $conexao,
        WhatsappCloudAccount $account
    ): ?array {
        $messageType = Str::lower(trim((string) ($message['type'] ?? '')));
        if ($messageType === '') {
            return null;
        }

        $phone = $this->normalizePhone((string) ($message['from'] ?? ''));
        if (!$phone) {
            return null;
        }

        $metadataPhoneNumberId = trim((string) (Arr::get($value, 'metadata.phone_number_id') ?? ''));
        if (
            $metadataPhoneNumberId !== ''
            && (string) $account->phone_number_id !== ''
            && $metadataPhoneNumberId !== (string) $account->phone_number_id
        ) {
            Log::channel('whatsapp_cloud_job')->warning('Mensagem ignorada: phone_number_id divergente da conta.', $this->logContext([
                'account_id' => $account->id,
                'metadata_phone_number_id' => $metadataPhoneNumberId,
                'account_phone_number_id' => (string) $account->phone_number_id,
            ]));

            return null;
        }

        $tipo = $this->normalizeTipo($messageType, $message);
        if ($tipo === null) {
            return null;
        }

        $text = $this->resolveText($messageType, $message);

        $media = $this->normalizeMediaPayload($messageType, $message, $account, $conexao);
        if (in_array($tipo, ['audio', 'image', 'document'], true) && empty($media)) {
            return null;
        }

        $messageId = trim((string) ($message['id'] ?? ''));
        $timestamp = Arr::get($message, 'timestamp');
        $eventId = $messageId !== ''
            ? 'cloud:' . $messageId
            : 'cloud:fallback:' . hash('sha256', implode('|', [
                $account->id,
                $phone,
                $messageType,
                (string) $timestamp,
                $text,
            ]));

        return [
            'phone' => $phone,
            'text' => $text,
            'tipo' => $tipo,
            'from_me' => false,
            'is_group' => false,
            'event_id' => $eventId,
            'event_id_raw' => $messageId,
            'message_timestamp' => $timestamp,
            'message_type' => $messageType,
            'lead_name' => $this->resolveLeadName($contacts, $phone),
            'received_at' => $this->payload['received_at'] ?? null,
            'media' => $media,
            'provider' => 'whatsapp_cloud',
            'provider_event' => 'messages',
            'provider_instance' => (string) $account->id,
            'provider_instance_id' => $account->id,
        ];
    }

    private function resolveLeadForWindow(Conexao $conexao, array $normalized): ?ClienteLead
    {
        $phone = trim((string) ($normalized['phone'] ?? ''));
        if ($phone === '') {
            return null;
        }

        $phoneCandidates = $this->phoneNumberNormalizer()->buildLeadPhoneLookupCandidates($phone);
        if (empty($phoneCandidates)) {
            return null;
        }

        $canonicalPhone = $phoneCandidates[0];
        $leadName = trim((string) ($normalized['lead_name'] ?? ''));
        if ($leadName === '') {
            $leadName = $canonicalPhone;
        }

        $existing = ClienteLead::query()
            ->where('cliente_id', $conexao->cliente_id)
            ->whereIn('phone', $phoneCandidates)
            ->first();

        if ($existing) {
            return $existing;
        }

        try {
            return ClienteLead::query()->create([
                'cliente_id' => $conexao->cliente_id,
                'phone' => $canonicalPhone,
                'name' => $leadName,
                'info' => null,
                'bot_enabled' => true,
            ]);
        } catch (QueryException) {
            return ClienteLead::query()
                ->where('cliente_id', $conexao->cliente_id)
                ->whereIn('phone', $phoneCandidates)
                ->first();
        }
    }

    private function touchInboundWindow(
        WhatsappCloudConversationWindowService $windowService,
        ClienteLead $lead,
        Conexao $conexao,
        array $normalized
    ): void {
        try {
            $windowService->touchInbound(
                (int) $lead->id,
                (int) $conexao->id,
                $this->parseInboundTimestamp($normalized),
                trim((string) ($normalized['event_id_raw'] ?? $normalized['event_id'] ?? '')) ?: null
            );
        } catch (\Throwable $exception) {
            Log::channel('whatsapp_cloud_job')->warning('Falha ao atualizar janela de conversa Cloud.', $this->logContext([
                'lead_id' => $lead->id,
                'conexao_id' => $conexao->id,
                'error' => $exception->getMessage(),
            ]));
        }
    }

    private function parseInboundTimestamp(array $normalized): Carbon
    {
        $timestamp = $normalized['message_timestamp'] ?? null;
        if (is_numeric($timestamp)) {
            return Carbon::createFromTimestampUTC((int) $timestamp);
        }

        if (is_string($timestamp) && trim($timestamp) !== '') {
            try {
                return Carbon::parse($timestamp, 'UTC')->setTimezone('UTC');
            } catch (\Throwable) {
                // fallback below
            }
        }

        return Carbon::now('UTC');
    }

    private function normalizeTipo(string $messageType, array $message): ?string
    {
        return match ($messageType) {
            'text', 'button', 'interactive' => 'text',
            'audio' => 'audio',
            'image' => 'image',
            'document' => 'document',
            'video' => 'video',
            default => null,
        };
    }

    private function resolveText(string $messageType, array $message): string
    {
        if ($messageType === 'text') {
            return trim((string) (Arr::get($message, 'text.body') ?? ''));
        }

        if ($messageType === 'button') {
            return trim((string) (Arr::get($message, 'button.text') ?? Arr::get($message, 'button.payload') ?? ''));
        }

        if ($messageType === 'interactive') {
            $candidates = [
                Arr::get($message, 'interactive.button_reply.title'),
                Arr::get($message, 'interactive.button_reply.id'),
                Arr::get($message, 'interactive.list_reply.title'),
                Arr::get($message, 'interactive.list_reply.id'),
                Arr::get($message, 'interactive.nfm_reply.response_json'),
            ];

            foreach ($candidates as $candidate) {
                if (is_string($candidate) && trim($candidate) !== '') {
                    return trim($candidate);
                }
            }

            return '';
        }

        $captionCandidates = [
            Arr::get($message, 'image.caption'),
            Arr::get($message, 'document.caption'),
            Arr::get($message, 'video.caption'),
        ];

        foreach ($captionCandidates as $candidate) {
            if (is_string($candidate) && trim($candidate) !== '') {
                return trim($candidate);
            }
        }

        return '';
    }

    private function resolveLeadName(array $contacts, string $phone): string
    {
        foreach ($contacts as $contact) {
            if (!is_array($contact)) {
                continue;
            }

            $waId = $this->normalizePhone((string) ($contact['wa_id'] ?? ''));
            if ($waId !== $phone) {
                continue;
            }

            $name = trim((string) (Arr::get($contact, 'profile.name') ?? ''));
            if ($name !== '') {
                return $name;
            }
        }

        return $phone;
    }

    private function normalizePhone(string $value): ?string
    {
        return $this->phoneNumberNormalizer()->normalizeLeadPhone($value);
    }

    private function phoneNumberNormalizer(): PhoneNumberNormalizer
    {
        return app(PhoneNumberNormalizer::class);
    }

    private function normalizeMediaPayload(
        string $messageType,
        array $message,
        WhatsappCloudAccount $account,
        Conexao $conexao
    ): array {
        if (!in_array($messageType, ['audio', 'image', 'document', 'video'], true)) {
            return [];
        }

        $bucket = Arr::get($message, $messageType, []);
        if (!is_array($bucket)) {
            return [];
        }

        $mediaId = trim((string) ($bucket['id'] ?? ''));
        if ($mediaId === '') {
            return [];
        }

        $metadata = $this->fetchMediaMetadata($mediaId, $account, $conexao, $messageType);
        if ($metadata === null) {
            return [];
        }

        $mimetype = trim((string) ($bucket['mime_type'] ?? $metadata['mime_type'] ?? ''));
        $filename = trim((string) ($bucket['filename'] ?? ''));

        if ($messageType === 'document' && !$this->isAllowedDocument($mimetype, $filename)) {
            Log::channel('whatsapp_cloud_job')->warning('Documento não permitido na whitelist (Cloud).', $this->logContext([
                'conexao_id' => $conexao->id,
                'media_id' => $mediaId,
                'mimetype' => $mimetype,
                'filename' => $filename,
            ]));
            return [];
        }

        $media = [
            'type' => $messageType,
            'mimetype' => $mimetype !== '' ? $mimetype : $this->defaultMimetypeForType($messageType),
            'filename' => $filename !== '' ? $filename : $this->defaultFilenameForType($messageType, $mimetype),
            'url' => $metadata['url'] ?? null,
            'file_id' => $mediaId,
            'file_sha256' => $metadata['sha256'] ?? null,
        ];

        if ($messageType === 'video') {
            if ($this->shouldIncludeMediaRaw()) {
                $media['raw'] = [
                    'bucket' => $bucket,
                    'metadata' => $metadata,
                ];
            }

            return array_filter($media, fn ($value) => $value !== null && $value !== '');
        }

        $binary = $this->downloadMediaBinary($metadata, $account, $conexao, $messageType);
        if ($binary === null) {
            return [];
        }

        $sizeBytes = strlen($binary);
        if ($sizeBytes <= 0) {
            return [];
        }

        $media['size_bytes'] = $sizeBytes;
        $limit = $this->base64LimitForType($messageType);

        if ($sizeBytes > $limit) {
            $storageKey = $this->storeBinaryMedia($binary, $media['filename'], $conexao, $messageType);
            if ($storageKey === null) {
                return [];
            }

            $media['storage_key'] = $storageKey;
        } else {
            $media['base64'] = base64_encode($binary);
        }

        if ($this->shouldIncludeMediaRaw()) {
            $media['raw'] = [
                'bucket' => $bucket,
                'metadata' => $metadata,
            ];
        }

        return array_filter($media, fn ($value) => $value !== null && $value !== '');
    }

    private function fetchMediaMetadata(
        string $mediaId,
        WhatsappCloudAccount $account,
        Conexao $conexao,
        string $type
    ): ?array {
        $accessToken = trim((string) ($account->access_token ?? ''));
        if ($accessToken === '') {
            Log::channel('whatsapp_cloud_job')->warning('Conta Cloud sem access token para download de mídia.', $this->logContext([
                'conexao_id' => $conexao->id,
                'account_id' => $account->id,
                'media_id' => $mediaId,
                'type' => $type,
            ]));
            return null;
        }

        $baseUrl = rtrim((string) config('services.whatsapp_cloud.base_url', 'https://graph.facebook.com'), '/');
        $version = trim((string) config('services.whatsapp_cloud.version', 'v23.0'));
        $timeout = max(3, (int) config('services.whatsapp_cloud.timeout', 15));
        $retryTimes = max(0, (int) config('services.whatsapp_cloud.retry_times', 1));
        $retrySleepMs = max(0, (int) config('services.whatsapp_cloud.retry_sleep_ms', 300));

        $url = sprintf('%s/%s/%s', $baseUrl, $version, $mediaId);

        $response = Http::timeout($timeout)
            ->retry($retryTimes, $retrySleepMs)
            ->withToken($accessToken)
            ->acceptJson()
            ->get($url);

        if ($response->failed()) {
            Log::channel('whatsapp_cloud_job')->warning('Falha ao obter metadata de mídia na Cloud API.', $this->logContext([
                'conexao_id' => $conexao->id,
                'account_id' => $account->id,
                'media_id' => $mediaId,
                'type' => $type,
                'status' => $response->status(),
            ]));
            return null;
        }

        $body = $response->json();
        if (!is_array($body)) {
            return null;
        }

        return [
            'id' => $body['id'] ?? $mediaId,
            'url' => $body['url'] ?? null,
            'mime_type' => $body['mime_type'] ?? null,
            'sha256' => $body['sha256'] ?? null,
            'file_size' => $body['file_size'] ?? null,
        ];
    }

    private function downloadMediaBinary(
        array $metadata,
        WhatsappCloudAccount $account,
        Conexao $conexao,
        string $type
    ): ?string {
        $url = trim((string) ($metadata['url'] ?? ''));
        if ($url === '') {
            return null;
        }

        $accessToken = trim((string) ($account->access_token ?? ''));
        if ($accessToken === '') {
            return null;
        }

        $timeout = max(3, (int) config('services.whatsapp_cloud.timeout', 15));
        $retryTimes = max(0, (int) config('services.whatsapp_cloud.retry_times', 1));
        $retrySleepMs = max(0, (int) config('services.whatsapp_cloud.retry_sleep_ms', 300));

        $response = Http::timeout($timeout)
            ->retry($retryTimes, $retrySleepMs)
            ->withToken($accessToken)
            ->get($url);

        if ($response->failed()) {
            Log::channel('whatsapp_cloud_job')->warning('Falha ao baixar mídia pela Cloud API.', $this->logContext([
                'conexao_id' => $conexao->id,
                'account_id' => $account->id,
                'type' => $type,
                'status' => $response->status(),
            ]));
            return null;
        }

        $contentType = strtolower(trim((string) ($response->header('Content-Type') ?? '')));
        if (
            $this->configBool('media.whatsapp_cloud.validate_response_content_type', true)
            && !$this->isExpectedResponseContentType($type, $contentType)
        ) {
            Log::channel('whatsapp_cloud_job')->warning('Content-Type inesperado ao baixar mídia Cloud.', $this->logContext([
                'conexao_id' => $conexao->id,
                'account_id' => $account->id,
                'type' => $type,
                'content_type' => $contentType,
            ]));
            return null;
        }

        $binary = $response->body();
        if (!is_string($binary) || $binary === '') {
            return null;
        }

        $maxBytes = max(1, (int) config('media.whatsapp_cloud.max_download_bytes', self::MAX_DOWNLOAD_BYTES));
        if (strlen($binary) > $maxBytes) {
            Log::channel('whatsapp_cloud_job')->warning('Mídia Cloud excede limite máximo de download.', $this->logContext([
                'conexao_id' => $conexao->id,
                'account_id' => $account->id,
                'type' => $type,
                'size_bytes' => strlen($binary),
                'max_bytes' => $maxBytes,
            ]));
            return null;
        }

        return $binary;
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

    private function base64LimitForType(string $type): int
    {
        return $type === 'audio'
            ? self::MAX_INLINE_BYTES_AUDIO
            : self::MAX_INLINE_BYTES_DEFAULT;
    }

    private function storeBinaryMedia(string $binary, string $filename, Conexao $conexao, string $type): ?string
    {
        $extension = pathinfo($filename, PATHINFO_EXTENSION);
        $extension = $extension !== '' ? ".{$extension}" : '';
        $key = 'whatsapp_cloud_media/' . (string) Str::uuid() . $extension;

        $diskName = config('media.disk', 'local');
        $disk = Storage::disk($diskName);
        if (!$disk->put($key, $binary)) {
            Log::channel('whatsapp_cloud_job')->warning('Falha ao salvar mídia Cloud no storage.', $this->logContext([
                'conexao_id' => $conexao->id,
                'type' => $type,
                'disk' => $diskName,
                'path' => $key,
            ]));
            return null;
        }

        return $key;
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

    private function deduplicateEvent(int $accountId, array $normalizedPayload): bool
    {
        $eventIdRaw = trim((string) ($normalizedPayload['event_id_raw'] ?? ''));
        if ($eventIdRaw === '') {
            $eventIdRaw = trim((string) ($normalizedPayload['event_id'] ?? ''));
        }

        if ($eventIdRaw === '') {
            $eventIdRaw = hash('sha256', implode('|', [
                $accountId,
                (string) ($normalizedPayload['phone'] ?? ''),
                (string) ($normalizedPayload['message_type'] ?? ''),
                (string) ($normalizedPayload['message_timestamp'] ?? ''),
                (string) ($normalizedPayload['text'] ?? ''),
            ]));
        }

        $cacheKey = 'wa_cloud:dedup:' . $accountId . ':' . hash('sha1', $eventIdRaw);

        return Cache::add($cacheKey, true, now()->addMinutes(self::DEDUP_TTL_MINUTES));
    }

    private function userStatus(Conexao $conexao): bool
    {
        return !empty($conexao->cliente)
            && !empty($conexao->cliente->user_id)
            && (bool) $conexao->is_active;
    }

    private function logContext(array $context = []): array
    {
        return LogContext::merge($this->logContextBase, $context);
    }
}
