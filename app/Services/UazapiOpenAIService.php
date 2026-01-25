<?php

namespace App\Services;

use App\Models\Assistant;
use App\Models\UazapiChat;
use App\Models\UazapiInstance;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Str;

class UazapiOpenAIService
{
    protected string $baseUrl = 'https://api.openai.com/v1';
    protected string $apiKey = '';

    protected ?UazapiInstance $instance = null;
    protected ?UazapiChat $chat = null;
    protected ?Assistant $assistant = null;
    protected ?string $conversationId = null;
    protected ?string $systemPrompt = null;

    public function handle(array $payload): void
    {
        $token = $payload['token'] ?? null;
        $instanceId = $payload['instance_id'] ?? null;
        $phone = $payload['phone'] ?? null;

        if (!$token || !$phone) {
            Log::warning('UazapiOpenAIService missing token or phone');
            return;
        }

        $instance = $instanceId ? UazapiInstance::find($instanceId) : null;
        if (!$instance) {
            $instance = UazapiInstance::where('token', $token)->first();
        }
        if (!$instance) {
            Log::warning('UazapiOpenAIService instance not found', ['token' => $token]);
            return;
        }

        $chat = UazapiChat::where('uazapi_instance_id', $instance->id)
            ->where('phone', $phone)
            ->first();
        if (!$chat) {
            Log::warning('UazapiOpenAIService chat not found', ['instance_id' => $instance->id, 'phone' => $phone]);
            return;
        }

        $assistant = $instance->assistant_id ? Assistant::find($instance->assistant_id) : null;
        if (!$assistant) {
            Log::warning('UazapiOpenAIService assistant not found', ['instance_id' => $instance->id]);
            return;
        }

        $credential = $assistant->credential ?? null;
        $apiKey = $credential?->token;
        if (!$apiKey || $apiKey === '******') {
            Log::warning('UazapiOpenAIService credential missing', ['assistant_id' => $assistant->id]);
            return;
        }

        $this->apiKey = $apiKey;
        $this->instance = $instance;
        $this->chat = $chat;
        $this->assistant = $assistant;
        $this->conversationId = $chat->conv_id ?: null;
        $this->systemPrompt = $this->buildSystemPrompt($assistant);

        if ($chat->conv_id && $assistant->version && $chat->version && $chat->version !== $assistant->version) {
            if ($this->createItems($chat->conv_id, $this->systemPrompt)) {
                $chat->version = $assistant->version;
                $chat->save();
            }
        }

        $input = $this->buildInput($payload);
        if (empty($input)) {
            return;
        }

        $model = $assistant->modelo ?: 'gpt-4.1-mini';
        $responseText = $this->createResponse($input, $model);

        if (!empty($responseText)) {
            $uazapi = new UazapiService();
            $sendResult = $uazapi->sendText($instance->token, $phone, $responseText);
            if (!empty($sendResult['error'])) {
                Log::error('UazapiOpenAIService sendText failed', [
                    'response' => $sendResult,
                ]);
            }
        }
    }

