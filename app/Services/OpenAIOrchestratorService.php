<?php

namespace App\Services;

use App\DTOs\IAResult;
use App\Models\Assistant;
use App\Models\AssistantLead;
use App\Models\ClienteLead;
use App\Models\Conexao;
use App\Models\SystemErrorLog;
use App\Support\LogContext;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class OpenAIOrchestratorService
{
    protected int $maxIterations;

    public function __construct(int $maxIterations = 5)
    {
        $this->maxIterations = $maxIterations;
    }

    public function handle(Conexao $conexao, Assistant $assistant, ClienteLead $lead, AssistantLead $assistantLead, array $payload, array $handlers = []): IAResult
    {
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
            Log::channel('ia_orchestrator')->warning('Conversation id ausente após resolução.', $this->logContext($payload, $conexao, $logContext));
            return IAResult::error('Conversation id ausente.', 'openai');
        }

        $payload['conversation_id'] = $assistantLead->conv_id;

        $input = $this->buildOpenAIInput($payload, $openAiService, $logContext, $conexao);
        if (empty($input)) {
            Log::channel('ia_orchestrator')->warning('OpenAI input vazio.', $this->logContext($payload, $conexao, $logContext));
            return IAResult::error('OpenAI input vazio.', 'openai');
        }

        $contactName = $payload['contact_name'] ?? null;
        $input = $this->prependSystemContext($input, is_string($contactName) ? $contactName : null);

        $model = (string) ($payload['assistant_model'] ?? 'gpt-4.1-mini');
        $requestPayload = [
            'model' => $model,
            'input' => $input,
            'conversation' => $assistantLead->conv_id,
        ];

        $tools = ToolsFactory::fromSystemPrompt($systemPrompt);
        if (!empty($tools)) {
            $requestPayload['tools'] = $tools;
        }

        $response = $openAiService->createResponse($requestPayload, $this->openAiRequestOptions($logContext));
        if (!$response) {
            Log::channel('ia_orchestrator')->warning('OpenAIService createResponse exception', $this->logContext($payload, $conexao, $logContext));
            return IAResult::error('OpenAI exception.', 'openai');
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

        $assistantText = $this->extractAssistantMessage($response->json() ?? []);
        if (!$assistantText) {
            Log::channel('ia_orchestrator')->warning('OpenAIService sem mensagem do assistente.', $this->logContext($payload, $conexao, $logContext));
            return IAResult::error('OpenAI sem mensagem do assistente.', 'openai', $response->json());
        }

        return IAResult::success($assistantText, 'openai', $response->json());
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
            $assistantLead->save();
        } elseif ($assistantLead->version && $assistant->version && $assistantLead->version !== $assistant->version) {
            $updated = $this->updateConversationContext($openAiService, $assistantLead->conv_id, $systemPrompt, $logContext);
            if (!$updated) {
                return null;
            }
            $assistantLead->version = $assistant->version;
            $assistantLead->save();
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
            Log::channel('ia_orchestrator')->warning('Falha ao criar conversation (exception).', $logContext);
            return null;
        }

        if ($response->failed()) {
            $this->handleOpenAIError($response, 'createConversation', $logContext);
            return null;
        }

        $convId = $response->json('id');
        if (!$convId) {
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
            Log::channel('ia_orchestrator')->warning('Falha ao atualizar contexto (exception).', $logContext);
            return false;
        }

        if ($response->failed()) {
            $this->handleOpenAIError($response, 'createItems', $logContext);
            return false;
        }

        return true;
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

    private function prependSystemContext(array $input, ?string $contactName = null, ?string $timezone = null): array
    {
        $timezone = $timezone ?: config('app.timezone', 'America/Sao_Paulo');
        $now = now($timezone);
        $dayName = $now->locale('pt_BR')->isoFormat('dddd');
        $date = $now->format('Y-m-d');
        $time = $now->format('H:i');
        $contactInfo = $contactName ? "nome do cliente/contato: {$contactName}" : '';

        return array_merge([
            [
                'role' => 'system',
                'content' => "Agora: {$now->toIso8601String()} ({$dayName}, {$date} as {$time}, tz: {$timezone}).\n{$contactInfo}",
            ],
        ], $input);
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
