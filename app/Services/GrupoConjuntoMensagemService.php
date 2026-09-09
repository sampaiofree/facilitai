<?php

namespace App\Services;

use App\Models\GrupoConjuntoMensagem;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

class GrupoConjuntoMensagemService
{
    private ?GrupoConjuntoActionTimingService $timingService = null;

    public function __construct(
        private readonly UazapiGruposService $uazapiGruposService,
        ?GrupoConjuntoActionTimingService $timingService = null
    ) {
        $this->timingService = $timingService;
    }

    public function dispatchAndPersist(GrupoConjuntoMensagem $mensagem, int $attempt = 1): GrupoConjuntoMensagem
    {
        $mensagem->loadMissing(['conexao.whatsappApi', 'conjunto']);

        $attemptValue = max((int) $mensagem->attempts, $attempt);
        $nowUtc = Carbon::now('UTC');

        $conexao = $mensagem->conexao;
        $providerSlug = strtolower((string) ($conexao?->whatsappApi?->slug ?? ''));
        $token = trim((string) ($conexao?->whatsapp_api_key ?? ''));

        if (! $conexao || $providerSlug !== 'uazapi' || $token === '') {
            $mensagem->update([
                'status' => 'failed',
                'failed_at' => $nowUtc,
                'sent_at' => null,
                'error_message' => 'Conexao invalida para envio de mensagens em grupo.',
                'attempts' => $attemptValue,
            ]);

            return $this->freshMensagem($mensagem);
        }

        $recipients = $this->normalizeRecipients((array) ($mensagem->recipients ?? []));

        if ($recipients === []) {
            $mensagem->update([
                'status' => 'failed',
                'failed_at' => $nowUtc,
                'sent_at' => null,
                'error_message' => 'Nenhum destinatario valido encontrado para este conjunto.',
                'attempts' => $attemptValue,
            ]);

            return $this->freshMensagem($mensagem);
        }

        $resolvedAction = $this->resolveActionData($mensagem, count($recipients));
        if (! $resolvedAction['ok']) {
            $mensagem->update([
                'status' => 'failed',
                'failed_at' => $nowUtc,
                'sent_at' => null,
                'error_message' => (string) ($resolvedAction['message'] ?? 'Ação inválida para envio em grupo.'),
                'attempts' => $attemptValue,
            ]);

            return $this->freshMensagem($mensagem);
        }

        $actionType = (string) $resolvedAction['action_type'];
        $actionPayload = (array) $resolvedAction['payload'];

        $sentCount = 0;
        $failedCount = 0;
        $firstError = null;
        $resultRows = [];

        foreach ($recipients as $recipientIndex => $recipient) {
            $jid = (string) $recipient['jid'];
            $name = (string) $recipient['name'];
            $recipientActionPayload = $this->resolveRecipientActionPayload(
                $actionType,
                $actionPayload,
                $recipientIndex
            );

            $slotGranted = $this->timingService()->acquireDispatchSlot(
                (int) $mensagem->user_id,
                (int) $mensagem->conexao_id,
                $jid,
                $actionType
            );

            if (! $slotGranted) {
                $failedCount++;
                $slotMessage = 'Cooldown/backoff ativo para esta conexão. Tente novamente em alguns instantes.';
                if ($firstError === null) {
                    $firstError = $slotMessage;
                }

                $resultRows[] = [
                    'jid' => $jid,
                    'name' => $name,
                    'action_type' => $actionType,
                    'status' => 'failed',
                    'http_status' => 0,
                    'error' => $slotMessage,
                ];

                continue;
            }

            try {
                $response = $this->dispatchRecipientAction($token, $jid, $actionType, $recipientActionPayload);
            } catch (\Throwable $exception) {
                $response = [
                    'error' => true,
                    'status' => 0,
                    'body' => $exception->getMessage(),
                ];
            }

            $this->timingService()->registerRemoteStatus(
                (int) $mensagem->conexao_id,
                (int) ($response['status'] ?? 0)
            );

            if (empty($response['error'])) {
                $sentCount++;
                $resultRows[] = [
                    'jid' => $jid,
                    'name' => $name,
                    'action_type' => $actionType,
                    'status' => 'sent',
                    'http_status' => (int) ($response['status'] ?? 200),
                ];

                continue;
            }

            $failedCount++;
            $httpStatus = (int) ($response['status'] ?? 0);
            $message = Arr::get($response, 'body.message')
                ?? Arr::get($response, 'message')
                ?? (is_string($response['body'] ?? null) ? $response['body'] : 'Falha ao enviar para o grupo.');

            $errorText = trim((string) $message);
            if ($firstError === null && $errorText !== '') {
                $firstError = $errorText;
            }

            $resultRows[] = [
                'jid' => $jid,
                'name' => $name,
                'action_type' => $actionType,
                'status' => 'failed',
                'http_status' => $httpStatus,
                'error' => $errorText,
            ];
        }

        $isSuccess = $failedCount === 0 && $sentCount > 0;
        $status = $isSuccess ? 'sent' : 'failed';

        $mensagem->update([
            'status' => $status,
            'sent_count' => $sentCount,
            'failed_count' => $failedCount,
            'result' => [
                'items' => $resultRows,
                'sent_count' => $sentCount,
                'failed_count' => $failedCount,
            ],
            'sent_at' => $isSuccess ? $nowUtc : null,
            'failed_at' => $isSuccess ? null : $nowUtc,
            'error_message' => $isSuccess ? null : Str::limit($firstError ?: 'Falha no envio para um ou mais grupos.', 1900),
            'attempts' => $attemptValue,
        ]);

        return $this->freshMensagem($mensagem);
    }

