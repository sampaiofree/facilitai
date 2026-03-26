<?php

namespace App\Services;

use App\DTOs\IAResult;
use App\Models\Assistant;
use App\Models\AssistantLead;
use App\Models\ClienteLead;
use App\Models\Conexao;
use App\Models\SystemErrorLog;
use App\Models\WhatsappCloudCustomField;
use App\Support\LogContext;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class OpenAIOrchestratorService
{
    protected int $maxIterations;
    private ?array $conversationResolutionFailure = null;

    public function __construct(int $maxIterations = 5)
    {
        $this->maxIterations = $maxIterations;
    }

    public function handle(Conexao $conexao, Assistant $assistant, ClienteLead $lead, AssistantLead $assistantLead, array $payload, array $handlers = []): IAResult
    {
        $this->conversationResolutionFailure = null;

        $token = $conexao->credential?->token;
        if (!$token || $token === '******') {
            Log::channel('ia_orchestrator')->error('OpenAI token não configurado.', $this->logContext($payload, $conexao));
            return IAResult::error('OpenAI token não configurado.', 'openai');
        }

        $openAiService = new OpenAIService($token);
        $systemPrompt = (string) ($payload['system_prompt'] ?? '');
        $logContext = LogContext::base($payload, $conexao);

        $assistantLead = $this->ensureConversation($assistantLead, $assistant, $openAiService, $systemPrompt, $logContext);
        if (!$assistantLead || empty($assistantLead->conv_id)) {
            $failure = $this->conversationResolutionFailure ?? [
                'stage' => 'ensure_conversation',
                'reason' => 'empty_after_resolution',
            ];

            Log::channel('ia_orchestrator')->warning(
                'Conversation id ausente apos resolucao.',
                $this->logContext($payload, $conexao, array_merge($logContext, [
                    'assistant_lead_id' => $assistantLead?->id,
                    'conversation_resolution_failure' => $failure,
                ]))
            );

            return IAResult::error(
                $this->buildConversationResolutionErrorMessage($failure),
                'openai',
                [
                    'conversation_resolution_failure' => $failure,
                ]
            );
        }

        $payload['conversation_id'] = $assistantLead->conv_id;

        $input = $this->buildOpenAIInput($payload, $openAiService, $logContext, $conexao);
        if (empty($input)) {
            Log::channel('ia_orchestrator')->warning('OpenAI input vazio.', $this->logContext($payload, $conexao, $logContext));
            return IAResult::error('OpenAI input vazio.', 'openai');
        }

        $input = $this->prependSystemContext($input, $lead);

        $model = (string) ($payload['assistant_model'] ?? 'gpt-4.1-mini');
        $requestPayload = [
            'model' => $model,
            'input' => $input,
            'conversation' => $assistantLead->conv_id,
            'truncation'=> 'auto'
        ];

        $tools = ToolsFactory::fromSystemPrompt($systemPrompt, [
            'lead_custom_fields' => $this->resolveLeadCustomFieldsForTools($lead),
        ]);
        if (!empty($tools)) {
            $requestPayload['tools'] = $tools;
        }

        $response = $openAiService->createResponse($requestPayload, $this->openAiRequestOptions($logContext));
        if (!$response) {
            Log::channel('ia_orchestrator')->warning('OpenAIService createResponse exception', $this->logContext($payload, $conexao, $logContext));
            return IAResult::error('OpenAI exception.', 'openai');
        }

        if ($response->failed() && $this->isConversationNotFoundResponse($response, $assistantLead->conv_id)) {
            $recoveredAssistantLead = $this->resetConversationAfterNotFound(
                $assistantLead,
                $assistant,
                $openAiService,
                $systemPrompt,
                $logContext,
                $response
            );

            if ($recoveredAssistantLead && !empty($recoveredAssistantLead->conv_id)) {
                $assistantLead = $recoveredAssistantLead;
                $payload['conversation_id'] = $assistantLead->conv_id;
                $requestPayload['conversation'] = $assistantLead->conv_id;

                $response = $openAiService->createResponse($requestPayload, $this->openAiRequestOptions($logContext));
                if (!$response) {
                    Log::channel('ia_orchestrator')->warning(
                        'OpenAIService createResponse exception after conversation reset',
                        $this->logContext($payload, $conexao, array_merge($logContext, [
                            'conversation_recovery' => 'conv_id_reset_retry',
                        ]))
                    );
                    return IAResult::error('OpenAI exception.', 'openai');
                }
            }
        }

        if ($response->failed()) {
            $this->handleOpenAIError($response, 'createResponse', $logContext);
            return IAResult::error('OpenAI error.', 'openai', $response->json());
        }

        $context = [
            'conversation_id' => $assistantLead->conv_id,
            'model' => $model,
            'conexao_id' => $payload['conexao_id'] ?? $conexao->id,
            'lead_id' => $lead->id,
            'assistant_id' => $assistant->id,
            'phone' => $payload['phone'] ?? null,
            'token' => $conexao->whatsapp_api_key,
            'system_prompt' => $systemPrompt,
        ];

        $response = $this->processToolCalls($response, $openAiService, $context, $handlers, [
            'request_options' => $this->openAiRequestOptions($logContext),
        ]) ?? $response;

        if (!$response) {
            Log::channel('ia_orchestrator')->warning('OpenAIService response missing after function calls.', $this->logContext($payload, $conexao, $logContext));
            return IAResult::error('OpenAI response missing.', 'openai');
        }
        if ($response->failed()) {
            $this->handleOpenAIError($response, 'function_call_response', $logContext);
            return IAResult::error('OpenAI error.', 'openai', $response->json());
        }

        $assistantResult = $this->resolveAssistantResult($response->json() ?? []);
        if (!$assistantResult->ok) {
            Log::channel('ia_orchestrator')->warning('OpenAIService sem mensagem do assistente.', $this->logContext($payload, $conexao, $logContext));
            return $assistantResult;
        }

        return $assistantResult;
    }

    private function ensureConversation(AssistantLead $assistantLead, Assistant $assistant, OpenAIService $openAiService, string $systemPrompt, array $logContext): ?AssistantLead
    {
        if (empty($assistantLead->conv_id)) {
            $convId = $this->createConversation($openAiService, $systemPrompt, $logContext);
            if (!$convId) {
                return null;
            }

            $assistantLead->conv_id = $convId;
            $assistantLead->version = $assistant->version ?? $assistantLead->version ?? 1;
            try {
                if ($assistantLead->save() === false) {
                    $this->setConversationResolutionFailure('assistant_lead_save', 'save_returned_false', [
                        'assistant_lead_id' => $assistantLead->id,
                        'after' => 'create_conversation',
                    ]);
                    Log::channel('ia_orchestrator')->warning('Falha ao salvar AssistantLead apos criar conversation.', array_merge($logContext, [
                        'assistant_lead_id' => $assistantLead->id,
                    ]));
                    return null;
                }
            } catch (\Throwable $e) {
                $this->setConversationResolutionFailure('assistant_lead_save', 'exception', [
                    'assistant_lead_id' => $assistantLead->id,
                    'after' => 'create_conversation',
                    'error' => $e->getMessage(),
                ]);
                Log::channel('ia_orchestrator')->error('Falha ao salvar AssistantLead apos criar conversation.', array_merge($logContext, [
                    'assistant_lead_id' => $assistantLead->id,
                    'error' => $e->getMessage(),
                ]));
                return null;
            }
        } elseif ($assistantLead->version && $assistant->version && $assistantLead->version !== $assistant->version) {
            $updated = $this->updateConversationContext($openAiService, $assistantLead->conv_id, $systemPrompt, $logContext);
            if (!$updated) {
                $recovered = $this->resetConversationAfterUpdateContextNotFound(
                    $assistantLead,
                    $assistant,
                    $openAiService,
                    $systemPrompt,
                    $logContext
                );
                if ($recovered) {
                    return $recovered;
                }
                return null;
            }
            $assistantLead->version = $assistant->version;
            try {
                if ($assistantLead->save() === false) {
                    $this->setConversationResolutionFailure('assistant_lead_save', 'save_returned_false', [
                        'assistant_lead_id' => $assistantLead->id,
                        'after' => 'update_context',
                    ]);
                    Log::channel('ia_orchestrator')->warning('Falha ao salvar AssistantLead apos atualizar contexto.', array_merge($logContext, [
                        'assistant_lead_id' => $assistantLead->id,
                    ]));
                    return null;
                }
            } catch (\Throwable $e) {
                $this->setConversationResolutionFailure('assistant_lead_save', 'exception', [
                    'assistant_lead_id' => $assistantLead->id,
                    'after' => 'update_context',
                    'error' => $e->getMessage(),
                ]);
                Log::channel('ia_orchestrator')->error('Falha ao salvar AssistantLead apos atualizar contexto.', array_merge($logContext, [
                    'assistant_lead_id' => $assistantLead->id,
                    'error' => $e->getMessage(),
                ]));
                return null;
            }
        }

        return $assistantLead;
    }

    private function createConversation(OpenAIService $openAiService, string $systemPrompt, array $logContext = []): ?string
    {
        $payload = [
            'items' => [
                [
                    'type' => 'message',
                    'role' => 'system',
                    'content' => $systemPrompt,
                ],
            ],
        ];

        $response = $openAiService->createConversation($payload, $this->openAiRequestOptions($logContext));
        if (!$response) {
            $this->setConversationResolutionFailure('create_conversation', 'exception');
            Log::channel('ia_orchestrator')->warning('Falha ao criar conversation (exception).', $logContext);
            return null;
        }

        if ($response->failed()) {
            $this->setConversationResolutionFailure('create_conversation', 'http_error', [
                'status' => $response->status(),
                'openai_error_message' => $this->extractOpenAIErrorMessage($response),
            ]);
            $this->handleOpenAIError($response, 'createConversation', $logContext);
            return null;
        }

        $convId = $response->json('id');
        if (!$convId) {
            $this->setConversationResolutionFailure('create_conversation', 'missing_id', [
                'status' => $response->status(),
            ]);
            Log::channel('ia_orchestrator')->error('OpenAIService createConversation missing id', $logContext);
            return null;
        }

        return (string) $convId;
    }

    private function updateConversationContext(OpenAIService $openAiService, string $conversationId, string $systemPrompt, array $logContext = []): bool
    {
        $payload = [
            'items' => [
                [
                    'type' => 'message',
                    'role' => 'system',
                    'content' => [
                        [
                            'type' => 'input_text',
                            'text' => "Novo contexto atualizado:\n\n{$systemPrompt}",
                        ],
                    ],
                ],
            ],
        ];

        $response = $openAiService->createItems($conversationId, $payload, $this->openAiRequestOptions($logContext));
        if (!$response) {
            $this->setConversationResolutionFailure('update_context', 'exception', [
                'conversation_id' => $conversationId,
            ]);
            Log::channel('ia_orchestrator')->warning('Falha ao atualizar contexto (exception).', $logContext);
            return false;
        }

        if ($response->failed()) {
            $this->setConversationResolutionFailure('update_context', 'http_error', [
                'conversation_id' => $conversationId,
                'status' => $response->status(),
                'openai_error_message' => $this->extractOpenAIErrorMessage($response),
            ]);
            $this->handleOpenAIError($response, 'createItems', $logContext);
            return false;
        }

        return true;
    }

    private function resetConversationAfterUpdateContextNotFound(
        AssistantLead $assistantLead,
        Assistant $assistant,
        OpenAIService $openAiService,
        string $systemPrompt,
        array $logContext
    ): ?AssistantLead {
        if (!$this->isConversationNotFoundFailure($this->conversationResolutionFailure, $assistantLead->conv_id)) {
            return null;
        }

        $oldConversationId = is_string($assistantLead->conv_id ?? null) ? (string) $assistantLead->conv_id : null;
        if (!$oldConversationId) {
            return null;
        }

        $errorMessage = is_string($this->conversationResolutionFailure['openai_error_message'] ?? null)
            ? (string) $this->conversationResolutionFailure['openai_error_message']
            : null;

        Log::channel('ia_orchestrator')->warning(
            'OpenAI conversation not found durante update_context; resetando conv_id e recriando conversation.',
            array_merge($logContext, [
                'assistant_lead_id' => $assistantLead->id,
                'old_conversation_id' => $oldConversationId,
                'openai_error_message' => $errorMessage,
            ])
        );

        try {
            $assistantLead->conv_id = null;
            $assistantLead->save();
        } catch (\Throwable $e) {
            Log::channel('ia_orchestrator')->error(
                'Falha ao resetar conv_id apos update_context conversation not found.',
                array_merge($logContext, [
                    'assistant_lead_id' => $assistantLead->id,
                    'old_conversation_id' => $oldConversationId,
                    'error' => $e->getMessage(),
                ])
            );
            return null;
        }

        $refreshed = $assistantLead->fresh();
        if ($refreshed instanceof AssistantLead) {
            $assistantLead = $refreshed;
        }

        $assistantLead = $this->ensureConversation($assistantLead, $assistant, $openAiService, $systemPrompt, $logContext);
        if (!$assistantLead || empty($assistantLead->conv_id)) {
            Log::channel('ia_orchestrator')->warning(
                'Nao foi possivel recriar conversation apos update_context conversation not found.',
                array_merge($logContext, [
                    'assistant_lead_id' => $refreshed?->id ?? $assistantLead?->id ?? null,
                    'old_conversation_id' => $oldConversationId,
                ])
            );
            return null;
        }

        Log::channel('ia_orchestrator')->info(
            'Conversation recriada apos not found durante update_context; fluxo seguira com nova conversation.',
            array_merge($logContext, [
                'assistant_lead_id' => $assistantLead->id,
                'old_conversation_id' => $oldConversationId,
                'new_conversation_id' => $assistantLead->conv_id,
            ])
        );

        return $assistantLead;
    }

    private function setConversationResolutionFailure(string $stage, string $reason, array $extra = []): void
    {
        $this->conversationResolutionFailure = array_merge([
            'stage' => $stage,
            'reason' => $reason,
        ], $extra);
    }

    private function buildConversationResolutionErrorMessage(array $failure): string
    {
        $stage = (string) ($failure['stage'] ?? 'unknown');
        $reason = (string) ($failure['reason'] ?? 'unknown');
        $status = isset($failure['status']) ? (string) $failure['status'] : null;
        $openAiError = isset($failure['openai_error_message']) && is_string($failure['openai_error_message'])
            ? trim($failure['openai_error_message'])
            : null;

        $message = match ("{$stage}:{$reason}") {
            'create_conversation:exception' => 'Conversation id ausente (falha ao criar conversation: exception na requisicao).',
            'create_conversation:http_error' => 'Conversation id ausente (falha ao criar conversation: erro HTTP da OpenAI).',
            'create_conversation:missing_id' => 'Conversation id ausente (falha ao criar conversation: resposta sem id).',
            'update_context:exception' => 'Conversation id ausente (falha ao atualizar contexto da conversation: exception na requisicao).',
            'update_context:http_error' => 'Conversation id ausente (falha ao atualizar contexto da conversation: erro HTTP da OpenAI).',
            'assistant_lead_save:save_returned_false' => 'Conversation id ausente (falha ao salvar AssistantLead apos resolver conversation).',
            'assistant_lead_save:exception' => 'Conversation id ausente (exception ao salvar AssistantLead apos resolver conversation).',
            'ensure_conversation:empty_after_resolution' => 'Conversation id ausente (ensureConversation retornou sem conv_id).',
            default => "Conversation id ausente ({$stage}:{$reason}).",
        };

        if ($status) {
            $message .= " status={$status}.";
        }

        if ($openAiError) {
            $message .= ' openai_error=' . $openAiError;
        }

        return $message;
    }

    private function openAiRequestOptions(array $logContext = []): array
    {
        return [
            'timeout' => 300,
            'max_retries' => 2,
            'base_delay_ms' => 1000,
            'max_delay_ms' => 8000,
            'log_context' => $logContext,
        ];
    }

    private function buildOpenAIInput(array $payload, OpenAIService $openAiService, array $logContext, Conexao $conexao): array
    {
        $tipo = Str::lower((string) ($payload['tipo'] ?? 'text'));
        $text = (string) ($payload['text'] ?? '');
        $role = ($payload['openai_role'] ?? null) === 'system' ? 'system' : 'user';

        if (str_contains($tipo, 'audio')) {
            $media = $this->resolveMediaFromPayload($payload);
            $base64 = $this->resolveMediaBase64($media, $logContext, $payload, $conexao);
            if (!$base64) {
                return [];
            }

            $response = $openAiService->transcreverAudio($base64, $this->openAiRequestOptions($logContext));
            if (!$response) {
                Log::channel('ia_orchestrator')->warning('Transcrição de áudio exception', $this->logContext($payload, $conexao, $logContext));
                return [];
            }

            if ($response->failed()) {
                $this->handleOpenAIError($response, 'transcreverAudio', $logContext);
                return [];
            }

            $transcription = $response->json('text');
            $transcription = is_string($transcription) ? trim($transcription) : '';
            if ($transcription === '') {
                return [];
            }

            return [
                [
                    'role' => $role,
                    'content' => $transcription,
                ],
            ];
        }

        if (str_contains($tipo, 'image')) {
            $media = $this->resolveMediaFromPayload($payload);
            $base64 = $this->resolveMediaBase64($media, $logContext, $payload, $conexao);
            if (!$base64) {
                return [];
            }

            $caption = $text !== '' ? $text : 'Imagem enviada.';
            $mimetype = $media['mimetype'] ?? 'image/jpeg';

            return [
                [
                    'role' => $role,
                    'content' => [
                        [
                            'type' => 'input_text',
                            'text' => $caption,
                        ],
                        [
                            'type' => 'input_image',
                            'image_url' => "data:{$mimetype};base64,{$base64}",
                        ],
                    ],
                ],
            ];
        }

        if (str_contains($tipo, 'video')) {
            if ($text !== '') {
            return [
                [
                    'role' => $role,
                    'content' => $text,
                ],
            ];
        }

            Log::channel('ia_orchestrator')->warning('Video sem legenda não suportado.', $this->logContext($payload, $conexao, $logContext));
            return [];
        }

        if (str_contains($tipo, 'document')) {
            $media = $this->resolveMediaFromPayload($payload);
            $base64 = $this->resolveMediaBase64($media, $logContext, $payload, $conexao);
            if (!$base64) {
                return [];
            }

            $caption = $text !== '' ? $text : 'Documento enviado.';
            $filename = $media['filename'] ?? 'documento.pdf';
            $mimetype = $media['mimetype'] ?? 'application/octet-stream';

            return [
                [
                    'role' => $role,
                    'content' => [
                        [
                            'type' => 'input_text',
                            'text' => $caption,
                        ],
                        [
                            'type' => 'input_file',
                            'filename' => $filename,
                            'file_data' => "data:{$mimetype};base64,{$base64}",
                        ],
                    ],
                ],
            ];
        }

        if (trim($text) === '') {
            return [];
        }

        return [
            [
                'role' => $role,
                'content' => $text,
            ],
        ];
    }

    private function resolveMediaFromPayload(array $payload): array
    {
        $media = is_array($payload['media'] ?? null) ? $payload['media'] : [];
        return $media;
    }

    private function resolveMediaBase64(array $media, array $logContext, array $payload, Conexao $conexao): ?string
    {
        $base64 = $media['base64'] ?? null;
        if (is_string($base64) && $base64 !== '') {
            return $base64;
        }

        $storageKey = $media['storage_key'] ?? null;
        if (!is_string($storageKey) || $storageKey === '') {
            Log::channel('ia_orchestrator')->warning('Media sem base64/storage_key.', $this->logContext($payload, $conexao, $logContext));
            return null;
        }

        $disk = Storage::disk(config('media.disk', 'local'));
        if (!$disk->exists($storageKey)) {
            Log::channel('ia_orchestrator')->warning('Arquivo de mídia não encontrado.', $this->logContext($payload, $conexao, array_merge($logContext, [
                'storage_key' => $storageKey,
            ])));
            return null;
        }

        $binary = $disk->get($storageKey);
        if ($binary === false || $binary === null) {
            Log::channel('ia_orchestrator')->warning('Falha ao ler arquivo de mídia.', $this->logContext($payload, $conexao, array_merge($logContext, [
                'storage_key' => $storageKey,
            ])));
            return null;
        }

        return base64_encode($binary);
    }

    private function prependSystemContext(array $input, ClienteLead $lead, ?string $timezone = null): array
    {
        $timezone = $timezone ?: config('app.timezone', 'America/Sao_Paulo');
        $now = now($timezone);
        $dayName = $now->locale('pt_BR')->isoFormat('dddd');
        $date = $now->format('Y-m-d');
        $time = $now->format('H:i');

        $lead->loadMissing('customFieldValues.customField');

        $leadInfo = trim((string) ($lead->info ?? ''));
        $leadCustomFields = $lead->customFieldValues
            ->filter(function ($fieldValue) {
                $value = trim((string) ($fieldValue->value ?? ''));
                return $value !== '' && $fieldValue->customField;
            })
            ->map(function ($fieldValue) {
                $field = $fieldValue->customField;
                $name = trim((string) ($field->name ?? ''));
                $label = trim((string) ($field->label ?: $name));
                $value = trim((string) ($fieldValue->value ?? ''));

                if ($label !== '' && $name !== '' && $label !== $name) {
                    return "- {$label} ({$name}): {$value}";
                }

                return "- {$label}: {$value}";
            })
            ->filter()
            ->values()
            ->all();

        $contextParts = array_filter([
            "Agora: {$now->toIso8601String()} ({$dayName}, {$date} as {$time}, tz: {$timezone}).",
            $leadInfo !== '' ? "Info do lead: {$leadInfo}" : null,
            !empty($leadCustomFields) ? "Campos personalizados do lead:\n" . implode("\n", $leadCustomFields) : null,
        ]);

        return array_merge([
            [
                'role' => 'system',
                'content' => implode("\n\n", $contextParts),
            ],
        ], $input);
    }

    private function resolveLeadCustomFieldsForTools(ClienteLead $lead): array
    {
        $lead->loadMissing('cliente');

        $userId = (int) ($lead->cliente?->user_id ?? 0);
        $clienteId = (int) ($lead->cliente_id ?? 0);
        if ($userId <= 0 || $clienteId <= 0) {
            return [];
        }

        return WhatsappCloudCustomField::query()
            ->where('user_id', $userId)
            ->where(function ($query) use ($clienteId) {
                $query->whereNull('cliente_id')
                    ->orWhere('cliente_id', $clienteId);
            })
            ->orderByRaw('CASE WHEN cliente_id IS NULL THEN 1 ELSE 0 END')
            ->orderBy('name')
            ->get(['name', 'label'])
            ->map(function (WhatsappCloudCustomField $field) {
                return [
                    'name' => trim((string) $field->name),
                    'label' => trim((string) ($field->label ?? '')),
                ];
            })
            ->filter(fn (array $field) => $field['name'] !== '')
            ->unique('name')
            ->values()
            ->all();
    }

    private function extractAssistantMessage(array $apiResponse): ?string
    {
        $output = $apiResponse['output'] ?? [];
        if (!is_array($output) || empty($output)) {
            return null;
        }

        $lastOutput = end($output);
        if (
            is_array($lastOutput) &&
            ($lastOutput['type'] ?? null) !== null &&
            in_array($lastOutput['type'], ['message', 'output_text'], true) &&
            ($lastOutput['role'] ?? null) === 'assistant' &&
            isset($lastOutput['content'][0]['text'])
        ) {
            return $lastOutput['content'][0]['text'];
        }

        foreach (array_reverse($output) as $outputItem) {
            if (
                isset($outputItem['type']) &&
                in_array($outputItem['type'], ['message', 'output_text'], true) &&
                ($outputItem['role'] ?? null) === 'assistant' &&
                isset($outputItem['content'][0]['text'])
            ) {
                return $outputItem['content'][0]['text'];
            }
        }

        return null;
    }

    private function resolveAssistantResult(array $apiResponse): IAResult
    {
        $output = $apiResponse['output'] ?? [];
        if (!is_array($output) || empty($output)) {
            return IAResult::error('OpenAI sem mensagem do assistente.', 'openai', $apiResponse);
        }

        $assistantText = $this->extractAssistantMessage($apiResponse);
        if (is_string($assistantText) && trim($assistantText) !== '') {
            return IAResult::success($assistantText, 'openai', $apiResponse);
        }

        if ($this->hasPendingFunctionCall($apiResponse)) {
            return IAResult::error('OpenAI sem mensagem do assistente.', 'openai', $apiResponse);
        }

        return IAResult::success('', 'openai', $apiResponse);
    }

    private function hasPendingFunctionCall(array $apiResponse): bool
    {
        $output = $apiResponse['output'] ?? [];
        if (!is_array($output) || empty($output)) {
            return false;
        }

        foreach ($output as $item) {
            if (is_array($item) && ($item['type'] ?? null) === 'function_call') {
                return true;
            }
        }

        return false;
    }

    private function processToolCalls(Response $response, OpenAIService $openAI, array $context, array $handlers, array $options = []): ?Response
    {
        $current = $response;
        $iterations = 0;
        $requestOptions = $options['request_options'] ?? [];

        while ($current && $iterations < $this->maxIterations) {
            $payload = $current->json();
            if (!is_array($payload)) {
                return $current;
            }

            $output = $payload['output'] ?? [];
            if (!is_array($output) || empty($output)) {
                return $current;
            }

            $toolOutputs = $this->buildToolOutputs($output, $handlers, $context);
            if (empty($toolOutputs)) {
                return $current;
            }

            $conversationId = $context['conversation_id'] ?? null;
            if (!is_string($conversationId) || $conversationId === '') {
                Log::channel('ia_orchestrator')->warning('OpenAIOrchestratorService missing conversation id', $context);
                return $current;
            }

            $payload = [
                'model' => $context['model'] ?? 'gpt-4.1-mini',
                'input' => $toolOutputs,
                'conversation' => $conversationId,
                'truncation' => 'auto',
            ];

            $current = $openAI->createResponse($payload, $requestOptions);
            if (!$current) {
                return null;
            }

            $iterations++;
        }

        if ($iterations >= $this->maxIterations) {
            Log::channel('ia_orchestrator')->warning('OpenAIOrchestratorService max iterations reached', [
                'conversation_id' => $context['conversation_id'] ?? null,
            ]);
        }

        return $current;
    }

    private function buildToolOutputs(array $output, array $handlers, array $context): array
    {
        $toolOutputs = [];

        foreach ($output as $item) {
            if (($item['type'] ?? null) !== 'function_call') {
                continue;
            }

            $name = $item['name'] ?? null;
            $callId = $item['call_id'] ?? $item['id'] ?? null;
            if (!is_string($name) || $name === '' || !is_string($callId) || $callId === '') {
                continue;
            }

            $arguments = $this->parseArguments($item['arguments'] ?? null);
            if (!is_array($arguments)) {
                $toolOutputs[] = [
                    'type' => 'function_call_output',
                    'call_id' => $callId,
                    'output' => 'Argumentos inválidos para a chamada da função.',
                ];
                continue;
            }

            $handler = $handlers[$name] ?? null;
            if (!is_callable($handler)) {
                $toolOutputs[] = [
                    'type' => 'function_call_output',
                    'call_id' => $callId,
                    'output' => "Função {$name} não suportada.",
                ];
                continue;
            }

            try {
                $result = $handler($arguments, $context);
            } catch (\Throwable $e) {
                Log::channel('ia_orchestrator')->error('OpenAIOrchestratorService handler exception', [
                    'function' => $name,
                    'error' => $e->getMessage(),
                ]);
                $result = 'Erro ao executar a função.';
            }

            if (is_array($result) && array_key_exists('output', $result)) {
                $result = $result['output'];
            }

            if ($result === null) {
                $result = 'Nenhuma resposta retornada pela função.';
            }

            $toolOutputs[] = [
                'type' => 'function_call_output',
                'call_id' => $callId,
                'output' => (string) $result,
            ];
        }

        return $toolOutputs;
    }

    private function parseArguments($rawArguments): ?array
    {
        if (is_array($rawArguments)) {
            return $rawArguments;
        }

        if (is_string($rawArguments) && $rawArguments !== '') {
            $decoded = json_decode($rawArguments, true);
            return is_array($decoded) ? $decoded : null;
        }

        return null;
    }

    private function isConversationNotFoundResponse(Response $response, ?string $expectedConversationId = null): bool
    {
        if (!in_array($response->status(), [400, 404], true)) {
            return false;
        }

        $message = $this->extractOpenAIErrorMessage($response);
        if (!$message) {
            return false;
        }

        $lower = Str::lower($message);
        if (!str_contains($lower, 'conversation with id') || !str_contains($lower, 'not found')) {
            return false;
        }

        $missingConversationId = $this->extractConversationIdFromNotFoundMessage($message);
        if ($expectedConversationId && $missingConversationId && $missingConversationId !== $expectedConversationId) {
            Log::channel('ia_orchestrator')->warning('OpenAI conversation not found com conv_id diferente do assistant_lead.', [
                'expected_conversation_id' => $expectedConversationId,
                'missing_conversation_id' => $missingConversationId,
            ]);
        }

        return true;
    }

    private function isConversationNotFoundFailure(?array $failure, ?string $expectedConversationId = null): bool
    {
        if (!is_array($failure)) {
            return false;
        }

        if (($failure['stage'] ?? null) !== 'update_context') {
            return false;
        }

        if (($failure['reason'] ?? null) !== 'http_error') {
            return false;
        }

        $status = (int) ($failure['status'] ?? 0);
        if (!in_array($status, [400, 404], true)) {
            return false;
        }

        $message = is_string($failure['openai_error_message'] ?? null)
            ? trim((string) $failure['openai_error_message'])
            : '';
        if ($message === '') {
            return false;
        }

        $lower = Str::lower($message);
        if (!str_contains($lower, 'conversation with id') || !str_contains($lower, 'not found')) {
            return false;
        }

        $missingConversationId = $this->extractConversationIdFromNotFoundMessage($message);
        if ($expectedConversationId && $missingConversationId && $missingConversationId !== $expectedConversationId) {
            Log::channel('ia_orchestrator')->warning('OpenAI conversation not found (failure struct) com conv_id diferente do assistant_lead.', [
                'expected_conversation_id' => $expectedConversationId,
                'missing_conversation_id' => $missingConversationId,
            ]);
        }

        return true;
    }

    private function resetConversationAfterNotFound(
        AssistantLead $assistantLead,
        Assistant $assistant,
        OpenAIService $openAiService,
        string $systemPrompt,
        array $logContext,
        Response $failedResponse
    ): ?AssistantLead {
        $oldConversationId = is_string($assistantLead->conv_id ?? null) ? (string) $assistantLead->conv_id : null;
        if (!$oldConversationId) {
            return null;
        }

        $errorMessage = $this->extractOpenAIErrorMessage($failedResponse);

        Log::channel('ia_orchestrator')->warning('OpenAI conversation not found; resetando conv_id e tentando novamente.', array_merge(
            $logContext,
            [
                'assistant_lead_id' => $assistantLead->id,
                'old_conversation_id' => $oldConversationId,
                'openai_error_message' => $errorMessage,
            ]
        ));

        try {
            $assistantLead->conv_id = null;
            $assistantLead->save();
        } catch (\Throwable $e) {
            Log::channel('ia_orchestrator')->error('Falha ao resetar conv_id após conversation not found.', array_merge(
                $logContext,
                [
                    'assistant_lead_id' => $assistantLead->id,
                    'old_conversation_id' => $oldConversationId,
                    'error' => $e->getMessage(),
                ]
            ));
            return null;
        }

        $refreshed = $assistantLead->fresh();
        if ($refreshed instanceof AssistantLead) {
            $assistantLead = $refreshed;
        }

        $assistantLead = $this->ensureConversation($assistantLead, $assistant, $openAiService, $systemPrompt, $logContext);
        if (!$assistantLead || empty($assistantLead->conv_id)) {
            Log::channel('ia_orchestrator')->warning('Nao foi possivel recriar conversation apos reset de conv_id.', array_merge(
                $logContext,
                [
                    'assistant_lead_id' => $refreshed?->id ?? $assistantLead?->id ?? null,
                    'old_conversation_id' => $oldConversationId,
                ]
            ));
            return null;
        }

        Log::channel('ia_orchestrator')->info('Conversation recriada apos not found; retry do createResponse sera executado.', array_merge(
            $logContext,
            [
                'assistant_lead_id' => $assistantLead->id,
                'old_conversation_id' => $oldConversationId,
                'new_conversation_id' => $assistantLead->conv_id,
            ]
        ));

        return $assistantLead;
    }

    private function extractOpenAIErrorMessage(Response $response): ?string
    {
        $message = $response->json('error.message');
        if (!is_string($message)) {
            return null;
        }

        $message = trim($message);
        return $message !== '' ? $message : null;
    }

    private function extractConversationIdFromNotFoundMessage(string $message): ?string
    {
        if (preg_match('/conversation with id [\'"]([^\'"]+)[\'"] not found/i', $message, $matches) !== 1) {
            return null;
        }

        $conversationId = trim((string) ($matches[1] ?? ''));
        return $conversationId !== '' ? $conversationId : null;
    }

    private function handleOpenAIError($response, string $context, array $logContext): void
    {
        $status = $response->status();
        if (in_array($status, [400, 401, 403], true)) {
            $this->logPermanentError($context, $response, $logContext);
            return;
        }

        Log::channel('ia_orchestrator')->warning("OpenAI error: {$context}", $this->logContext([], null, array_merge($logContext, [
            'status' => $status,
            'body' => $response->body(),
        ])));
    }

    private function logPermanentError(string $function, $response, array $logContext): void
    {
        Log::channel('ia_orchestrator')->error("OpenAI error permanente: {$function}", $this->logContext([], null, array_merge($logContext, [
            'status' => $response->status(),
            'body' => $response->body(),
        ])));

        try {
            SystemErrorLog::create([
                'context' => 'OpenAIOrchestratorService',
                'function_name' => $function,
                'message' => 'OpenAI error permanente',
                'payload' => array_merge($logContext, [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]),
            ]);
        } catch (\Throwable $e) {
            Log::channel('ia_orchestrator')->error('Falha ao registrar SystemErrorLog', $this->logContext([], null, [
                'error' => $e->getMessage(),
            ]));
        }
    }

    private function logContext(array $payload = [], ?Conexao $conexao = null, array $extra = []): array
    {
        return LogContext::merge(
            LogContext::base($payload, $conexao),
            $extra
        );
    }
}
