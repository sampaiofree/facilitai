<?php

namespace App\Services;

use App\Jobs\ProcessIncomingMessageJob;
use App\Jobs\SyncCloudTemplateContextJob;
use App\Models\ClienteLead;
use App\Models\Conexao;
use App\Models\LeadWebhookLink;
use App\Models\Tag;
use App\Models\WhatsappCloudTemplate;
use App\Models\WhatsappCloudCustomField;
use App\Support\PhoneNumberNormalizer;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class LeadWebhookProcessor
{
    public function __construct(
        protected LeadWebhookPayloadMapper $payloadMapper,
        protected LeadWebhookDeliveryService $deliveryService,
        protected PhoneNumberNormalizer $phoneNumberNormalizer,
        protected WhatsappCloudConversationWindowService $conversationWindowService,
        protected WhatsappCloudTemplateSendService $templateSendService
    ) {
    }

    public function process(LeadWebhookLink $link, array $payload): array
    {
        $delivery = $this->deliveryService->start($link, $payload);
        $duplicate = $this->deliveryService->findRecentDuplicate($delivery);

        if ($duplicate) {
            $delivery = $this->deliveryService->markDuplicate($delivery, $duplicate);

            return [
                'http_status' => 200,
                'ok' => true,
                'delivery_id' => $delivery->id,
                'status' => 'duplicate',
                'lead_id' => null,
                'message' => 'Payload duplicado recentemente.',
            ];
        }

        $config = is_array($link->config) ? $link->config : [];
        $phonePath = $this->payloadMapper->normalizePath((string) data_get($config, 'lead.phone_path', ''));

        if ($phonePath === null) {
            $delivery = $this->deliveryService->finalize(
                $delivery,
                'failed',
                ['message' => 'Mapeamento de telefone não configurado.'],
                'Mapeamento de telefone não configurado.'
            );

            return $this->buildErrorResponse($delivery, 422, 'Mapeamento de telefone não configurado.');
        }

        $rawPhone = $this->payloadMapper->resolveScalarString($payload, $phonePath);
        $normalizedPhone = $this->phoneNumberNormalizer->normalizeLeadPhone($rawPhone);

        if ($normalizedPhone === null) {
            $delivery = $this->deliveryService->finalize(
                $delivery,
                'failed',
                ['message' => 'Telefone ausente ou inválido no payload.'],
                'Telefone ausente ou inválido no payload.'
            );

            return $this->buildErrorResponse($delivery, 422, 'Telefone ausente ou inválido no payload.');
        }

        $namePath = $this->payloadMapper->normalizePath((string) data_get($config, 'lead.name_path', ''));
        $resolvedName = $this->payloadMapper->resolveScalarString($payload, $namePath);

        $issues = [];
        $tagNames = [];
        $savedFields = [];
        $promptStatus = null;
        $leadWasCreated = false;

        try {
            [$lead, $leadWasCreated] = DB::transaction(function () use (
                $link,
                $normalizedPhone,
                $resolvedName,
                $config,
                $payload,
                &$issues,
                &$tagNames,
                &$savedFields
            ) {
                [$lead, $created] = $this->upsertLead($link, $normalizedPhone, $resolvedName);

                $tagIds = collect((array) data_get($config, 'actions', []))
                    ->where('type', 'tag')
                    ->pluck('tag_id')
                    ->filter(fn ($value) => is_int($value) || ctype_digit((string) $value))
                    ->map(fn ($value) => (int) $value)
                    ->unique()
                    ->values()
                    ->all();

                if ($tagIds !== []) {
                    $tags = $this->resolveScopedTags($link, $tagIds)->keyBy('id');
                    $missingTagIds = array_values(array_diff($tagIds, $tags->keys()->all()));

                    if ($missingTagIds !== []) {
                        $issues[] = 'Algumas tags configuradas não estão mais disponíveis.';
                    }

                    if ($tags->isNotEmpty()) {
                        $lead->tags()->syncWithoutDetaching($tags->keys()->all());
                        $tagNames = $tags->pluck('name')->values()->all();
                    }
                }

                $fieldActions = collect((array) data_get($config, 'actions', []))
                    ->where('type', 'custom_field')
                    ->values();

                if ($fieldActions->isNotEmpty()) {
                    $fieldIds = $fieldActions
                        ->pluck('field_id')
                        ->filter(fn ($value) => is_int($value) || ctype_digit((string) $value))
                        ->map(fn ($value) => (int) $value)
                        ->unique()
                        ->values()
                        ->all();

                    $fields = $this->resolveScopedCustomFields($link, $fieldIds)->keyBy('id');
                    $missingFieldIds = array_values(array_diff($fieldIds, $fields->keys()->all()));

                    if ($missingFieldIds !== []) {
                        $issues[] = 'Alguns campos personalizados configurados não estão mais disponíveis.';
                    }

                    foreach ($fieldActions as $action) {
                        $fieldId = (int) ($action['field_id'] ?? 0);
                        $field = $fields->get($fieldId);
                        if (!$field) {
                            continue;
                        }

                        $value = $this->payloadMapper->resolveScalarString($payload, $action['source_path'] ?? null);
                        if ($value === null) {
                            continue;
                        }

                        $lead->customFieldValues()->updateOrCreate(
                            [
                                'cliente_lead_id' => $lead->id,
                                'whatsapp_cloud_custom_field_id' => $field->id,
                            ],
                            [
                                'value' => $value,
                            ]
                        );

                        $savedFields[] = $field->name;
                    }
                }

                return [$lead->fresh(['cliente', 'tags', 'customFieldValues']), $created];
            });
        } catch (\Throwable $exception) {
            $delivery = $this->deliveryService->finalize(
                $delivery,
                'failed',
                ['message' => 'Falha ao processar o payload.'],
                $exception->getMessage()
            );

            throw $exception;
        }

        $promptAction = collect((array) data_get($config, 'actions', []))
            ->first(fn (mixed $action) => is_array($action) && ($action['type'] ?? null) === 'prompt');

        if (is_array($promptAction)) {
            $promptResult = $this->dispatchPromptAction($link, $lead, $promptAction, $payload);
            $promptStatus = $promptResult['message'];

            if (($promptResult['status'] ?? 'partial') !== 'processed') {
                $issues[] = $promptResult['message'];
            }
        }

        $status = $issues === [] ? 'processed' : 'partial';
        $result = [
            'lead' => [
                'id' => $lead->id,
                'action' => $leadWasCreated ? 'created' : 'updated',
            ],
            'tags' => $tagNames,
            'custom_fields' => array_values(array_unique($savedFields)),
            'prompt' => $promptStatus,
            'issues' => array_values(array_unique($issues)),
        ];

        $delivery = $this->deliveryService->finalize(
            $delivery,
            $status,
            $result,
            $issues === [] ? null : implode(' ', array_values(array_unique($issues))),
            $lead->id,
            $normalizedPhone
        );

        return [
            'http_status' => 200,
            'ok' => true,
            'delivery_id' => $delivery->id,
            'status' => $delivery->status,
            'lead_id' => $lead->id,
            'message' => $leadWasCreated ? 'Lead criado com sucesso.' : 'Lead atualizado com sucesso.',
        ];
    }

    /**
     * @return array{0: ClienteLead, 1: bool}
     */
    private function upsertLead(LeadWebhookLink $link, string $normalizedPhone, ?string $resolvedName): array
    {
        $phoneCandidates = $this->phoneNumberNormalizer->buildLeadPhoneLookupCandidates($normalizedPhone);

        $lead = ClienteLead::query()
            ->where('cliente_id', $link->cliente_id)
            ->whereIn('phone', $phoneCandidates)
            ->orderByRaw('phone = ? desc', [$normalizedPhone])
            ->first();

        if ($lead) {
            $payload = [];
            if ($resolvedName !== null) {
                $payload['name'] = $resolvedName;
            }

            if ($payload !== []) {
                $lead->update($payload);
            }

            return [$lead->fresh(), false];
        }

        try {
            $lead = ClienteLead::create([
                'cliente_id' => $link->cliente_id,
                'bot_enabled' => true,
                'phone' => $normalizedPhone,
                'name' => $resolvedName,
                'info' => null,
            ]);

            return [$lead, true];
        } catch (UniqueConstraintViolationException) {
            $lead = ClienteLead::query()
                ->where('cliente_id', $link->cliente_id)
                ->whereIn('phone', $phoneCandidates)
                ->orderByRaw('phone = ? desc', [$normalizedPhone])
                ->firstOrFail();

            if ($resolvedName !== null) {
                $lead->update([
                    'name' => $resolvedName,
                ]);
            }

            return [$lead->fresh(), false];
        }
    }

    private function resolveScopedTags(LeadWebhookLink $link, array $tagIds)
    {
        return Tag::query()
            ->where('user_id', $link->user_id)
            ->whereIn('id', $tagIds)
            ->where(function ($query) use ($link) {
                $query->whereNull('cliente_id')
                    ->orWhere('cliente_id', $link->cliente_id);
            })
            ->get(['id', 'name']);
    }

    private function resolveScopedCustomFields(LeadWebhookLink $link, array $fieldIds)
    {
        return WhatsappCloudCustomField::query()
            ->where('user_id', $link->user_id)
            ->whereIn('id', $fieldIds)
            ->where(function ($query) use ($link) {
                $query->whereNull('cliente_id')
                    ->orWhere('cliente_id', $link->cliente_id);
            })
            ->get(['id', 'name', 'label']);
    }

    private function dispatchPromptAction(LeadWebhookLink $link, ClienteLead $lead, array $promptAction, array $payload): array
    {
        /** @var Conexao|null $conexao */
        $conexao = $link->conexao()
            ->with(['assistant', 'whatsappApi', 'whatsappCloudAccount'])
            ->where('cliente_id', $link->cliente_id)
            ->where('is_active', true)
            ->whereNull('deleted_at')
            ->first();

        if (!$conexao) {
            return [
                'status' => 'partial',
                'message' => 'Ação de enviar para assistente não executada: conexão fixa não está disponível.',
            ];
        }

        $providerSlug = Str::lower((string) ($conexao->whatsappApi?->slug ?? ''));
        if ($providerSlug === 'whatsapp_cloud') {
            return $this->dispatchCloudTemplateAction($link, $conexao, $lead, $promptAction, $payload);
        }

        $renderedPrompt = trim($this->payloadMapper->renderTemplate((string) ($promptAction['template'] ?? ''), $payload));
        if ($renderedPrompt === '') {
            return [
                'status' => 'processed',
                'message' => 'Texto ignorado porque ficou vazio após renderização.',
            ];
        }

        return $this->dispatchPrompt($conexao, $lead, $renderedPrompt);
    }

    private function dispatchPrompt(Conexao $conexao, ClienteLead $lead, string $prompt): array
    {
        if (!$conexao->assistant) {
            return [
                'status' => 'partial',
                'message' => 'Texto não enviado: conexão sem assistente vinculado.',
            ];
        }

        $providerSlug = Str::lower((string) ($conexao->whatsappApi?->slug ?? ''));
        if ($providerSlug === 'whatsapp_cloud' && !$this->conversationWindowService->isInsideWindow((int) $lead->id, (int) $conexao->id)) {
            return [
                'status' => 'partial',
                'message' => 'Texto não enviado: conversa fora da janela de 24h do WhatsApp Cloud.',
            ];
        }

        $agoraUtc = Carbon::now('UTC');
        $eventId = sprintf(
            'webhook:lead:%d:conexao:%d:ts:%d',
            $lead->id,
            $conexao->id,
            $agoraUtc->valueOf()
        );

        $jobPayload = [
            'phone' => $lead->phone,
            'text' => $prompt,
            'tipo' => 'text',
            'from_me' => false,
            'is_group' => false,
            'lead_name' => $lead->name ?? $lead->phone,
            'openai_role' => 'system',
            'event_id' => $eventId,
            'message_timestamp' => $agoraUtc->valueOf(),
            'message_type' => 'conversation',
        ];

        ProcessIncomingMessageJob::dispatch($conexao->id, $lead->id, $jobPayload)
            ->onQueue('processarconversa');

        return [
            'status' => 'processed',
            'message' => 'Texto enviado para a fila do assistente.',
        ];
    }

    private function dispatchCloudTemplateAction(
        LeadWebhookLink $link,
        Conexao $conexao,
        ClienteLead $lead,
        array $promptAction,
        array $payload
    ): array {
        $templateId = (int) ($promptAction['whatsapp_cloud_template_id'] ?? 0);
        if ($templateId <= 0) {
            return [
                'status' => 'partial',
                'message' => 'Modelo não enviado: nenhum modelo Cloud foi configurado.',
            ];
        }

        /** @var WhatsappCloudTemplate|null $template */
        $template = WhatsappCloudTemplate::query()
            ->where('user_id', $link->user_id)
            ->find($templateId);

        if (!$template) {
            return [
                'status' => 'partial',
                'message' => 'Modelo não enviado: o modelo Cloud configurado não está mais disponível.',
            ];
        }

        [$resolvedVariables] = $this->templateSendService->resolveBoundTemplateVariablesForLead(
            $template,
            $lead,
            (int) $link->user_id,
            (array) ($promptAction['template_variable_bindings'] ?? []),
            true,
            ' '
        );

        $sendResult = $this->templateSendService->sendToLead([
            'user_id' => (int) $link->user_id,
            'conexao' => $conexao,
            'template' => $template,
            'lead' => $lead,
            'template_variables' => $resolvedVariables,
            'allow_empty_variables' => true,
            'empty_variable_fallback' => ' ',
        ]);

        if (!($sendResult['ok'] ?? false)) {
            return [
                'status' => 'partial',
                'message' => 'Modelo não enviado: ' . trim((string) ($sendResult['message'] ?? 'Falha ao enviar modelo Cloud.')),
            ];
        }

        $nowUtc = Carbon::now('UTC');
        $this->conversationWindowService->touchOutbound((int) $lead->id, (int) $conexao->id, $nowUtc);

        if (!$conexao->assistant) {
            return [
                'status' => 'partial',
                'message' => 'Modelo enviado para o WhatsApp Cloud, mas a conexão não possui assistente vinculado para sincronizar contexto.',
            ];
        }

        $assistantContextInstructions = trim($this->payloadMapper->renderTemplate(
            (string) ($promptAction['assistant_context_instructions'] ?? ''),
            $payload
        ));
        $response = is_array($sendResult['response'] ?? null)
            ? $sendResult['response']
            : [];
        $metaMessageId = trim((string) data_get($response, 'body.messages.0.id', ''));

        try {
            SyncCloudTemplateContextJob::dispatch([
                'conexao_id' => (int) $conexao->id,
                'cliente_lead_id' => (int) $lead->id,
                'template_id' => (int) $template->id,
                'template_variables' => is_array($sendResult['resolved_variables'] ?? null)
                    ? $sendResult['resolved_variables']
                    : $resolvedVariables,
                'assistant_context_instructions' => $assistantContextInstructions !== '' ? $assistantContextInstructions : null,
                'meta_message_id' => $metaMessageId !== '' ? $metaMessageId : null,
                'sent_at' => $nowUtc->toIso8601String(),
            ])->onQueue('processarconversa');
        } catch (\Throwable) {
            return [
                'status' => 'partial',
                'message' => 'Modelo enviado para o WhatsApp Cloud, mas a sincronização de contexto do assistente não foi enfileirada.',
            ];
        }

        return [
            'status' => 'processed',
            'message' => 'Modelo enviado para o WhatsApp Cloud e sincronização de contexto enfileirada.',
        ];
    }

    private function buildErrorResponse($delivery, int $statusCode, string $message): array
    {
        return [
            'http_status' => $statusCode,
            'ok' => false,
            'delivery_id' => $delivery->id,
            'status' => $delivery->status,
            'lead_id' => null,
            'message' => $message,
        ];
    }
}