    /**
     * @return array{mensagem: GrupoConjuntoMensagem, deferred: bool, delay_seconds: int, completed: bool}
     */
    public function dispatchRecipientAndPersist(
        GrupoConjuntoMensagem $mensagem,
        int $recipientIndex,
        int $attempt = 1
    ): array {
        $mensagem->loadMissing(['conexao.whatsappApi', 'conjunto']);

        $attemptValue = max((int) $mensagem->attempts, $attempt);
        $nowUtc = Carbon::now('UTC');

        $conexao = $mensagem->conexao;
        $providerSlug = strtolower((string) ($conexao?->whatsappApi?->slug ?? ''));
        $token = trim((string) ($conexao?->whatsapp_api_key ?? ''));

        if (! $conexao || $providerSlug !== 'uazapi' || $token === '') {
            return $this->completedRecipientResult($this->markMensagemAsFailed(
                $mensagem,
                'Conexao invalida para envio de mensagens em grupo.',
                $nowUtc,
                $attemptValue
            ));
        }

        $recipients = $this->normalizeRecipients((array) ($mensagem->recipients ?? []));

        if ($recipients === []) {
            return $this->completedRecipientResult($this->markMensagemAsFailed(
                $mensagem,
                'Nenhum destinatario valido encontrado para este conjunto.',
                $nowUtc,
                $attemptValue
            ));
        }

        $recipientIndex = max(0, $recipientIndex);
        if (! isset($recipients[$recipientIndex])) {
            return $this->finishRecipientDispatch($mensagem, $nowUtc, $attemptValue);
        }

        $resolvedAction = $this->resolveActionData($mensagem, count($recipients));
        if (! $resolvedAction['ok']) {
            return $this->completedRecipientResult($this->markMensagemAsFailed(
                $mensagem,
                (string) ($resolvedAction['message'] ?? 'Ação inválida para envio em grupo.'),
                $nowUtc,
                $attemptValue
            ));
        }

        $actionType = (string) $resolvedAction['action_type'];
        $actionPayload = (array) $resolvedAction['payload'];
        $recipient = $recipients[$recipientIndex];
        $jid = (string) $recipient['jid'];
        $isLastRecipient = $recipientIndex >= count($recipients) - 1;

        if ($this->recipientAlreadyProcessed($mensagem, $jid, $actionType)) {
            return $isLastRecipient
                ? $this->finishRecipientDispatch($mensagem, $nowUtc, $attemptValue)
                : [
                    'mensagem' => $this->freshMensagem($mensagem),
                    'deferred' => false,
                    'delay_seconds' => 0,
                    'completed' => false,
                ];
        }

        $waitSeconds = $this->timingService()->reserveDispatchSlot(
            (int) $mensagem->user_id,
            (int) $mensagem->conexao_id,
            $jid,
            $actionType
        );

        if ($waitSeconds > 0) {
            return [
                'mensagem' => $this->freshMensagem($mensagem),
                'deferred' => true,
                'delay_seconds' => max(1, (int) ceil($waitSeconds)),
                'completed' => false,
            ];
        }

        $recipientActionPayload = $this->resolveRecipientActionPayload(
            $actionType,
            $actionPayload,
            $recipientIndex
        );

        try {
            $response = $this->dispatchRecipientAction($token, $jid, $actionType, $recipientActionPayload);
        } catch (\Throwable $exception) {
            $response = [
                'error' => true,
                'status' => 0,
                'body' => $exception->getMessage(),
            ];
        }

        $this->timingService()->registerRemoteStatus(
            (int) $mensagem->conexao_id,
            (int) ($response['status'] ?? 0)
        );

        if (empty($response['error'])) {
            return $this->persistRecipientResult($mensagem, [
                'jid' => $jid,
                'name' => (string) $recipient['name'],
                'action_type' => $actionType,
                'status' => 'sent',
                'http_status' => (int) ($response['status'] ?? 200),
            ], $isLastRecipient, $nowUtc, $attemptValue);
        }

        $httpStatus = (int) ($response['status'] ?? 0);
        $message = Arr::get($response, 'body.message')
            ?? Arr::get($response, 'message')
            ?? (is_string($response['body'] ?? null) ? $response['body'] : 'Falha ao enviar para o grupo.');

        return $this->persistRecipientResult($mensagem, [
            'jid' => $jid,
            'name' => (string) $recipient['name'],
            'action_type' => $actionType,
            'status' => 'failed',
            'http_status' => $httpStatus,
            'error' => trim((string) $message),
        ], $isLastRecipient, $nowUtc, $attemptValue);
    }

