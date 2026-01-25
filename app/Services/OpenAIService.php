<?php

namespace App\Services;

use App\Models\Assistant;
use App\Models\AssistantLead;
use App\Models\ClienteLead;
use App\Models\Conexao;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use App\Services\UazapiService;
use OpenAI\Contracts\ClientContract;
use OpenAI\Factory;

class OpenAIService
{
    protected ClientContract $client;
    protected string $apiKey;
    protected Conexao $conexao;
    protected string $baseUrl = 'https://api.openai.com/v1';

    public function __construct(Conexao $conexao)
    {
        $this->conexao = $conexao;
        $credential = $conexao->credential;
        $token = $credential?->token;

        if (empty($token) || $token === '******') {
            Log::channel('openai')->error('OpenAIService credential missing token', [
                'conexao_id' => $conexao->id,
                'credential_id' => $credential?->id,
            ]);
            throw new \RuntimeException('Token da OpenAI não configurado na credencial vinculada.');
        }

        $this->apiKey = $token;
        $this->client = (new Factory())->create([
            'api_key' => $this->apiKey,
            'base_uri' => $this->baseUrl,
        ]);
    }

    public function client(): ClientContract
    {
        return $this->client;
    }

    public function apiKey(): string
    {
        return $this->apiKey;
    }

    public function conexao(): Conexao
    {
        return $this->conexao;
    }

    public function createConversation(): ?string
    {
        // Cria uma conversa nova usando o contexto do assistente vinculado à conexão.
        $assistant = $this->conexao->assistant;
        if (!$assistant) {
            Log::channel('openai')->error('OpenAIService createConversation missing assistant', [
                'conexao_id' => $this->conexao->id,
                'assistant_id' => $this->conexao->assistant_id,
            ]);
            return null;
        }

        $systemPrompt = $this->buildSystemPrompt($assistant);
        if ($systemPrompt === '') {
            Log::channel('openai')->warning('OpenAIService createConversation empty system prompt', [
                'conexao_id' => $this->conexao->id,
                'assistant_id' => $assistant->id,
            ]);
        }

        $payload = [
            'items' => [
                [
                    'type' => 'message',
                    'role' => 'system',
                    'content' => $systemPrompt,
                ],
            ],
        ];

        $response = Http::withToken($this->apiKey)
            ->timeout(90)
            ->retry(2, 1000)
            ->post("{$this->baseUrl}/conversations", $payload);

        if ($response->failed()) {
            Log::channel('openai')->error('OpenAIService createConversation failed', [
                'conexao_id' => $this->conexao->id,
                'assistant_id' => $assistant->id,
                'status' => $response->status(),
                'body' => $response->body(),
            ]);
            return null;
        }

        $conversationId = $response->json('id');
        if (!$conversationId) {
            Log::channel('openai')->error('OpenAIService createConversation missing id', [
                'conexao_id' => $this->conexao->id,
                'assistant_id' => $assistant->id,
            ]);
            return null;
        }

        return (string) $conversationId;
    }

    public function handle(array $payload): void
    {
        // Encaminha o payload para geração de resposta via OpenAI e envia ao WhatsApp.
        $response = $this->createResponse($payload);
        if (!$response) {
            Log::channel('openai')->warning('OpenAIService handle returned empty response', [
                'conexao_id' => $this->conexao->id,
            ]);
            return;
        }

        $assistantText = $this->extractAssistantMessage($response);
        if (!$assistantText) {
            Log::channel('openai')->warning('OpenAIService handle missing assistant text', [
                'conexao_id' => $this->conexao->id,
            ]);
            return;
        }

        $phone = Arr::get($payload, 'phone');
        $token = $this->conexao->whatsapp_api_key;
        if (!$phone || !$token) {
            Log::channel('openai')->warning('OpenAIService handle missing phone or token', [
                'conexao_id' => $this->conexao->id,
            ]);
            return;
        }

        $uazapi = new UazapiService();
        $sendResult = $uazapi->sendText($token, $phone, $assistantText);
        if (!empty($sendResult['error'])) {
            Log::channel('openai')->error('OpenAIService sendText failed', [
                'conexao_id' => $this->conexao->id,
                'response' => $sendResult,
            ]);
        }
    }

