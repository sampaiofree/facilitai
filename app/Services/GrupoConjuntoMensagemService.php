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

        if (!$conexao || $providerSlug !== 'uazapi' || $token === '') {
            $mensagem->update([
                'status' => 'failed',
                'failed_at' => $nowUtc,
                'sent_at' => null,
                'error_message' => 'Conexao invalida para envio de mensagens em grupo.',
                'attempts' => $attemptValue,
            ]);

            return $mensagem->fresh();
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

            return $mensagem->fresh();
        }

        $resolvedAction = $this->resolveActionData($mensagem);
        if (!$resolvedAction['ok']) {
            $mensagem->update([
                'status' => 'failed',
                'failed_at' => $nowUtc,
                'sent_at' => null,
                'error_message' => (string) ($resolvedAction['message'] ?? 'Ação inválida para envio em grupo.'),
                'attempts' => $attemptValue,
            ]);

            return $mensagem->fresh();
        }

        $actionType = (string) $resolvedAction['action_type'];
        $actionPayload = (array) $resolvedAction['payload'];

        $sentCount = 0;
        $failedCount = 0;
        $firstError = null;
        $resultRows = [];

        foreach ($recipients as $recipient) {
            $jid = (string) $recipient['jid'];
            $name = (string) $recipient['name'];

            $slotGranted = $this->timingService()->acquireDispatchSlot(
                (int) $mensagem->user_id,
                (int) $mensagem->conexao_id,
                $jid,
                $actionType
            );

            if (!$slotGranted) {
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
                $response = $this->dispatchRecipientAction($token, $jid, $actionType, $actionPayload);
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

        return $mensagem->fresh();
    }

    private function resolveActionData(GrupoConjuntoMensagem $mensagem): array
    {
        $actionType = $mensagem->resolveActionType();
        $payload = $mensagem->resolvePayload();

        return match ($actionType) {
            GrupoConjuntoMensagem::ACTION_SEND_MEDIA => $this->resolveSendMediaAction($payload),
            GrupoConjuntoMensagem::ACTION_UPDATE_GROUP_NAME => $this->resolveSingleFieldAction($payload, 'group_name', 25),
            GrupoConjuntoMensagem::ACTION_UPDATE_GROUP_DESCRIPTION => $this->resolveSingleFieldAction($payload, 'group_description', 512),
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

        if (!in_array($mediaType, ['image', 'video', 'document', 'audio'], true)) {
            return [
                'ok' => false,
                'message' => 'Tipo de mídia inválido para envio.',
            ];
        }

        if (!$this->isHttpUrl($mediaUrl)) {
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

    private function resolveGroupImageAction(array $payload): array
    {
        $value = trim((string) ($payload['group_image_url'] ?? ''));
        if (!$this->isHttpUrl($value)) {
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
        if (!empty($info['error'])) {
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
            if (!is_string($value)) {
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

        if (!filter_var($value, FILTER_VALIDATE_URL)) {
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
            if (!is_array($recipient)) {
                continue;
            }

            $jid = trim((string) ($recipient['jid'] ?? ''));
            if ($jid === '' || !preg_match('/^[0-9]+@g\.us$/', $jid)) {
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