    private function completedRecipientResult(GrupoConjuntoMensagem $mensagem): array
    {
        return [
            'mensagem' => $mensagem,
            'deferred' => false,
            'delay_seconds' => 0,
            'completed' => true,
        ];
    }

    private function markMensagemAsFailed(
        GrupoConjuntoMensagem $mensagem,
        string $errorMessage,
        Carbon $nowUtc,
        int $attemptValue
    ): GrupoConjuntoMensagem {
        $mensagem->update([
            'status' => 'failed',
            'failed_at' => $nowUtc,
            'sent_at' => null,
            'error_message' => Str::limit($errorMessage, 1900),
            'attempts' => $attemptValue,
        ]);

        return $this->freshMensagem($mensagem);
    }

    private function finishRecipientDispatch(
        GrupoConjuntoMensagem $mensagem,
        Carbon $nowUtc,
        int $attemptValue
    ): array {
        $rows = $this->resultRows($mensagem);
        $sentCount = $this->countResultRowsByStatus($rows, 'sent');
        $failedCount = $this->countResultRowsByStatus($rows, 'failed');
        $isSuccess = $failedCount === 0 && $sentCount > 0;

        $mensagem->update([
            'status' => $isSuccess ? 'sent' : 'failed',
            'sent_count' => $sentCount,
            'failed_count' => $failedCount,
            'result' => [
                'items' => $rows,
                'sent_count' => $sentCount,
                'failed_count' => $failedCount,
            ],
            'sent_at' => $isSuccess ? $nowUtc : null,
            'failed_at' => $isSuccess ? null : $nowUtc,
            'error_message' => $isSuccess ? null : Str::limit($this->firstResultError($rows) ?: 'Falha no envio para um ou mais grupos.', 1900),
            'attempts' => $attemptValue,
        ]);

        return $this->completedRecipientResult($this->freshMensagem($mensagem));
    }

