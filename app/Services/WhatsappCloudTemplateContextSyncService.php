<?php

namespace App\Services;

use App\Models\Assistant;
use App\Models\AssistantLead;
use App\Models\ClienteLead;
use App\Models\Conexao;
use App\Models\WhatsappCloudTemplate;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class WhatsappCloudTemplateContextSyncService
{
    private const IDEMPOTENCY_TTL_DAYS = 30;
    private const LOCK_TTL_MINUTES = 5;

    /**
     * Sincroniza no contexto OpenAI a mensagem de template já enviada ao WhatsApp.
     * Esta rotina nunca chama createResponse; ela apenas adiciona item na conversation.
     *
     * @param array{
     *   conexao_id:int,
     *   cliente_lead_id:int,
     *   template_id:int,
     *   template_variables?:array<string,mixed>,
     *   assistant_context_instructions?:string|null,
     *   meta_message_id?:string|null,
     *   sent_at?:string|null
     * } $payload
     */
    public function sync(array $payload): void
    {
        $conexaoId = $this->toInt($payload['conexao_id'] ?? null);
        $leadId = $this->toInt($payload['cliente_lead_id'] ?? null);
        $templateId = $this->toInt($payload['template_id'] ?? null);

        if ($conexaoId <= 0 || $leadId <= 0 || $templateId <= 0) {
            Log::channel('ia_orchestrator')->warning('Template context sync ignorado: payload inválido.', [
                'payload' => $payload,
            ]);
            return;
        }

        $conexao = Conexao::query()
            ->with(['assistant', 'credential.iaplataforma', 'cliente'])
            ->find($conexaoId);
        if (!$conexao) {
            Log::channel('ia_orchestrator')->warning('Template context sync ignorado: conexão não encontrada.', [
                'conexao_id' => $conexaoId,
                'lead_id' => $leadId,
                'template_id' => $templateId,
            ]);
            return;
        }

        $assistant = $conexao->assistant;
        if (!$assistant) {
            Log::channel('ia_orchestrator')->info('Template context sync ignorado: conexão sem assistente.', [
                'conexao_id' => $conexaoId,
                'lead_id' => $leadId,
                'template_id' => $templateId,
            ]);
            return;
        }

        // O conv_id atual pertence ao fluxo OpenAI; se no futuro houver outro provider,
        // pulamos o sync para não gravar contexto em provedor incorreto.
        $provider = Str::lower(trim((string) ($conexao->credential?->iaplataforma?->nome ?? 'openai')));
        if ($provider !== '' && $provider !== 'openai') {
            Log::channel('ia_orchestrator')->info('Template context sync ignorado: provider diferente de OpenAI.', [
                'conexao_id' => $conexaoId,
                'lead_id' => $leadId,
                'template_id' => $templateId,
                'provider' => $provider,
            ]);
            return;
        }

        $lead = ClienteLead::query()->with('cliente')->find($leadId);
        if (!$lead) {
            Log::channel('ia_orchestrator')->warning('Template context sync ignorado: lead não encontrado.', [
                'conexao_id' => $conexaoId,
                'lead_id' => $leadId,
                'template_id' => $templateId,
            ]);
            return;
        }

        if ((int) $lead->cliente_id !== (int) $conexao->cliente_id) {
            Log::channel('ia_orchestrator')->warning('Template context sync ignorado: lead/conexão de clientes diferentes.', [
                'conexao_id' => $conexaoId,
                'lead_id' => $leadId,
                'template_id' => $templateId,
                'lead_cliente_id' => (int) $lead->cliente_id,
                'conexao_cliente_id' => (int) $conexao->cliente_id,
            ]);
            return;
        }

        $template = WhatsappCloudTemplate::query()->find($templateId);
        if (!$template) {
            Log::channel('ia_orchestrator')->warning('Template context sync ignorado: template não encontrado.', [
                'conexao_id' => $conexaoId,
                'lead_id' => $leadId,
                'template_id' => $templateId,
            ]);
            return;
        }

        $templateVariables = $this->normalizeTemplateVariables((array) ($payload['template_variables'] ?? []));
        $idempotencyDigest = $this->buildIdempotencyDigest($payload, $templateVariables);
        $doneKey = "wa_cloud_template_ctx_done:{$idempotencyDigest}";
        $lockKey = "wa_cloud_template_ctx_lock:{$idempotencyDigest}";

        if (Cache::get($doneKey)) {
            Log::channel('ia_orchestrator')->info('Template context sync ignorado: evento já sincronizado.', [
                'conexao_id' => $conexaoId,
                'lead_id' => $leadId,
                'template_id' => $templateId,
                'idempotency_key' => $idempotencyDigest,
            ]);
            return;
        }

        if (!Cache::add($lockKey, true, now()->addMinutes(self::LOCK_TTL_MINUTES))) {
            Log::channel('ia_orchestrator')->info('Template context sync em processamento concorrente.', [
                'conexao_id' => $conexaoId,
                'lead_id' => $leadId,
                'template_id' => $templateId,
                'idempotency_key' => $idempotencyDigest,
            ]);
            return;
        }

        try {
            if (Cache::get($doneKey)) {
                return;
            }

            $openAi = $this->resolveOpenAiService($conexao);
            if (!$openAi) {
                return;
            }

            $assistantLead = $this->ensureAssistantLead($lead, $assistant);
            if (!$assistantLead) {
                return;
            }

            $assistantLead = $this->ensureConversation($assistantLead, $assistant, $openAi);
            if (!$assistantLead || trim((string) ($assistantLead->conv_id ?? '')) === '') {
                Log::channel('ia_orchestrator')->warning('Template context sync falhou: conv_id indisponível.', [
                    'assistant_lead_id' => $assistantLead?->id,
                    'conexao_id' => $conexaoId,
                    'lead_id' => $leadId,
                    'template_id' => $templateId,
                ]);
                return;
            }

            $contextText = $this->buildContextText($template, $lead, $payload, $templateVariables);
            $sent = $this->appendTemplateMessageToConversation($openAi, $assistantLead, $contextText);
            if (!$sent) {
                return;
            }

            Cache::put($doneKey, true, now()->addDays(self::IDEMPOTENCY_TTL_DAYS));
        } finally {
            Cache::forget($lockKey);
        }
    }

    private function resolveOpenAiService(Conexao $conexao): ?OpenAIService
    {
        $token = trim((string) ($conexao->credential?->token ?? ''));
        if ($token === '' || $token === '******') {
            Log::channel('ia_orchestrator')->warning('Template context sync ignorado: token OpenAI ausente.', [
                'conexao_id' => $conexao->id,
            ]);
            return null;
        }

        try {
            return new OpenAIService($token);
        } catch (\Throwable $exception) {
            Log::channel('ia_orchestrator')->error('Template context sync falhou ao criar OpenAIService.', [
                'conexao_id' => $conexao->id,
                'error' => $exception->getMessage(),
            ]);
            return null;
        }
    }

    private function ensureAssistantLead(ClienteLead $lead, Assistant $assistant): ?AssistantLead
    {
        try {
            return AssistantLead::query()->firstOrCreate(
                [
                    'lead_id' => (int) $lead->id,
                    'assistant_id' => (int) $assistant->id,
                ],
                [
                    'version' => max(1, (int) ($assistant->version ?? 1)),
                    'conv_id' => null,
                ]
            );
        } catch (\Throwable $exception) {
            Log::channel('ia_orchestrator')->error('Template context sync falhou ao resolver AssistantLead.', [
                'lead_id' => (int) $lead->id,
                'assistant_id' => (int) $assistant->id,
                'error' => $exception->getMessage(),
            ]);
            return null;
        }
    }

    private function ensureConversation(AssistantLead $assistantLead, Assistant $assistant, OpenAIService $openAi): ?AssistantLead
    {
        $currentConvId = trim((string) ($assistantLead->conv_id ?? ''));
        if ($currentConvId !== '') {
            return $assistantLead;
        }

        $systemPrompt = $this->buildSystemPrompt($assistant);
        $createPayload = [
            'items' => [
                [
                    'type' => 'message',
                    'role' => 'system',
                    'content' => $systemPrompt,
                ],
            ],
        ];

        $response = $openAi->createConversation($createPayload, $this->openAiRequestOptions());
        if (!$response) {
            throw new \RuntimeException('OpenAI createConversation sem resposta.');
        }

        if ($response->failed()) {
            if ($this->isRetryableStatus($response)) {
                throw new \RuntimeException('OpenAI createConversation temporariamente indisponível.');
            }

            Log::channel('ia_orchestrator')->warning('Template context sync: createConversation retornou erro não recuperável.', [
                'assistant_lead_id' => (int) $assistantLead->id,
                'status' => $response->status(),
                'body' => $response->json(),
            ]);
            return null;
        }

        $newConvId = trim((string) ($response->json('id') ?? ''));
        if ($newConvId === '') {
            Log::channel('ia_orchestrator')->warning('Template context sync: createConversation sem id.', [
                'assistant_lead_id' => (int) $assistantLead->id,
                'body' => $response->json(),
            ]);
            return null;
        }

        $assistantLead->conv_id = $newConvId;
        $assistantLead->version = max(1, (int) ($assistant->version ?? $assistantLead->version ?? 1));
        $assistantLead->save();

        return $assistantLead;
    }

    private function appendTemplateMessageToConversation(OpenAIService $openAi, AssistantLead $assistantLead, string $contextText): bool
    {
        $convId = trim((string) ($assistantLead->conv_id ?? ''));
        if ($convId === '') {
            return false;
        }

        $response = $openAi->createItems(
            $convId,
            [
                'items' => [
                    [
                        'type' => 'message',
                        'role' => 'system',
                        'content' => [
                            [
                                'type' => 'input_text',
                                'text' => $contextText,
                            ],
                        ],
                    ],
                ],
            ],
            $this->openAiRequestOptions()
        );

        if (!$response) {
            throw new \RuntimeException('OpenAI createItems sem resposta.');
        }

        if ($response->successful()) {
            return true;
        }

        if ($this->isConversationNotFoundResponse($response, $convId)) {
            return $this->resetConversationAndRetryAppend($openAi, $assistantLead, $contextText);
        }

        if ($this->isRetryableStatus($response)) {
            throw new \RuntimeException('OpenAI createItems temporariamente indisponível.');
        }

        Log::channel('ia_orchestrator')->warning('Template context sync: createItems retornou erro não recuperável.', [
            'assistant_lead_id' => (int) $assistantLead->id,
            'conversation_id' => $convId,
            'status' => $response->status(),
            'body' => $response->json(),
        ]);

        return false;
    }

    private function resetConversationAndRetryAppend(OpenAIService $openAi, AssistantLead $assistantLead, string $contextText): bool
    {
        $assistant = Assistant::query()->find((int) $assistantLead->assistant_id);
        if (!$assistant) {
            Log::channel('ia_orchestrator')->warning('Template context sync: assistente não encontrado para reset de conv_id.', [
                'assistant_lead_id' => (int) $assistantLead->id,
            ]);
            return false;
        }

        $assistantLead->conv_id = null;
        $assistantLead->save();

        $assistantLead = $this->ensureConversation($assistantLead, $assistant, $openAi);
        if (!$assistantLead || trim((string) ($assistantLead->conv_id ?? '')) === '') {
            return false;
        }

        $retry = $openAi->createItems(
            (string) $assistantLead->conv_id,
            [
                'items' => [
                    [
                        'type' => 'message',
                        'role' => 'system',
                        'content' => [
                            [
                                'type' => 'input_text',
                                'text' => $contextText,
                            ],
                        ],
                    ],
                ],
            ],
            $this->openAiRequestOptions()
        );

        if (!$retry) {
            throw new \RuntimeException('OpenAI createItems retry sem resposta.');
        }

        if ($retry->successful()) {
            return true;
        }

        if ($this->isRetryableStatus($retry)) {
            throw new \RuntimeException('OpenAI createItems retry temporariamente indisponível.');
        }

        Log::channel('ia_orchestrator')->warning('Template context sync: retry createItems falhou.', [
            'assistant_lead_id' => (int) $assistantLead->id,
            'conversation_id' => (string) $assistantLead->conv_id,
            'status' => $retry->status(),
            'body' => $retry->json(),
        ]);

        return false;
    }

    private function buildContextText(
        WhatsappCloudTemplate $template,
        ClienteLead $lead,
        array $payload,
        array $templateVariables
    ): string {
        $renderedBody = $this->renderTextWithVariables((string) ($template->body_text ?? ''), $templateVariables);
        $renderedFooter = $this->renderTextWithVariables((string) ($template->footer_text ?? ''), $templateVariables);

        $buttonLines = [];
        foreach ((array) ($template->buttons ?? []) as $button) {
            if (!is_array($button)) {
                continue;
            }

            $type = Str::upper(trim((string) ($button['type'] ?? '')));
            $text = $this->renderTextWithVariables((string) ($button['text'] ?? ''), $templateVariables);
            $url = $this->renderTextWithVariables((string) ($button['url'] ?? ''), $templateVariables);

            if ($type === 'URL') {
                $buttonLines[] = trim("- URL: {$text}" . ($url !== '' ? " -> {$url}" : ''));
                continue;
            }

            if ($text !== '') {
                $buttonLines[] = trim("- RESPOSTA_RAPIDA: {$text}");
            }
        }

        $metaMessageId = trim((string) ($payload['meta_message_id'] ?? ''));
        $sentAt = $this->parseSentAtToAppTimezone($payload['sent_at'] ?? null);
        $assistantContextInstructions = $this->normalizeAssistantContextInstructions(
            $payload['assistant_context_instructions'] ?? null
        );

        $parts = [
            '[REGISTRO INTERNO] Mensagem de template enviada ao lead via WhatsApp Cloud.',
            'Lead ID: ' . (int) $lead->id,
            'Telefone: ' . (string) ($lead->phone ?? '-'),
            'Template: ' . trim((string) (($template->title ?: $template->template_name) ?? '-')),
            'Template interno: ' . trim((string) ($template->template_name ?? '-')),
            'Idioma: ' . trim((string) ($template->language_code ?? '-')),
            'Categoria: ' . trim((string) ($template->category ?? '-')),
        ];

        if ($metaMessageId !== '') {
            $parts[] = 'Meta message id: ' . $metaMessageId;
        }

        if ($sentAt !== null) {
            $parts[] = 'Enviado em: ' . $sentAt->format('d/m/Y H:i:s') . ' (' . config('app.timezone', 'America/Sao_Paulo') . ')';
        }

        if ($assistantContextInstructions !== null) {
            $parts[] = '';
            $parts[] = 'Instruções para o assistente (persistidas neste contexto):';
            $parts[] = $assistantContextInstructions;
        }

        $parts[] = '';
        $parts[] = 'Conteúdo enviado:';
        $parts[] = $renderedBody !== '' ? $renderedBody : '[sem corpo]';

        if ($renderedFooter !== '') {
            $parts[] = '';
            $parts[] = 'Rodapé:';
            $parts[] = $renderedFooter;
        }

        if (!empty($buttonLines)) {
            $parts[] = '';
            $parts[] = 'Botões:';
            $parts[] = implode("\n", $buttonLines);
        }

        return trim(implode("\n", $parts));
    }

    private function renderTextWithVariables(string $text, array $variables): string
    {
        if ($text === '') {
            return '';
        }

        $rendered = preg_replace_callback('/\{([a-z0-9_]+)\}/', function (array $matches) use ($variables): string {
            $key = Str::lower(trim((string) ($matches[1] ?? '')));
            $replacement = trim((string) ($variables[$key] ?? ''));

            return $replacement !== '' ? $replacement : $matches[0];
        }, $text);

        return is_string($rendered) ? $rendered : $text;
    }

    private function buildSystemPrompt(Assistant $assistant): string
    {
        $parts = [
            $assistant->systemPrompt ?? null,
            $assistant->instructions ?? null,
            $assistant->prompt_notificar_adm ?? null,
            $assistant->prompt_buscar_get ?? null,
            $assistant->prompt_enviar_media ?? null,
            $assistant->prompt_registrar_info_chat ?? null,
            $assistant->prompt_gerenciar_agenda ?? null,
            $assistant->prompt_aplicar_tags ?? null,
            $assistant->prompt_sequencia ?? null,
        ];

        $parts = array_filter($parts, fn ($value) => is_string($value) && trim($value) !== '');
        $prompt = trim(implode("\n", $parts));

        return $prompt !== '' ? $prompt : 'Contexto inicial do assistente.';
    }

    private function openAiRequestOptions(): array
    {
        return [
            'timeout' => 120,
            'max_retries' => 2,
            'base_delay_ms' => 1000,
            'max_delay_ms' => 8000,
        ];
    }

    private function isRetryableStatus(Response $response): bool
    {
        $status = $response->status();
        return $status === 408 || $status === 429 || $status >= 500;
    }

    private function isConversationNotFoundResponse(Response $response, ?string $expectedConversationId = null): bool
    {
        if (!in_array($response->status(), [400, 404], true)) {
            return false;
        }

        $message = Str::lower(trim((string) ($response->json('error.message') ?? $response->body())));
        if ($message === '') {
            return false;
        }

        $hasConversationMarker = str_contains($message, 'conversation');
        $hasNotFoundMarker = str_contains($message, 'not found')
            || str_contains($message, 'does not exist')
            || str_contains($message, 'unknown conversation');

        if (!$hasConversationMarker || !$hasNotFoundMarker) {
            return false;
        }

        if (!is_string($expectedConversationId) || trim($expectedConversationId) === '') {
            return true;
        }

        return str_contains($message, Str::lower(trim($expectedConversationId)));
    }

    private function buildIdempotencyDigest(array $payload, array $templateVariables): string
    {
        $metaMessageId = trim((string) ($payload['meta_message_id'] ?? ''));
        if ($metaMessageId !== '') {
            return hash('sha256', 'meta:' . $metaMessageId);
        }

        $fingerprintData = [
            'conexao_id' => $this->toInt($payload['conexao_id'] ?? null),
            'cliente_lead_id' => $this->toInt($payload['cliente_lead_id'] ?? null),
            'template_id' => $this->toInt($payload['template_id'] ?? null),
            'sent_at' => trim((string) ($payload['sent_at'] ?? '')),
            'template_variables' => $templateVariables,
            'assistant_context_instructions' => $this->normalizeAssistantContextInstructions(
                $payload['assistant_context_instructions'] ?? null
            ),
        ];

        return hash('sha256', (string) json_encode($fingerprintData, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
    }

    private function normalizeTemplateVariables(array $templateVariables): array
    {
        $normalized = [];

        foreach ($templateVariables as $key => $value) {
            $name = Str::lower(trim((string) $key));
            if ($name === '') {
                continue;
            }

            if (is_array($value)) {
                $value = implode(', ', array_map(fn ($item) => trim((string) $item), $value));
            }

            $normalized[$name] = trim((string) $value);
        }

        return $normalized;
    }

    private function parseSentAtToAppTimezone(mixed $value): ?Carbon
    {
        if (!is_string($value) || trim($value) === '') {
            return null;
        }

        try {
            return Carbon::parse($value, 'UTC')->setTimezone(config('app.timezone', 'America/Sao_Paulo'));
        } catch (\Throwable) {
            return null;
        }
    }

    private function toInt(mixed $value): int
    {
        return is_numeric($value) ? (int) $value : 0;
    }

    private function normalizeAssistantContextInstructions(mixed $value): ?string
    {
        if (!is_string($value)) {
            return null;
        }

        $normalized = trim(str_replace(["\r\n", "\r"], "\n", $value));
        return $normalized !== '' ? $normalized : null;
    }
}