    protected function buildSystemPrompt(Assistant $assistant): string
    {
        // Concatena os prompts configurados no assistente para servir de contexto inicial.
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

    public function createResponse(array $payload): ?array
    {
        // Monta o input e envia a requisição para o endpoint /responses da OpenAI.
        $assistant = $this->conexao->assistant;
        if (!$assistant) {
            Log::channel('openai')->error('OpenAIService createResponse missing assistant', [
                'conexao_id' => $this->conexao->id,
                'assistant_id' => $this->conexao->assistant_id,
            ]);
            return null;
        }

        $phone = Arr::get($payload, 'phone');
        $lead = $this->resolveClienteLead($phone);
        $assistantLead = $lead ? $this->resolveAssistantLead($lead) : null;

        if (!$assistantLead || empty($assistantLead->conv_id)) {
            Log::channel('openai')->error('OpenAIService createResponse missing assistant_lead conv_id', [
                'conexao_id' => $this->conexao->id,
                'assistant_id' => $this->conexao->assistant_id,
                'lead_id' => $lead?->id,
            ]);
            return null;
        }

        $input = $this->buildInput($payload);
        if (empty($input)) {
            Log::channel('openai')->warning('OpenAIService createResponse empty input', [
                'conexao_id' => $this->conexao->id,
            ]);
            return null;
        }

        $input = $this->prependSystemContext($lead, $input);
        $model = $assistant->modelo ?: 'gpt-4.1-mini';

        $requestPayload = [
            'model' => $model,
            'input' => $input,
            'conversation' => $assistantLead->conv_id,
        ];

        $response = Http::withToken($this->apiKey)
            ->timeout(90)
            ->retry(2, 1000)
            ->post("{$this->baseUrl}/responses", $requestPayload);

        if ($response->failed()) {
            Log::channel('openai')->error('OpenAIService createResponse failed', [
                'conexao_id' => $this->conexao->id,
                'assistant_id' => $assistant->id,
                'status' => $response->status(),
                'body' => $response->body(),
            ]);
            return null;
        }

        return $response->json();
    }

    protected function buildInput(array $payload): array
    {
        // Constrói o array de input com texto e mídia, já tratando áudio via transcrição.
        $tipo = Str::lower((string) ($payload['tipo'] ?? 'text'));
        $message = is_array($payload['message'] ?? null) ? $payload['message'] : [];
        $media = is_array($payload['media'] ?? null) ? $payload['media'] : null;

        $text = $payload['combined_text']
            ?? ($message['text'] ?? null)
            ?? ($message['content'] ?? null);
        $text = is_string($text) ? trim($text) : '';

        if (str_contains($tipo, 'audio')) {
            if (!$media || empty($media['base64'])) {
                Log::channel('openai')->warning('OpenAIService audio without media payload');
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

        if (str_contains($tipo, 'image')) {
            if (!$media || empty($media['base64'])) {
                Log::channel('openai')->warning('OpenAIService image without media payload');
                return [];
            }

            $caption = $text !== '' ? $text : 'Imagem enviada.';
            $mimetype = $media['mimetype'] ?? 'image/jpeg';

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
                            'image_url' => "data:{$mimetype};base64,{$media['base64']}",
                        ],
                    ],
                ],
            ];
        }

        if (str_contains($tipo, 'video')) {
            if ($text !== '') {
                return [
                    [
                        'role' => 'user',
                        'content' => $text,
                    ],
                ];
            }

            Log::channel('openai')->warning('OpenAIService video not supported without caption');
            return [];
        }