    private function persistRecipientResult(
        GrupoConjuntoMensagem $mensagem,
        array $resultRow,
        bool $isLastRecipient,
        Carbon $nowUtc,
        int $attemptValue
    ): array {
        $rows = $this->replaceResultRow($this->resultRows($mensagem), $resultRow);
        $sentCount = $this->countResultRowsByStatus($rows, 'sent');
        $failedCount = $this->countResultRowsByStatus($rows, 'failed');

        $payload = [
            'status' => 'queued',
            'sent_count' => $sentCount,
            'failed_count' => $failedCount,
            'result' => [
                'items' => $rows,
                'sent_count' => $sentCount,
                'failed_count' => $failedCount,
            ],
            'sent_at' => null,
            'failed_at' => null,
            'error_message' => null,
            'attempts' => $attemptValue,
        ];

        if ($isLastRecipient) {
            $isSuccess = $failedCount === 0 && $sentCount > 0;

            $payload['status'] = $isSuccess ? 'sent' : 'failed';
            $payload['sent_at'] = $isSuccess ? $nowUtc : null;
            $payload['failed_at'] = $isSuccess ? null : $nowUtc;
            $payload['error_message'] = $isSuccess
                ? null
                : Str::limit($this->firstResultError($rows) ?: 'Falha no envio para um ou mais grupos.', 1900);
        }

        $mensagem->update($payload);

        return [
            'mensagem' => $this->freshMensagem($mensagem),
            'deferred' => false,
            'delay_seconds' => 0,
            'completed' => $isLastRecipient,
        ];
    }

    private function resultRows(GrupoConjuntoMensagem $mensagem): array
    {
        $items = data_get($mensagem->result, 'items', []);

        if (! is_array($items)) {
            return [];
        }

        return array_values(array_filter($items, static fn ($item): bool => is_array($item)));
    }

    private function replaceResultRow(array $rows, array $newRow): array
    {
        $jid = (string) ($newRow['jid'] ?? '');
        $actionType = (string) ($newRow['action_type'] ?? '');
        $replaced = false;

        foreach ($rows as $index => $row) {
            if ((string) ($row['jid'] ?? '') === $jid && (string) ($row['action_type'] ?? '') === $actionType) {
                $rows[$index] = $newRow;
                $replaced = true;
                break;
            }
        }

        if (! $replaced) {
            $rows[] = $newRow;
        }

        return array_values($rows);
    }

    private function recipientAlreadyProcessed(GrupoConjuntoMensagem $mensagem, string $jid, string $actionType): bool
    {
        foreach ($this->resultRows($mensagem) as $row) {
            if ((string) ($row['jid'] ?? '') !== $jid) {
                continue;
            }

            if ((string) ($row['action_type'] ?? '') !== $actionType) {
                continue;
            }

            if (in_array((string) ($row['status'] ?? ''), ['sent', 'failed'], true)) {
                return true;
            }
        }

        return false;
    }

    private function countResultRowsByStatus(array $rows, string $status): int
    {
        return count(array_filter(
            $rows,
            static fn (array $row): bool => (string) ($row['status'] ?? '') === $status
        ));
    }

    private function firstResultError(array $rows): ?string
    {
        foreach ($rows as $row) {
            $error = trim((string) ($row['error'] ?? ''));
            if ($error !== '') {
                return $error;
            }
        }

        return null;
    }

    private function freshMensagem(GrupoConjuntoMensagem $mensagem): GrupoConjuntoMensagem
    {
        return $mensagem->fresh() ?? $mensagem;
    }

    private function resolveActionData(GrupoConjuntoMensagem $mensagem, int $recipientCount): array
    {
        $actionType = $mensagem->resolveActionType();
        $payload = $mensagem->resolvePayload();

        return match ($actionType) {
            GrupoConjuntoMensagem::ACTION_SEND_MEDIA => $this->resolveSendMediaAction($payload),
            GrupoConjuntoMensagem::ACTION_UPDATE_GROUP_NAME => $this->resolveGroupNameAction($payload, $recipientCount),
            GrupoConjuntoMensagem::ACTION_UPDATE_GROUP_DESCRIPTION => $this->resolveSingleFieldAction($payload, 'group_description', 2048),
            GrupoConjuntoMensagem::ACTION_UPDATE_GROUP_IMAGE => $this->resolveGroupImageAction($payload),
            default => $this->resolveSendTextAction($payload, (string) $mensagem->mensagem),
        } + ['action_type' => $actionType];
    }

    private function resolveSendTextAction(array $payload, string $fallbackText): array
    {
        $text = trim((string) ($payload['text'] ?? $fallbackText));
        if ($text === '') {
            return [
                'ok' => false,
                'message' => 'Texto da mensagem não informado para envio.',
            ];
        }

        $mentionAll = $this->isTruthy($payload['mention_all'] ?? false);
        $resolvedPayload = ['text' => $text];
        if ($mentionAll) {
            $resolvedPayload['mention_all'] = true;
        }

        return [
            'ok' => true,
            'payload' => $resolvedPayload,
        ];
    }