    protected function buildInput(array $payload): array
    {
        $tipo = $payload['tipo'] ?? 'text';
        $message = $payload['message'] ?? [];

        $tipoLower = Str::lower((string) $tipo);
        if ($tipoLower === 'text') {
            $text = $payload['combined_text']
                ?? ($message['text'] ?? null)
                ?? ($message['content'] ?? null);

            $text = is_string($text) ? trim($text) : '';
            if ($text === '') {
                return [];
            }

            return [
                [
                    'role' => 'user',
                    'content' => $text,
                ],
            ];
        }

        if (str_contains($tipoLower, 'audio')) {
            $media = (new DescriptoService())->decryptToBase64($message, (string) $tipo);
            if (!$media || empty($media['base64'])) {
                return [];
            }

            $transcription = $this->transcreverAudio($media['base64']);
            $transcription = is_string($transcription) ? trim($transcription) : '';
            if ($transcription === '') {
                return [];
            }

            return [
                [
                    'role' => 'user',
                    'content' => $transcription,
                ],
            ];
        }

        if (str_contains($tipoLower, 'image')) {
            $media = (new DescriptoService())->decryptToBase64($message, (string) $tipo);
            if (!$media || empty($media['base64'])) {
                return [];
            }

            $caption = $message['text'] ?? 'Imagem enviada.';
            return [
                [
                    'role' => 'user',
                    'content' => [
                        [
                            'type' => 'input_text',
                            'text' => $caption,
                        ],
                        [
                            'type' => 'input_image',
                            'image_url' => "data:{$media['mimetype']};base64,{$media['base64']}",
                        ],
                    ],
                ],
            ];
        }

        if (str_contains($tipoLower, 'document')) {
            $media = (new DescriptoService())->decryptToBase64($message, (string) $tipo);
            if (!$media || empty($media['base64'])) {
                return [];
            }

            $caption = $message['text'] ?? 'Documento enviado.';
            $filename = $message['content']['fileName'] ?? 'documento';

            return [
                [
                    'role' => 'user',
                    'content' => [
                        [
                            'type' => 'input_text',
                            'text' => $caption,
                        ],
                        [
                            'type' => 'input_file',
                            'filename' => $filename,
                            'file_data' => "data:{$media['mimetype']};base64,{$media['base64']}",
                        ],
                    ],
                ],
            ];
        }

        return [];
    }

    protected function buildSystemPrompt(Assistant $assistant): string
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

        $parts = array_filter($parts, function ($value) {
            return is_string($value) && trim($value) !== '';
        });