        if (str_contains($tipo, 'document')) {
            if (!$media || empty($media['base64'])) {
                Log::channel('openai')->warning('OpenAIService document without media payload');
                return [];
            }

            if (!$this->isPdfMedia($media, $message)) {
                Log::channel('openai')->warning('OpenAIService document not supported (only PDF)', [
                    'mimetype' => $media['mimetype'] ?? null,
                    'filename' => Arr::get($message, 'content.fileName'),
                ]);
                return [];
            }

            $caption = $text !== '' ? $text : 'Documento enviado.';
            $filename = $this->resolvePdfFilename($message);
            $mimetype = $media['mimetype'] ?? 'application/pdf';

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
                            'file_data' => "data:{$mimetype};base64,{$media['base64']}",
                        ],
                    ],
                ],
            ];
        }

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

    protected function prependSystemContext(?ClienteLead $lead, array $input): array
    {
        // Injeta contexto temporal e nome do contato no início do input.
        $timezone = config('app.timezone', 'America/Sao_Paulo');
        $now = now($timezone);
        $dayName = $now->locale('pt_BR')->isoFormat('dddd');
        $date = $now->format('Y-m-d');
        $time = $now->format('H:i');
        $contactName = $lead?->name ? "nome do cliente/contato: {$lead->name}" : '';

        return array_merge([
            [
                'role' => 'system',
                'content' => "Agora: {$now->toIso8601String()} ({$dayName}, {$date} as {$time}, tz: {$timezone}).\n{$contactName}",
            ],
        ], $input);
    }

    protected function resolveClienteLead(?string $phone): ?ClienteLead
    {
        if (!$phone) {
            return null;
        }

        return ClienteLead::where('cliente_id', $this->conexao->cliente_id)
            ->where('phone', $phone)
            ->first();
    }

    protected function resolveAssistantLead(ClienteLead $lead): ?AssistantLead
    {
        if (!$this->conexao->assistant_id) {
            return null;
        }

        return AssistantLead::where('lead_id', $lead->id)
            ->where('assistant_id', $this->conexao->assistant_id)
            ->first();
    }

    protected function resolvePdfFilename(array $message): string
    {
        $filename = (string) Arr::get($message, 'content.fileName', 'documento.pdf');
        $filename = trim($filename);
        if ($filename === '') {
            $filename = 'documento.pdf';
        }

        if (!Str::endsWith(Str::lower($filename), '.pdf')) {
            $filename .= '.pdf';
        }

        return $filename;
    }

    protected function isPdfMedia(array $media, array $message): bool
    {
        $mimetype = Str::lower((string) ($media['mimetype'] ?? ''));
        if ($mimetype === 'application/pdf') {
            return true;
        }

        $filename = (string) Arr::get($message, 'content.fileName');
        return $filename !== '' && Str::endsWith(Str::lower($filename), '.pdf');
    }

    protected function extractAssistantMessage(array $apiResponse): ?string
    {
        // Extrai a última mensagem do assistente retornada pelo endpoint /responses.
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

    public function transcreverAudio(string $base64, string $filename = 'audio'): ?string
    {
        // Converte o áudio base64 em arquivo temporário e usa a API de transcrição da OpenAI.
        $tmpPath = storage_path('app/tmp/');
        $suffix = uniqid('', true);
        $originalAudioPath = $tmpPath . $filename . '_' . $suffix . '.ogg';
        $convertedAudioPath = $tmpPath . $filename . '_' . $suffix . '.mp3';

        try {
            // Garante que o diretório temporário existe antes de gravar os arquivos.
            if (!file_exists($tmpPath)) {
                mkdir($tmpPath, 0777, true);
            }

            // Salva o áudio original recebido em base64.
            file_put_contents($originalAudioPath, base64_decode($base64));

            // Converte para MP3 usando ffmpeg via Process.
            $result = Process::run("ffmpeg -i {$originalAudioPath} -acodec libmp3lame -q:a 2 {$convertedAudioPath} -y");
            if (!$result->successful()) {
                Log::channel('openai')->error('OpenAIService transcreverAudio ffmpeg failed', [
                    'exit_code' => $result->exitCode(),
                    'error' => $result->errorOutput(),
                ]);
                return null;
            }

            // Chama o endpoint de transcrição da OpenAI.
            $response = Http::withToken($this->apiKey)
                ->timeout(90)
                ->retry(2, 1000)
                ->asMultipart()
                ->post("{$this->baseUrl}/audio/transcriptions", [
                    'file' => fopen($convertedAudioPath, 'r'),
                    'model' => 'whisper-1',
                    'language' => 'pt',
                ]);

            if ($response->successful()) {
                return $response->json('text');
            }

            Log::channel('openai')->error('OpenAIService transcreverAudio failed', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            return null;
        } catch (\Throwable $e) {
            Log::channel('openai')->error('OpenAIService transcreverAudio exception', [
                'error' => $e->getMessage(),
            ]);
            return null;
        } finally {
            // Limpa os arquivos temporários para evitar acúmulo no disco.
            if (file_exists($originalAudioPath)) {
                unlink($originalAudioPath);
            }
            if (file_exists($convertedAudioPath)) {
                unlink($convertedAudioPath);
            }
        }
    }
}