    private function resolveSendMediaAction(array $payload): array
    {
        $mediaType = trim((string) ($payload['media_type'] ?? ''));
        $mediaUrl = trim((string) ($payload['media_url'] ?? ''));
        $caption = trim((string) ($payload['caption'] ?? ''));
        $mentionAll = $this->isTruthy($payload['mention_all'] ?? false);

        if (! in_array($mediaType, ['image', 'video', 'document', 'audio'], true)) {
            return [
                'ok' => false,
                'message' => 'Tipo de mídia inválido para envio.',
            ];
        }

        if (! $this->isHttpUrl($mediaUrl)) {
            return [
                'ok' => false,
                'message' => 'URL de mídia inválida para envio.',
            ];
        }

        $resolved = [
            'media_type' => $mediaType,
            'media_url' => $mediaUrl,
        ];

        if ($caption !== '') {
            $resolved['caption'] = $caption;
        }
        if ($mentionAll) {
            $resolved['mention_all'] = true;
        }

        return [
            'ok' => true,
            'payload' => $resolved,
        ];
    }

    private function resolveSingleFieldAction(array $payload, string $field, int $maxLength): array
    {
        $value = trim((string) ($payload[$field] ?? ''));
        if ($value === '') {
            return [
                'ok' => false,
                'message' => 'Valor da ação não informado.',
            ];
        }

        if (mb_strlen($value) > $maxLength) {
            return [
                'ok' => false,
                'message' => 'Valor da ação excede o limite permitido.',
            ];
        }

        return [
            'ok' => true,
            'payload' => [$field => $value],
        ];
    }

    private function resolveGroupNameAction(array $payload, int $recipientCount): array
    {
        $baseName = trim((string) ($payload['group_name'] ?? ''));
        if ($baseName === '') {
            return [
                'ok' => false,
                'message' => 'Título dos grupos não informado.',
            ];
        }

        $sequenceEnabled = $this->isTruthy($payload['group_name_sequence'] ?? false);
        if (! $sequenceEnabled) {
            return $this->resolveSingleFieldAction(['group_name' => $baseName], 'group_name', 25);
        }

        $sequenceStart = filter_var(
            $payload['group_name_sequence_start'] ?? null,
            FILTER_VALIDATE_INT,
            ['options' => ['min_range' => 1, 'max_range' => 999999999]]
        );

        if ($sequenceStart === false) {
            return [
                'ok' => false,
                'message' => 'Número inicial inválido para a sequência de títulos.',
            ];
        }

        $sequenceEnd = $sequenceStart + max(0, $recipientCount - 1);
        if (mb_strlen("$baseName #$sequenceEnd") > 25) {
            return [
                'ok' => false,
                'message' => 'O maior título da sequência excede o limite de 25 caracteres.',
            ];
        }

        return [
            'ok' => true,
            'payload' => [
                'group_name' => $baseName,
                'group_name_sequence' => true,
                'group_name_sequence_start' => $sequenceStart,
            ],
        ];
    }

    private function resolveRecipientActionPayload(string $actionType, array $payload, int $recipientIndex): array
    {
        if (
            $actionType !== GrupoConjuntoMensagem::ACTION_UPDATE_GROUP_NAME
            || ! $this->isTruthy($payload['group_name_sequence'] ?? false)
        ) {
            return $payload;
        }

        $baseName = trim((string) ($payload['group_name'] ?? ''));
        $sequenceStart = max(1, (int) ($payload['group_name_sequence_start'] ?? 1));

        return [
            'group_name' => $baseName.' #'.($sequenceStart + max(0, $recipientIndex)),
        ];
    }

    private function resolveGroupImageAction(array $payload): array
    {
        $value = trim((string) ($payload['group_image_url'] ?? ''));
        if (! $this->isHttpUrl($value)) {
            return [
                'ok' => false,
                'message' => 'URL de imagem do grupo inválida.',
            ];
        }

        return [
            'ok' => true,
            'payload' => ['group_image_url' => $value],
        ];
    }