        return trim(implode("\n", $parts));
    }

    protected function createConversation(): ?string
    {
        if (!$this->systemPrompt) {
            return null;
        }

        $payload = [
            'items' => [
                [
                    'type' => 'message',
                    'role' => 'system',
                    'content' => $this->systemPrompt,
                ],
            ],
        ];

        $response = Http::withToken($this->apiKey)
            ->timeout(90)
            ->retry(2, 1000)
            ->post("{$this->baseUrl}/conversations", $payload);

        if ($response->failed()) {
            Log::error('UazapiOpenAIService createConversation failed', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);
            return null;
        }

        $conversationId = $response->json('id');
        if (!$conversationId) {
            Log::error('UazapiOpenAIService createConversation missing id');
            return null;
        }

        $this->conversationId = (string) $conversationId;
        $this->chat->conv_id = $this->conversationId;
        $this->chat->version = $this->assistant?->version;
        $this->chat->save();

        return $this->conversationId;
    }

    protected function createItems(string $conversationId, string $novoPrompt): bool
    {
        $payload = [
            'items' => [
                [
                    'type' => 'message',
                    'role' => 'system',
                    'content' => [
                        [
                            'type' => 'input_text',
                            'text' => "Novo contexto atualizado:\n\n{$novoPrompt}",
                        ],
                    ],
                ],
            ],
        ];

        $response = Http::withToken($this->apiKey)
            ->timeout(90)
            ->retry(2, 1000)
            ->post("{$this->baseUrl}/conversations/{$conversationId}/items", $payload);

        if ($response->failed()) {
            Log::error('UazapiOpenAIService createItems failed', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);
            return false;
        }

        return true;
    }

    protected function createResponse(array $input, string $model): ?string
    {
        if (!$this->apiKey) {
            return null;
        }

        $this->conversationId = $this->conversationId ?? $this->createConversation();
        if (!$this->conversationId) {
            return null;
        }

        $timezone = config('app.timezone', 'America/Sao_Paulo');
        $now = now($timezone);
        $dayName = $now->locale('pt_BR')->isoFormat('dddd');
        $date = $now->format('Y-m-d');
        $time = $now->format('H:i');
        $contactName = $this->chat?->nome ? "nome do cliente/contato: {$this->chat->nome}" : '';

        $input = array_merge([
            [
                'role' => 'system',
                'content' => "Agora: {$now->toIso8601String()} ({$dayName}, {$date} as {$time}, tz: {$timezone}).\n{$contactName}",
            ],
        ], $input);

        $tools = $this->buildTools();

        $payload = [
            'model' => $model,
            'input' => $input,
            'tools' => $tools,
            'conversation' => $this->conversationId,
        ];

        $response = $this->postResponse($payload);
        if (!$response) {
            return null;
        }

        $apiResponse = $response->json();

        if ($this->hasFunctionCall($apiResponse)) {
            return $this->submitFunctionCall($apiResponse);
        }

        return $this->extractAssistantMessage($apiResponse);
    }

    protected function buildTools(): array
    {
        $tools = [];

        if ($this->systemPrompt && str_contains($this->systemPrompt, 'notificar_adm')) {
            $tools[] = [
                'type' => 'function',
                'name' => 'notificar_adm',
                'description' => 'Notifica um administrador humano quando necessario.',
                'parameters' => [
                    'type' => 'object',
                    'properties' => [
                        'numeros_telefone' => [
                            'type' => 'array',
                            'items' => ['type' => 'string'],
                            'description' => 'Lista de numeros para notificar.',
                        ],
                        'mensagem' => [
                            'type' => 'string',
                            'description' => 'Mensagem a ser enviada.',
                        ],
                    ],
                    'required' => ['numeros_telefone', 'mensagem'],
                    'additionalProperties' => false,
                ],
                'strict' => true,
            ];
        }

        if ($this->systemPrompt && str_contains($this->systemPrompt, 'buscar_get')) {
            $tools[] = [
                'type' => 'function',
                'name' => 'buscar_get',
                'description' => 'Busca conteudo de uma URL.',
                'parameters' => [
                    'type' => 'object',
                    'properties' => [
                        'url' => [
                            'type' => 'string',
                            'description' => 'URL completa para buscar.',
                        ],
                    ],
                    'required' => ['url'],
                    'additionalProperties' => false,
                ],
                'strict' => true,
            ];
        }

        if ($this->systemPrompt && str_contains($this->systemPrompt, 'enviar_media')) {
            $tools[] = [
                'type' => 'function',
                'name' => 'enviar_media',
                'description' => 'Envia midia via URL.',
                'parameters' => [
                    'type' => 'object',
                    'properties' => [
                        'url' => [
                            'type' => 'string',
                            'description' => 'URL publica da midia.',
                        ],
                        'type' => [
                            'type' => 'string',
                            'description' => 'Tipo opcional da midia (image, video, document, audio, ptt, myaudio).',
                        ],
                        'text' => [
                            'type' => 'string',
                            'description' => 'Legenda opcional.',
                        ],
                        'docName' => [
                            'type' => 'string',
                            'description' => 'Nome do arquivo (documento).',
                        ],
                    ],
                    'required' => ['url'],
                    'additionalProperties' => false,
                ],
                'strict' => true,
            ];
        }

        if ($this->systemPrompt && str_contains($this->systemPrompt, 'registrar_info_chat')) {
            $tools[] = [
                'type' => 'function',
                'name' => 'registrar_info_chat',
                'description' => 'Registra informacoes do chat atual.',
                'parameters' => [
                    'type' => 'object',
                    'properties' => [
                        'nome' => [
                            'type' => 'string',
                            'description' => 'Nome do cliente ou contato.',
                        ],
                        'informacoes' => [
                            'type' => 'string',
                            'description' => 'Informacoes adicionais sobre o atendimento.',
                        ],
                        'aguardando_atendimento' => [
                            'type' => 'boolean',
                            'description' => 'Marca se aguarda atendimento humano.',
                        ],
                    ],
                    'required' => ['nome', 'informacoes'],
                    'additionalProperties' => false,
                ],
                'strict' => true,
            ];
        }

        if ($this->systemPrompt && str_contains($this->systemPrompt, 'gerenciar_agenda')) {
            $tools[] = [
                'type' => 'function',
                'name' => 'gerenciar_agenda',
                'description' => 'Gerencia agenda interna (nao suportado no Uazapi ainda).',
                'parameters' => [
                    'type' => 'object',
                    'properties' => [
                        'acao' => [
                            'type' => 'string',
                            'description' => 'Acao desejada.',
                        ],
                    ],
                    'required' => ['acao'],
                    'additionalProperties' => false,
                ],
                'strict' => true,
            ];
        }

        if ($this->systemPrompt && str_contains($this->systemPrompt, 'aplicar_tags')) {
            $tools[] = [
                'type' => 'function',
                'name' => 'aplicar_tags',
                'description' => 'Aplica tags ao chat (nao suportado no Uazapi ainda).',
                'parameters' => [
                    'type' => 'object',
                    'properties' => [
                        'tags' => [
                            'type' => 'array',
                            'items' => ['type' => 'string'],
                            'description' => 'Lista de tags.',
                        ],
                    ],
                    'required' => ['tags'],
                    'additionalProperties' => false,
                ],
                'strict' => true,
            ];
        }

        if ($this->systemPrompt && str_contains($this->systemPrompt, 'inscrever_sequencia')) {
            $tools[] = [
                'type' => 'function',
                'name' => 'inscrever_sequencia',
                'description' => 'Inscreve chat em sequencia (nao suportado no Uazapi ainda).',
                'parameters' => [
                    'type' => 'object',
                    'properties' => [
                        'sequence_id' => [
                            'type' => 'integer',
                            'description' => 'ID da sequencia.',
                        ],
                    ],
                    'required' => ['sequence_id'],
                    'additionalProperties' => false,
                ],
                'strict' => true,
            ];
        }

        if ($this->systemPrompt && str_contains($this->systemPrompt, 'enviar_post')) {
            $tools[] = [
                'type' => 'function',
                'name' => 'enviar_post',
                'description' => 'Envia evento para webhook externo.',
                'parameters' => [
                    'type' => 'object',
                    'properties' => [
                        'event' => [
                            'type' => 'string',
                            'description' => 'Tipo do evento.',
                        ],
                        'url' => [
                            'type' => 'string',
                            'description' => 'Endpoint do webhook.',
                        ],
                        'payload' => [
                            'type' => 'object',
                            'description' => 'Dados estruturados do evento.',
                        ],
                    ],
                    'required' => ['event', 'url', 'payload'],
                    'additionalProperties' => false,
                ],
                'strict' => true,
            ];
        }

        return $tools;
    }

    protected function postResponse(array $payload): ?\Illuminate\Http\Client\Response
    {
        $maxAttempts = 3;
        $attempt = 0;

        do {
            $attempt++;
            $response = Http::withToken($this->apiKey)
                ->timeout(90)
                ->retry(2, 1000)
                ->post("{$this->baseUrl}/responses", $payload);

            if ($response->successful()) {
                return $response;
            }

            $errorCode = $response->json('error.code');
            if (in_array($errorCode, ['conversation_locked', 'rate_limit_exceeded'], true)) {
                sleep(35);
            } else {
                return null;
            }
        } while ($attempt < $maxAttempts);

        return null;
    }

    protected function hasFunctionCall(array $apiResponse): bool
    {
        foreach ($apiResponse['output'] ?? [] as $output) {
            if (($output['type'] ?? null) === 'function_call') {
                return true;
            }
        }

        return false;
    }

    protected function submitFunctionCall(array $apiResponse): ?string
    {
        $toolOutputs = [];
        foreach ($apiResponse['output'] ?? [] as $output) {
            if (($output['type'] ?? null) !== 'function_call') {
                continue;
            }
            $toolOutput = $this->handleFunctionCall($output);
            if ($toolOutput === null) {
                return null;
            }
            $toolOutputs[] = $toolOutput;
        }

        if (empty($toolOutputs)) {
            return null;
        }

        $model = $this->assistant?->modelo ?: 'gpt-4.1-mini';
        $payload = [
            'model' => $model,
            'input' => $toolOutputs,
            'conversation' => $this->conversationId,
        ];

        $response = $this->postResponse($payload);
        if (!$response) {
            return null;
        }

        return $this->extractAssistantMessage($response->json());
    }

    protected function handleFunctionCall(array $functionCall): ?array
    {
        $functionName = $functionCall['name'] ?? null;
        $rawArguments = $functionCall['arguments'] ?? null;

        if (!$functionName || !is_string($functionName)) {
            return null;
        }

        if (is_array($rawArguments)) {
            $arguments = $rawArguments;
        } elseif (is_string($rawArguments) && $rawArguments !== '') {
            $arguments = json_decode($rawArguments, true);
        } else {
            $arguments = null;
        }

        if (!is_array($arguments)) {
            return null;
        }

        if ($functionName === 'enviar_media') {
            $url = $arguments['url'] ?? null;
            if (!is_string($url) || $url === '') {
                return null;
            }

            $type = $arguments['type'] ?? null;
            $text = $arguments['text'] ?? null;
            $docName = $arguments['docName'] ?? null;

            $this->enviarMedia($url, $type, $text, $docName);

            return [
                'type' => 'function_call_output',
                'call_id' => $functionCall['call_id'] ?? $functionCall['id'] ?? null,
                'output' => 'Midia enviada para a fila de envio.',
            ];
        }

        if ($functionName === 'notificar_adm') {
            $numeros = $arguments['numeros_telefone'] ?? null;
            $mensagem = $arguments['mensagem'] ?? null;
            if (!is_array($numeros) || $mensagem === null) {
                return null;
            }

            $this->notificarAdm($numeros, (string) $mensagem);

            return [
                'type' => 'function_call_output',
                'call_id' => $functionCall['call_id'] ?? $functionCall['id'] ?? null,
                'output' => 'Notificacao enviada.',
            ];
        }

        if ($functionName === 'buscar_get') {
            $url = $arguments['url'] ?? null;
            if (!is_string($url) || $url === '') {
                return null;
            }

            $resultado = $this->buscarGet($url);

            return [
                'type' => 'function_call_output',
                'call_id' => $functionCall['call_id'] ?? $functionCall['id'] ?? null,
                'output' => (string) $resultado,
            ];
        }

        if ($functionName === 'registrar_info_chat') {
            $this->registrarInfoChat(
                $arguments['nome'] ?? null,
                $arguments['informacoes'] ?? null,
                (bool) ($arguments['aguardando_atendimento'] ?? false)
            );

            return [
                'type' => 'function_call_output',
                'call_id' => $functionCall['call_id'] ?? $functionCall['id'] ?? null,
                'output' => 'Informacoes registradas.',
            ];
        }

        if ($functionName === 'enviar_post') {
            $event = $arguments['event'] ?? null;
            $url = $arguments['url'] ?? null;
            $payload = $arguments['payload'] ?? null;
            if (!is_string($event) || !is_string($url) || !is_array($payload)) {
                return null;
            }

            $resultado = $this->enviarPost($event, $url, $payload);

            return [
                'type' => 'function_call_output',
                'call_id' => $functionCall['call_id'] ?? $functionCall['id'] ?? null,
                'output' => (string) $resultado,
            ];
        }

        if (in_array($functionName, ['gerenciar_agenda', 'aplicar_tags', 'inscrever_sequencia'], true)) {
            return [
                'type' => 'function_call_output',
                'call_id' => $functionCall['call_id'] ?? $functionCall['id'] ?? null,
                'output' => 'Funcao nao suportada no Uazapi.',
            ];
        }

        return [
            'type' => 'function_call_output',
            'call_id' => $functionCall['call_id'] ?? $functionCall['id'] ?? null,
            'output' => 'Funcao nao suportada.',
        ];
    }

    protected function extractAssistantMessage(array $apiResponse): ?string
    {
        $lastOutput = $apiResponse['output'] ? end($apiResponse['output']) : null;

        if (
            is_array($lastOutput) &&
            ($lastOutput['type'] ?? null) !== null &&
            in_array($lastOutput['type'], ['message', 'output_text'], true) &&
            ($lastOutput['role'] ?? null) === 'assistant' &&
            isset($lastOutput['content'][0]['text'])
        ) {
            return $lastOutput['content'][0]['text'];
        }

        foreach (array_reverse($apiResponse['output'] ?? []) as $outputItem) {
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

    protected function registrarInfoChat(?string $nome, ?string $informacoes, bool $aguardando): bool
    {
        if (!$this->chat) {
            return false;
        }

        $currentInfo = trim((string) ($this->chat->informacoes ?? ''));
        $newInfo = trim((string) ($informacoes ?? ''));
        $timestamp = now(config('app.timezone', 'America/Sao_Paulo'))->format('d/m/Y H:i');

        if ($newInfo !== '') {
            $newInfo = "[{$timestamp}] " . $newInfo;
        }

        if ($currentInfo !== '' && $newInfo !== '') {
            $newInfo = $currentInfo . "\n" . $newInfo;
        } elseif ($currentInfo !== '') {
            $newInfo = $currentInfo;
        }

        $this->chat->update([
            'nome' => $nome,
            'informacoes' => $newInfo,
            'aguardando_atendimento' => $aguardando,
        ]);

        return true;
    }

    protected function enviarMedia(string $url, ?string $type = null, ?string $text = null, ?string $docName = null): bool
    {
        if (!$this->instance || !$this->chat) {
            return false;
        }

        $finalType = $type ? Str::lower((string) $type) : $this->resolveMediaType($url);
        $options = [];

        if (is_string($text) && trim($text) !== '') {
            $options['text'] = $text;
        }

        if ($finalType === 'document') {
            $docName = $docName ?: $this->extractFilename($url);
            if ($docName) {
                $options['docName'] = $docName;
            }
        }

        $uazapi = new UazapiService();
        $response = $uazapi->sendMedia($this->instance->token, $this->chat->phone, $finalType, $url, $options);

        if (!empty($response['error'])) {
            Log::error('UazapiOpenAIService enviarMedia failed', ['response' => $response]);
            return false;
        }

        return true;
    }

    protected function resolveMediaType(string $url): string
    {
        $lower = Str::lower($url);
        if (str_starts_with($lower, 'data:')) {
            if (str_starts_with($lower, 'data:image/')) {
                return 'image';
            }
            if (str_starts_with($lower, 'data:video/')) {
                return 'video';
            }
            if (str_starts_with($lower, 'data:audio/')) {
                return 'audio';
            }
            if (str_starts_with($lower, 'data:application/')) {
                return 'document';
            }
        }
        $ext = pathinfo(parse_url($lower, PHP_URL_PATH) ?? '', PATHINFO_EXTENSION);

        return match ($ext) {
            'jpg', 'jpeg', 'png', 'webp' => 'image',
            'mp4' => 'video',
            'mp3', 'ogg' => 'audio',
            'pdf', 'doc', 'docx', 'xls', 'xlsx' => 'document',
            default => 'document',
        };
    }

    protected function extractFilename(string $url): ?string
    {
        $path = parse_url($url, PHP_URL_PATH);
        if (!$path) {
            return null;
        }
        $name = basename($path);
        return $name !== '' ? $name : null;
    }

    protected function notificarAdm(array $numeros, string $mensagem): void
    {
        if (!$this->instance) {
            return;
        }

        $uazapi = new UazapiService();
        foreach ($numeros as $numero) {
            $numero = preg_replace('/\D/', '', (string) $numero);
            if ($numero === '') {
                continue;
            }
            $uazapi->sendText($this->instance->token, $numero, $mensagem);
        }
    }

    protected function buscarGet(string $url): string
    {
        try {
            $response = Http::timeout(10)
                ->withOptions(['verify' => false])
                ->get($url);

            if ($response->failed()) {
                Log::error('UazapiOpenAIService buscarGet failed', ['status' => $response->status()]);
                return 'Nao foi possivel obter conteudo da URL.';
            }

            $content = (string) $response->body();
            $headers = $response->headers();

            $content = $this->extrairTextoPlano($content, $headers);
            if (strlen($content) > 80000) {
                $content = substr($content, 0, 80000);
            }

            return trim($content);
        } catch (\Throwable $e) {
            Log::error('UazapiOpenAIService buscarGet exception', ['error' => $e->getMessage()]);
            return 'Erro ao buscar conteudo da URL.';
        }
    }

    protected function extrairTextoPlano(string $content, array $headers): string
    {
        if (!$this->respostaPareceHtml($content, $headers)) {
            return trim($content);
        }

        $content = str_ireplace(['<br>', '<br/>', '<br />'], "\n", $content);
        $content = preg_replace('#<li[^>]*>#i', "- ", $content);
        $content = preg_replace('#</(p|div|section|article|header|footer|main|aside|nav|li|ul|ol|h[1-6]|table|tr|td|th)>#i', "\n", $content);
        $content = preg_replace_callback(
            '#<a[^>]+href=[\'"]([^\'"]+)[\'"][^>]*>(.*?)</a>#is',
            function ($m) {
                $text = trim(strip_tags($m[2]));
                $href = trim($m[1]);
                return $text ? "{$text} ({$href})" : $href;
            },
            $content
        );
        $content = preg_replace('#<(script|style)[^>]*>.*?</\1>#is', ' ', $content);
        $text = strip_tags($content);
        $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $text = preg_replace('/[ \t]+/', ' ', $text);
        $text = preg_replace('/\n{2,}/', "\n", $text);

        return trim($text);
    }

    protected function respostaPareceHtml(string $content, array $headers): bool
    {
        foreach ($headers as $name => $values) {
            $headerName = is_string($name) ? strtolower($name) : '';
            if ($headerName === 'content-type') {
                $value = is_array($values) ? implode(';', $values) : (string) $values;
                if (stripos($value, 'text/html') !== false) {
                    return true;
                }
            }
        }

        return stripos($content, '<html') !== false || stripos($content, '<body') !== false;
    }

    protected function enviarPost(string $event, string $url, array $payload): string
    {
        $event = trim($event);
        $url = trim($url);

        if ($event === '' || $url === '') {
            return 'Dados invalidos para envio do evento.';
        }

        try {
            $response = Http::timeout(10)->post($url, [
                'event' => $event,
                'payload' => $payload,
            ]);

            if ($response->successful()) {
                return 'Evento enviado com sucesso.';
            }

            return 'Erro ao enviar o evento.';
        } catch (\Throwable $e) {
            Log::error('UazapiOpenAIService enviarPost exception', ['error' => $e->getMessage()]);
            return 'Erro ao enviar o evento.';
        }
    }

    protected function transcreverAudio(string $base64): ?string
    {
        $tmpPath = storage_path('app/tmp/');
        $originalAudioPath = $tmpPath . 'uazapi_audio.ogg';
        $convertedAudioPath = $tmpPath . 'uazapi_audio.mp3';

        try {
            if (!file_exists($tmpPath)) {
                mkdir($tmpPath, 0777, true);
            }

            file_put_contents($originalAudioPath, base64_decode($base64));

            $result = Process::run("ffmpeg -i {$originalAudioPath} -acodec libmp3lame -q:a 2 {$convertedAudioPath} -y");
            if (!$result->successful()) {
                Log::error('UazapiOpenAIService ffmpeg failed', [
                    'exit_code' => $result->exitCode(),
                    'error' => $result->errorOutput(),
                ]);
                return null;
            }

            $response = Http::withToken($this->apiKey)
                ->timeout(90)
                ->retry(2, 1000)
                ->asMultipart()
                ->post('https://api.openai.com/v1/audio/transcriptions', [
                    'file' => fopen($convertedAudioPath, 'r'),
                    'model' => 'whisper-1',
                    'language' => 'pt',
                ]);

            if ($response->successful()) {
                return $response->json('text');
            }
        } catch (\Throwable $e) {
            Log::error('UazapiOpenAIService transcribe failed', [
                'error' => $e->getMessage(),
            ]);
        } finally {
            if (file_exists($originalAudioPath)) {
                unlink($originalAudioPath);
            }
            if (file_exists($convertedAudioPath)) {
                unlink($convertedAudioPath);
            }
        }

        return null;
    }
}
