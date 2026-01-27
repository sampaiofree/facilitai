<?php

namespace App\Jobs;

use App\Models\Assistant;
use App\Models\AssistantLead;
use App\Models\ClienteLead;
use App\Models\Conexao;
use App\Services\IAOrchestratorService;
use App\Services\UazapiService;
use App\DTOs\IAResult;
use App\Support\LogContext;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class ProcessIncomingMessageJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1;
    public int $timeout = 300;

    protected int $conexaoId;
    protected ?int $clienteLeadId;
    protected array $payload;
    protected ?string $cacheKey;
    protected bool $isMedia;
    protected int $debounceSeconds;
    protected int $maxWaitSeconds;

    protected ?Conexao $conexao = null;
    protected ?ClienteLead $clienteLead = null;

    public function __construct(int $conexaoId, ?int $clienteLeadId, array $payload, ?string $cacheKey = null, bool $isMedia = false, int $debounceSeconds = 5, int $maxWaitSeconds = 40)
    {
        $this->conexaoId = $conexaoId;
        $this->clienteLeadId = $clienteLeadId;
        $this->payload = $payload;
        $this->cacheKey = $cacheKey;
        $this->isMedia = $isMedia;
        $this->debounceSeconds = $debounceSeconds;
        $this->maxWaitSeconds = $maxWaitSeconds;
    }

    public function handle(): void
    {
        $conexao = $this->loadConexao();
        if (!$conexao) {
            return;
        }

        if (!empty($this->cacheKey)) {
            $this->handleDebounce();
            return;
        }

        $phone = (string) ($this->payload['phone'] ?? '');
        if ($phone === '') {
            return;
        }

        if (($this->payload['is_group'] ?? false) === true) {
            return;
        }

        $assistant = $conexao->assistant;
        if (!$assistant) {
            Log::channel('process_job')->warning('Assistente não encontrado para conexão.', $this->logContext([
                'conexao_id' => $conexao->id,
            ]));
            return;
        }

        $leadName = (string) ($this->payload['lead_name'] ?? $phone);
        $lead = $this->resolveClienteLead($phone, $leadName);
        if (!$lead) {
            Log::channel('process_job')->warning('Falha ao criar/atualizar ClienteLead.', $this->logContext([
                'conexao_id' => $conexao->id,
                'phone' => $phone,
            ]));
            return;
        }
        $this->clienteLead = $lead;
        $this->clienteLeadId = $lead->id;

        $fromMe = ($this->payload['from_me'] ?? false) === true;
        $text = (string) ($this->payload['text'] ?? '');

        if ($fromMe) {
            $botEnabled = str_contains($text, '#');
            $lead->bot_enabled = $botEnabled;
            $lead->save();
            return;
        }

        if (!$lead->bot_enabled) {
            return;
        }

        $tipo = Str::lower((string) ($this->payload['tipo'] ?? 'text'));
        if (str_contains($tipo, 'video')) {
            $this->sendText($conexao->whatsapp_api_key, $phone, 'Nao consegui ler o video. Se possivel, envie uma descricao do que aparece nele.', [
                'conexao_id' => $conexao->id,
                'assistant_id' => $assistant->id,
                'lead_id' => $lead->id,
                'phone' => $phone,
            ]);
            return;
        }

        $systemPrompt = $this->buildSystemPrompt($assistant);
        $assistantLead = $this->ensureAssistantLead($lead, $assistant);
        if (!$assistantLead) {
            Log::channel('process_job')->warning('Falha ao resolver AssistantLead.', $this->logContext([
                'conexao_id' => $conexao->id,
                'assistant_id' => $assistant->id,
                'lead_id' => $lead->id,
            ]));
            return;
        }
        $this->persistAssistantLeadWebhookPayload($assistantLead);

        $payload = $this->payload;
        $payload['conexao_id'] = $conexao->id;
        $payload['assistant_id'] = $assistant->id;
        $payload['assistant_lead_id'] = $assistantLead->id;
        $payload['lead_id'] = $lead->id;
        $payload['conversation_id'] = $assistantLead->conv_id;
        $payload['assistant_model'] = $assistant->modelo ?: 'gpt-4.1-mini';
        $payload['contact_name'] = $lead->name ?: $leadName;
        $payload['system_prompt'] = $systemPrompt;

        $tipo = Str::lower((string) ($payload['tipo'] ?? 'text'));
        if ($tipo !== 'text') {
            $payload['is_media'] = true;
            $this->sendIAResponse($payload, $assistant, $lead, $assistantLead);
            return;
        }

        if (!$this->cacheDisponivel()) {
            $payload['is_media'] = false;
            $this->sendIAResponse($payload, $assistant, $lead, $assistantLead);
            return;
        }

        $cacheKey = "debounce:{$lead->id}:{$assistant->id}";
        $buffer = Cache::get($cacheKey, []);
        $agora = Carbon::now()->timestamp;

        if (empty($buffer)) {
            $buffer = [
                'started_at' => $agora,
                'last_at' => $agora,
                'messages' => [$text],
                'data' => $payload,
            ];
        } else {
            $buffer['last_at'] = $agora;
            $buffer['messages'][] = $text;
            $buffer['messages'] = array_slice($buffer['messages'], -10);
            $buffer['data'] = $payload;
        }

        Cache::put($cacheKey, $buffer, now()->addSeconds(120));

        self::dispatch($this->conexaoId, $this->clienteLeadId, $payload, $cacheKey, false, $this->debounceSeconds, $this->maxWaitSeconds)
            ->delay(now()->addSeconds($this->debounceSeconds))->onQueue('processarconversa');
    }

    private function handleDebounce(): void
    {
        $conexao = $this->loadConexao();
        if (!$conexao) {
            return;
        }

        $buffer = Cache::get($this->cacheKey);
        if (empty($buffer) || empty($buffer['messages'])) {
            return;
        }

        $now = Carbon::now();
        $lastAt = Carbon::createFromTimestamp($buffer['last_at'] ?? $now->timestamp);
        $startedAt = Carbon::createFromTimestamp($buffer['started_at'] ?? $now->timestamp);

        if ($lastAt->gt($now->subSeconds($this->debounceSeconds)) && $startedAt->gt($now->subSeconds($this->maxWaitSeconds))) {
            self::dispatch($this->conexaoId, $this->clienteLeadId, $this->payload, $this->cacheKey, $this->isMedia, $this->debounceSeconds, $this->maxWaitSeconds)
                ->delay(now()->addSeconds($this->debounceSeconds))->onQueue('processarconversa');
            return;
        }

        $combined = implode("\n", $buffer['messages']);
        $payload = is_array($buffer['data'] ?? null) ? $buffer['data'] : $this->payload;
        $payload['is_media'] = $this->isMedia;
        $payload['text'] = $combined;

        Cache::forget($this->cacheKey);

        $assistant = $conexao->assistant;
        if (!$assistant) {
            return;
        }

        $lead = $this->loadLeadFromPayload($payload);
        if (!$lead) {
            return;
        }

        $assistantLead = $this->ensureAssistantLead($lead, $assistant);
        if (!$assistantLead) {
            return;
        }
        $this->persistAssistantLeadWebhookPayload($assistantLead);

        $this->sendIAResponse($payload, $assistant, $lead, $assistantLead);
    }

    private function loadConexao(): ?Conexao
    {
        if ($this->conexao) {
            return $this->conexao;
        }

        $conexao = Conexao::with(['cliente', 'assistant', 'credential.iaplataforma'])->find($this->conexaoId);
        if (!$conexao) {
            Log::channel('process_job')->error('Conexao não encontrada para ProcessIncomingMessageJob.', $this->logContext([
                'conexao_id' => $this->conexaoId,
            ]));
            return null;
        }

        $this->conexao = $conexao;
        if ($this->clienteLeadId) {
            $this->clienteLead = ClienteLead::find($this->clienteLeadId);
        }

        return $conexao;
    }

    private function resolveClienteLead(string $phone, string $leadName): ?ClienteLead
    {
        $lead = $this->clienteLead;
        if (!$lead) {
            $lead = ClienteLead::where('cliente_id', $this->conexao->cliente_id)
                ->where('phone', $phone)
                ->first();
        }

        if (!$lead) {
            $lead = ClienteLead::create([
                'cliente_id' => $this->conexao->cliente_id,
                'phone' => $phone,
                'name' => $leadName,
                'info' => null,
                'bot_enabled' => true,
            ]);

            return $lead;
        }

        $lead->fill([
            'name' => $leadName,
            'info' => $lead->info ?? null,
        ]);
        if ($lead->isDirty()) {
            $lead->save();
        }

        return $lead;
    }

    private function ensureAssistantLead(ClienteLead $lead, Assistant $assistant): ?AssistantLead
    {
        // Garante que o assistente existe antes de criar o relacionamento.
        if (!$assistant->id || !Assistant::whereKey($assistant->id)->exists()) {
            Log::channel('process_job')->warning('Assistente inválido para AssistantLead.', $this->logContext([
                'assistant_id' => $assistant->id ?? null,
                'lead_id' => $lead->id,
                'conexao_id' => $this->conexao?->id,
            ]));
            return null;
        }

        // Garante que o lead existe antes de criar o relacionamento.
        if (!$lead->id || !ClienteLead::whereKey($lead->id)->exists()) {
            Log::channel('process_job')->warning('Lead inválido para AssistantLead.', $this->logContext([
                'assistant_id' => $assistant->id ?? null,
                'lead_id' => $lead->id ?? null,
                'conexao_id' => $this->conexao?->id,
            ]));
            return null;
        }

        $assistantLead = AssistantLead::where('lead_id', $lead->id)
            ->where('assistant_id', $assistant->id)
            ->first();

        if ($assistantLead) {
            return $assistantLead;
        }

        return AssistantLead::create([
            'lead_id' => $lead->id,
            'assistant_id' => $assistant->id,
            'version' => $assistant->version ?? 1,
            'conv_id' => null,
        ]);
    }

    private function loadLeadFromPayload(array $payload): ?ClienteLead
    {
        $leadId = $payload['lead_id'] ?? null;
        if ($leadId) {
            $lead = ClienteLead::find($leadId);
            if ($lead) {
                return $lead;
            }
        }

        $phone = (string) ($payload['phone'] ?? '');
        $leadName = (string) ($payload['lead_name'] ?? $phone);
        if ($phone === '') {
            return null;
        }

        return $this->resolveClienteLead($phone, $leadName);
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

        $parts = array_filter($parts, function ($value) {
            return is_string($value) && trim($value) !== '';
        });

        return trim(implode("\n", $parts));
    }

    private function sendIAResponse(array $payload, Assistant $assistant, ClienteLead $lead, AssistantLead $assistantLead): void
    {
        $idempotencyKey = $this->buildIdempotencyKey($payload);
        if ($idempotencyKey && Cache::has($idempotencyKey)) {
            return;
        }

        $phone = (string) ($payload['phone'] ?? '');
        $token = $this->conexao?->whatsapp_api_key;

        if ($token && $phone !== '') {
            try {
                $presenceResult = (new UazapiService())->messagePresence($token, $phone);

                if (!empty($presenceResult['error'])) {
                    Log::channel('process_job')->warning('Falha ao enviar presence via Uazapi.', $this->logContext([
                        'response' => $presenceResult,
                    ]));
                }
            } catch (\Throwable $exception) {
                Log::channel('process_job')->error('Erro inesperado ao chamar messagePresence.', [
                    'error' => $exception->getMessage(),
                    'assistant_lead_id' => $assistantLead->id,
                    'conexao_id' => $this->conexao?->id,
                ]);
            }
        }

        $logContext = array_filter([
            'conexao_id' => $payload['conexao_id'] ?? $this->conexao?->id,
            'assistant_id' => $payload['assistant_id'] ?? null,
            'lead_id' => $payload['lead_id'] ?? null,
            'phone' => $phone,
        ], function ($value) {
            return $value !== null && $value !== '';
        });

        $handlers = $this->buildToolHandlers($payload, $this->conexao, $this->clienteLead);
        $orchestrator = new IAOrchestratorService();
        $result = $orchestrator->handleMessage($this->conexao, $assistant, $lead, $assistantLead, $payload, $handlers);
        $this->persistAssistantLeadResponse($assistantLead, $result);

        if (!$result->ok || !is_string($result->text) || trim($result->text) === '') {
            Log::channel('process_job')->warning('IAOrchestratorService sem resposta final.', $this->logContext(array_merge($logContext, [
                'provider' => $result->provider,
                'error' => $result->error,
            ])));
            return;
        }

        $this->sendText($token, $phone, $result->text, $logContext);

        if ($idempotencyKey) {
            Cache::put($idempotencyKey, true, now()->addHours($this->idempotencyTtlHours()));
        }
    }

    private function persistAssistantLeadWebhookPayload(AssistantLead $assistantLead): void
    {
        try {
            $assistantLead->update([
                'webhook_payload' => $this->payload,
            ]);
        } catch (\Throwable $exception) {
            Log::channel('process_job')->error('Falha ao salvar webhook_payload.', $this->logContext([
                'assistant_lead_id' => $assistantLead->id,
                'error' => $exception->getMessage(),
            ]));
        }
    }

    private function persistAssistantLeadResponse(AssistantLead $assistantLead, IAResult $result): void
    {
        try {
            $assistantLead->update([
                'assistant_response' => $this->serializeIAResult($result),
                'job_message' => $this->buildJobMessageFromResult($result),
            ]);
        } catch (\Throwable $exception) {
            Log::channel('process_job')->error('Falha ao salvar resposta da IA.', $this->logContext([
                'assistant_lead_id' => $assistantLead->id,
                'error' => $exception->getMessage(),
            ]));
        }
    }

    private function serializeIAResult(IAResult $result): array
    {
        return [
            'ok' => $result->ok,
            'text' => $result->text,
            'provider' => $result->provider,
            'error' => $result->error,
            'raw' => $result->raw,
        ];
    }

    private function buildJobMessageFromResult(IAResult $result): string
    {
        $payload = [
            'status' => $result->ok ? 'concluido' : 'erro',
            'provider' => $result->provider,
            'text' => $result->text,
            'error' => $result->error,
            'raw' => $result->raw,
        ];

        return json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    private function sendText(?string $token, string $phone, string $message, array $logContext = []): void
    {
        if (!$token || $phone === '') {
            Log::channel('process_job')->warning('Token ou telefone ausente para envio da resposta.', $this->logContext($logContext));
            return;
        }

        $uazapi = new UazapiService();
        $sendResult = $uazapi->sendText($token, $phone, $message);
        if (!empty($sendResult['error'])) {
            Log::channel('process_job')->error('Falha ao enviar mensagem via Uazapi.', $this->logContext(array_merge($logContext, [
                'response' => $sendResult,
            ])));
            $this->throwTransient('Falha ao enviar mensagem via Uazapi.', $logContext);
        }
    }

    private function buildIdempotencyKey(array $payload): ?string
    {
        $assistantLeadId = $payload['assistant_lead_id'] ?? null;
        if (!$assistantLeadId) {
            return null;
        }

        $aggregatedText = (string) ($payload['text'] ?? '');
        $mediaSignature = $this->buildMediaSignature($payload['media'] ?? null);

        $payloadHash = hash('sha256', json_encode([
            (int) $assistantLeadId,
            $aggregatedText,
            $mediaSignature,
        ]));

        return "resp:{$assistantLeadId}:{$payloadHash}";
    }

    private function buildMediaSignature($media): ?string
    {
        if (!is_array($media) || empty($media)) {
            return null;
        }

        $base64Hash = null;
        if (!empty($media['base64']) && is_string($media['base64'])) {
            $base64Hash = hash('sha256', $media['base64']);
        }

        return json_encode([
            'type' => $media['type'] ?? null,
            'mimetype' => $media['mimetype'] ?? null,
            'filename' => $media['filename'] ?? null,
            'size_bytes' => $media['size_bytes'] ?? null,
            'storage_key' => $media['storage_key'] ?? null,
            'base64_hash' => $base64Hash,
        ]);
    }

    private function idempotencyTtlHours(): int
    {
        $value = (int) env('IDEMPOTENCY_TTL_HOURS', 6);
        return $value > 0 ? $value : 6;
    }

    private function buildToolHandlers(array $payload, ?Conexao $conexao, ?ClienteLead $lead): array
    {
        $token = $conexao?->whatsapp_api_key;
        $phone = $payload['phone'] ?? null;

        return [
            'enviar_media' => function (array $arguments, array $context) use ($token, $phone) {
                return $this->handleEnviarMedia($token, $phone, $arguments);
            },
            'notificar_adm' => function (array $arguments, array $context) use ($token) {
                return $this->handleNotificarAdm($token, $arguments);
            },
            'buscar_get' => function (array $arguments, array $context) {
                return $this->handleBuscarGet($arguments);
            },
            'registrar_info_chat' => function (array $arguments, array $context) use ($lead) {
                return $this->handleRegistrarInfoLead($lead, $arguments);
            },
            'enviar_post' => function (array $arguments, array $context) use ($payload, $conexao) {
                return $this->handleEnviarPost($arguments, $payload, $conexao);
            },
            'gerenciar_agenda' => function (array $arguments, array $context) {
                return 'Funcao nao suportada no Uazapi.';
            },
            'aplicar_tags' => function (array $arguments, array $context) {
                return 'Funcao nao suportada no Uazapi.';
            },
            'inscrever_sequencia' => function (array $arguments, array $context) {
                return 'Funcao nao suportada no Uazapi.';
            },
        ];
    }

    private function handleEnviarMedia(?string $token, ?string $phone, array $arguments): string
    {
        $url = $arguments['url'] ?? null;
        if (!is_string($url) || $url === '') {
            return 'URL inválida para envio de mídia.';
        }

        if (!$token || !$phone) {
            return 'Token ou telefone ausente para envio de mídia.';
        }

        $type = $arguments['type'] ?? null;
        $text = $arguments['text'] ?? null;
        $docName = $arguments['docName'] ?? null;

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
        $response = $uazapi->sendMedia($token, $phone, $finalType, $url, $options);

        if (!empty($response['error'])) {
            Log::channel('process_job')->error('Falha ao enviar mídia via Uazapi.', $this->logContext([
                'response' => $response,
            ]));
            return 'Falha ao enviar mídia.';
        }

        return 'Midia enviada para a fila de envio.';
    }

    private function resolveMediaType(string $url): string
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
                return 'ptt';
            }
            if (str_starts_with($lower, 'data:application/')) {
                return 'document';
            }
        }

        $ext = pathinfo(parse_url($lower, PHP_URL_PATH) ?? '', PATHINFO_EXTENSION);

        return match ($ext) {
            'jpg', 'jpeg', 'png', 'webp' => 'image',
            'mp4' => 'video',
            'mp3', 'ogg' => 'ptt',
            'pdf', 'doc', 'docx', 'xls', 'xlsx' => 'document',
            default => 'document',
        };
    }

    private function extractFilename(string $url): ?string
    {
        $path = parse_url($url, PHP_URL_PATH);
        if (!$path) {
            return null;
        }

        $name = basename($path);
        return $name !== '' ? $name : null;
    }

    private function handleNotificarAdm(?string $token, array $arguments): string
    {
        $numeros = $arguments['numeros_telefone'] ?? null;
        $mensagem = $arguments['mensagem'] ?? null;

        if (!is_array($numeros) || !is_string($mensagem)) {
            return 'Parâmetros inválidos para notificar administrador.';
        }

        if (!$token) {
            return 'Token ausente para notificar administrador.';
        }

        $uazapi = new UazapiService();
        foreach ($numeros as $numero) {
            $numero = preg_replace('/\D/', '', (string) $numero);
            if ($numero === '') {
                continue;
            }
            $uazapi->sendText($token, $numero, $mensagem);
        }

        return 'Notificação enviada para o administrador.';
    }

    private function handleBuscarGet(array $arguments): string
    {
        $url = $arguments['url'] ?? null;
        if (!is_string($url) || $url === '') {
            return 'URL inválida para busca.';
        }

        try {
            $response = Http::timeout(10)
                ->withOptions(['verify' => false])
                ->get($url);

            if ($response->failed()) {
                Log::channel('process_job')->error('Falha ao buscar URL.', $this->logContext([
                    'status' => $response->status(),
                ]));
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
            Log::channel('process_job')->error('Erro em buscar_get', $this->logContext([
                'error' => $e->getMessage(),
            ]));
            return 'Erro ao buscar conteudo da URL.';
        }
    }

    private function extrairTextoPlano(string $content, array $headers): string
    {
        if (!$this->respostaPareceHtml($content, $headers)) {
            return trim($content);
        }

        $content = str_ireplace(['<br>', '<br/>', '<br />'], "\n", $content);
        $content = preg_replace('#<li[^>]*>#i', "- ", $content);
        $content = preg_replace('#</(p|div|section|article|header|footer|main|aside|nav|li|ul|ol|h[1-6]|table|tr|td|th)>#i', "\n", $content);
        $content = preg_replace_callback(
            '#<a[^>]+href=[\'"]([^\'"]+)[\'"][^>]*>(.*?)</a>#is',
            function ($matches) {
                $text = trim(strip_tags($matches[2]));
                $href = trim($matches[1]);
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

    private function respostaPareceHtml(string $content, array $headers): bool
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

    private function handleRegistrarInfoLead(?ClienteLead $lead, array $arguments): string
    {
        if (!$lead) {
            return 'Lead não encontrado para registrar informações.';
        }

        $nome = $arguments['nome'] ?? null;
        $informacoes = $arguments['informacoes'] ?? null;

        $informacoesAtuais = trim((string) ($lead->info ?? ''));
        $novaInformacao = trim((string) ($informacoes ?? ''));
        $timestamp = now(config('app.timezone', 'America/Sao_Paulo'))->format('d/m/Y H:i');

        if ($novaInformacao !== '') {
            $novaInformacao = "[{$timestamp}] " . $novaInformacao;
        }

        if ($informacoesAtuais !== '' && $novaInformacao !== '') {
            $novaInformacao = $informacoesAtuais . "\n" . $novaInformacao;
        } elseif ($informacoesAtuais !== '') {
            $novaInformacao = $informacoesAtuais;
        }

        $lead->update([
            'name' => $nome ?? $lead->name,
            'info' => $novaInformacao,
        ]);

        return 'Informações registradas com sucesso.';
    }

    private function handleEnviarPost(array $arguments, array $payload, ?Conexao $conexao): string
    {
        $event = trim((string) ($arguments['event'] ?? ''));
        $url = trim((string) ($arguments['url'] ?? ''));
        $payloadData = $arguments['payload'] ?? null;

        if ($event === '' || $url === '' || !is_array($payloadData)) {
            return 'Dados inválidos para envio do evento.';
        }

        if (!Str::startsWith($url, ['https://'])) {
            return 'URL do webhook inválida.';
        }

        $body = [
            'event' => $event,
            'source' => 'facilitai',
            'triggered_at' => now()->toIso8601String(),
            'conversation_id' => $payload['conversation_id'] ?? null,
            'conexao_id' => $conexao?->id,
            'contact' => [
                'nome' => $payload['contact_name'] ?? null,
                'whatsapp' => $payload['phone'] ?? null,
            ],
            'payload' => $payloadData,
        ];

        try {
            $response = Http::timeout(5)->post($url, $body);
            if ($response->successful()) {
                return 'Evento enviado com sucesso.';
            }
            return 'Erro ao enviar o evento.';
        } catch (\Throwable $e) {
            Log::channel('process_job')->error('Erro ao enviar webhook', $this->logContext([
                'error' => $e->getMessage(),
            ]));
            return 'Erro ao enviar o evento.';
        }
    }

    private function cacheDisponivel(): bool
    {
        try {
            $key = 'uazapi_cache_test_' . uniqid();
            Cache::put($key, 1, 5);
            Cache::forget($key);
            return true;
        } catch (\Throwable $e) {
            return false;
        }
    }

    private function throwTransient(string $message, array $logContext): void
    {
        Log::channel('process_job')->warning($message, $this->logContext($logContext));
        throw new \RuntimeException($message);
    }

    private function logContext(array $extra = []): array
    {
        return LogContext::merge(
            LogContext::jobContext($this),
            LogContext::base($this->payload, $this->conexao),
            $extra
        );
    }

    public function failed(\Throwable $exception): void
    {
        Log::channel('process_job')->error('ProcessIncomingMessageJob failed', $this->logContext([
            'error' => $exception->getMessage(),
            'exception' => get_class($exception),
        ]));
    }
}