    private function dispatchRecipientAction(string $token, string $jid, string $actionType, array $payload): array
    {
        $mentionAll = $this->isTruthy($payload['mention_all'] ?? false);

        return match ($actionType) {
            GrupoConjuntoMensagem::ACTION_SEND_MEDIA => $this->uazapiGruposService->sendMediaToGroup(
                $token,
                $jid,
                (string) ($payload['media_type'] ?? ''),
                (string) ($payload['media_url'] ?? ''),
                array_filter([
                    'text' => trim((string) ($payload['caption'] ?? '')),
                    'mentions' => $mentionAll ? 'all' : '',
                ], static fn ($value): bool => $value !== '')
            ),
            GrupoConjuntoMensagem::ACTION_UPDATE_GROUP_NAME => $this->dispatchUpdateGroupNameWithFallback(
                $token,
                $jid,
                (string) ($payload['group_name'] ?? '')
            ),
            GrupoConjuntoMensagem::ACTION_UPDATE_GROUP_DESCRIPTION => $this->uazapiGruposService->updateGroupDescription(
                $token,
                $jid,
                (string) ($payload['group_description'] ?? '')
            ),
            GrupoConjuntoMensagem::ACTION_UPDATE_GROUP_IMAGE => $this->uazapiGruposService->updateGroupImage(
                $token,
                $jid,
                (string) ($payload['group_image_url'] ?? '')
            ),
            default => $this->uazapiGruposService->sendTextToGroup(
                $token,
                $jid,
                (string) ($payload['text'] ?? ''),
                ...($mentionAll ? [['mentions' => 'all']] : [])
            ),
        };
    }

    private function dispatchUpdateGroupNameWithFallback(string $token, string $jid, string $targetName): array
    {
        $response = $this->uazapiGruposService->updateGroupName($token, $jid, $targetName);
        if (empty($response['error'])) {
            return $response;
        }

        $info = $this->uazapiGruposService->getGroupInfo($token, $jid);
        if (! empty($info['error'])) {
            return $response;
        }

        $currentName = $this->extractGroupNameFromInfo($info);
        if ($currentName === '' || trim($currentName) !== trim($targetName)) {
            return $response;
        }

        return [
            'status' => 200,
            'body' => [
                'message' => 'Group name already matches target value.',
                'fallback' => 'group_info_match',
            ],
        ];
    }

    private function extractGroupNameFromInfo(array $payload): string
    {
        $candidates = [
            Arr::get($payload, 'Name'),
            Arr::get($payload, 'name'),
            Arr::get($payload, 'subject'),
            Arr::get($payload, 'group.Name'),
            Arr::get($payload, 'group.name'),
            Arr::get($payload, 'body.Name'),
            Arr::get($payload, 'body.name'),
        ];

        foreach ($candidates as $value) {
            if (! is_string($value)) {
                continue;
            }

            $name = trim($value);
            if ($name !== '') {
                return $name;
            }
        }

        return '';
    }

    private function isHttpUrl(string $value): bool
    {
        if ($value === '') {
            return false;
        }

        if (! filter_var($value, FILTER_VALIDATE_URL)) {
            return false;
        }

        $scheme = (string) parse_url($value, PHP_URL_SCHEME);

        return in_array(strtolower($scheme), ['http', 'https'], true);
    }

    private function isTruthy(mixed $value): bool
    {
        if (is_bool($value)) {
            return $value;
        }

        if (is_int($value)) {
            return $value === 1;
        }

        if (is_string($value)) {
            return in_array(strtolower(trim($value)), ['1', 'true', 'on', 'yes'], true);
        }

        return false;
    }

    private function normalizeRecipients(array $recipients): array
    {
        $normalized = [];

        foreach ($recipients as $recipient) {
            if (! is_array($recipient)) {
                continue;
            }

            $jid = trim((string) ($recipient['jid'] ?? ''));
            if ($jid === '' || ! preg_match('/^[0-9]+@g\.us$/', $jid)) {
                continue;
            }

            $name = trim((string) ($recipient['name'] ?? ''));

            $normalized[$jid] = [
                'jid' => $jid,
                'name' => $name !== '' ? $name : $jid,
            ];
        }

        return array_values($normalized);
    }

    private function timingService(): GrupoConjuntoActionTimingService
    {
        if ($this->timingService instanceof GrupoConjuntoActionTimingService) {
            return $this->timingService;
        }

        $service = app(GrupoConjuntoActionTimingService::class);
        $this->timingService = $service;

        return $service;
    }
}
