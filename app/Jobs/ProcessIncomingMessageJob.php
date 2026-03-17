<?php

namespace App\Jobs;

use App\Models\Assistant;
use App\Models\AssistantLead;
use App\Models\ClienteLead;
use App\Models\Conexao;
use App\Models\ScheduledMessage;
use App\Models\Sequence;
use App\Models\SequenceChat;
use App\Models\Tag;
use App\Models\User;
use App\Models\WhatsappApi;
use App\Models\WhatsappCloudCustomField;
use App\Services\EvolutionAPIOficial;
use App\Services\IAOrchestratorService;
use App\Services\ScheduledMessageService;
use App\Services\UazapiService;
use App\Services\WhatsappCloudApiService;
use App\DTOs\IAResult;
use App\Support\LogContext;
use App\Support\PhoneNumberNormalizer;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Contracts\Queue\ShouldBeUniqueUntilProcessing;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Carbon;
use Illuminate\Contracts\Cache\LockTimeoutException;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class ProcessIncomingMessageJob implements ShouldQueue, ShouldBeUniqueUntilProcessing
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private const DEBOUNCE_CACHE_TTL_SECONDS = 600;
    private const OPENAI_ERROR_NOTIFY_TTL_SECONDS = 86400;
    private const OPENAI_FALLBACK_MESSAGE = 'Peço desculpas, mas não consegui processar a sua última mensagem, poderia enviar novamente por favor?';

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

    public function __construct(int $conexaoId, ?int $clienteLeadId, array $payload, ?string $cacheKey = null, bool $isMedia = false, int $debounceSeconds = 5, int $maxWaitSeconds = 25)
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

        $phone = app(PhoneNumberNormalizer::class)->normalizeLeadPhone((string) ($this->payload['phone'] ?? '')) ?? '';
        if ($phone === '') {
            $this->logSilentReturn('telefone_ausente');
            return;
        }
        $this->payload['phone'] = $phone;

        if (($this->payload['is_group'] ?? false) === true) {
            $this->logSilentReturn('mensagem_grupo');
            return;
        }

        $assistant = $conexao->assistant;
        if (!$assistant) {
            Log::channel('process_job')->warning('Assistente não encontrado para conexão.', $this->logContext([
                'conexao_id' => $conexao->id,
            ]));
            return;
        }
        $this->maxWaitSeconds = $this->resolveMaxWaitSeconds($assistant);

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
            $this->logSilentReturn('from_me', [
                'bot_enabled' => $botEnabled,
            ]);
            return;
        }

        // Registra último contato recebido do lead via updated_at.
        $lead->touch();

        if (!$lead->bot_enabled) {
            $this->logSilentReturn('bot_desativado');
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
        $payload['assistant_model'] = $conexao->iamodelo?->nome ?? 'gpt-4.1-mini';
        $payload['contact_name'] = $lead->name ?: $leadName;
        $payload['system_prompt'] = $systemPrompt;

        $tipo = Str::lower((string) ($payload['tipo'] ?? 'text'));
        if ($tipo !== 'text') {
            $payload['is_media'] = true;
            $this->sendIAResponse($payload, $assistant, $lead, $assistantLead);
            return;
        }

        if (($payload['bypass_debounce'] ?? false) === true) {
            $payload['is_media'] = false;
            $this->sendIAResponse($payload, $assistant, $lead, $assistantLead);
            return;
        }

        if (!$this->cacheDisponivel()) {
            $payload['is_media'] = false;
            $this->sendIAResponse($payload, $assistant, $lead, $assistantLead);
            return;
        }

        $cacheKey = "debounce:{$lead->id}:{$assistant->id}";
        $lockKey = "debounce-lock:{$lead->id}:{$assistant->id}";
        $scheduledKey = "{$cacheKey}:scheduled";
        $bufferMessagesCount = 0;
        $shouldDispatch = false;
        $bufferVersion = null;
        $scheduledTtl = min(self::DEBOUNCE_CACHE_TTL_SECONDS, $this->maxWaitSeconds + $this->debounceSeconds + 30);
        $staleSeconds = max($this->maxWaitSeconds + 5, $this->debounceSeconds * 2);

        try {
            Cache::lock($lockKey, 3)->block(2, function () use (
                $cacheKey,
                $scheduledKey,
                $payload,
                $text,
                $scheduledTtl,
                $staleSeconds,
                &$bufferMessagesCount,
                &$shouldDispatch,
                &$bufferVersion
            ) {
                $buffer = Cache::get($cacheKey, []);
                $agora = Carbon::now()->timestamp;
                $previousLastAt = (int) ($buffer['last_at'] ?? 0);

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

                $buffer['version'] = (int) ($buffer['version'] ?? 0) + 1;
                $buffer['data']['debounce_version'] = $buffer['version'];

                Cache::put($cacheKey, $buffer, now()->addSeconds(self::DEBOUNCE_CACHE_TTL_SECONDS));

                $bufferMessagesCount = count($buffer['messages'] ?? []);
                $bufferVersion = $buffer['version'];

                $created = Cache::add($scheduledKey, true, now()->addSeconds($scheduledTtl));
                if ($created) {
                    $shouldDispatch = true;
                    return;
                }

                if (($agora - $previousLastAt) >= $staleSeconds) {
                    Cache::put($scheduledKey, true, now()->addSeconds($scheduledTtl));
                    $shouldDispatch = true;
                }
            });
        } catch (LockTimeoutException $exception) {
            Log::channel('process_job')->warning('Debounce lock timeout.', $this->logContext([
                'cache_key' => $cacheKey,
            ]));

            self::dispatch($this->conexaoId, $this->clienteLeadId, $payload)
                ->delay(now()->addSeconds(1))
                ->onQueue('processarconversa');
            return;
        }

        if ($shouldDispatch) {
            $dispatchPayload = $payload;
            if ($bufferVersion !== null) {
                $dispatchPayload['debounce_version'] = $bufferVersion;
            }
            self::dispatch($this->conexaoId, $this->clienteLeadId, $dispatchPayload, $cacheKey, false, $this->debounceSeconds, $this->maxWaitSeconds)
                ->delay(now()->addSeconds($this->maxWaitSeconds))->onQueue('processarconversa');
        }

        $this->logSilentReturn('debounce_buffered', [
            'cache_key' => $cacheKey,
            'messages_count' => $bufferMessagesCount,
            'debounce_seconds' => $this->debounceSeconds,
            'wait_seconds' => $this->maxWaitSeconds,
            'max_wait_seconds' => $this->maxWaitSeconds,
            'scheduled' => $shouldDispatch,
        ]);
    }

    /**
     * Compatibilidade com jobs legados (ProcessDebounceJob) ainda presentes na fila.
     */
    public function handleDebounce(): void
    {
        $conexao = $this->loadConexao();
        if (!$conexao) {
            return;
        }
        $this->maxWaitSeconds = $this->resolveMaxWaitSeconds($conexao->assistant);

        $lockKey = $this->lockKeyFromCacheKey($this->cacheKey);
        $scheduledKey = $this->scheduledKeyFromCacheKey($this->cacheKey);
        $scheduledTtl = min(self::DEBOUNCE_CACHE_TTL_SECONDS, $this->maxWaitSeconds + $this->debounceSeconds + 30);
        $buffer = null;

        try {
            Cache::lock($lockKey, 3)->block(2, function () use (&$buffer) {
                $buffer = Cache::get($this->cacheKey);
            });
        } catch (LockTimeoutException $exception) {
            if ($scheduledKey) {
                Cache::put($scheduledKey, true, now()->addSeconds($scheduledTtl));
            }
            self::dispatch($this->conexaoId, $this->clienteLeadId, $this->payload, $this->cacheKey, $this->isMedia, $this->debounceSeconds, $this->maxWaitSeconds)
                ->delay(now()->addSeconds($this->debounceSeconds))
                ->onQueue('processarconversa');
            return;
        }

        if (empty($buffer) || empty($buffer['messages'])) {
            if ($scheduledKey) {
                Cache::forget($scheduledKey);
            }
            $this->logSilentReturn('debounce_buffer_empty', [
                'cache_key' => $this->cacheKey,
            ]);
            return;
        }

        $now = Carbon::now();
        $lastAt = Carbon::createFromTimestamp($buffer['last_at'] ?? $now->timestamp);
        $secondsSinceLast = $now->timestamp - $lastAt->timestamp;
        $currentVersion = (int) ($buffer['version'] ?? 0);
        $expectedVersion = $this->payload['debounce_version'] ?? null;
        $dispatchPayload = $this->payload;
        if ($currentVersion > 0) {
            $dispatchPayload['debounce_version'] = $currentVersion;
        }

        if ($expectedVersion !== null && $currentVersion > 0 && $currentVersion !== (int) $expectedVersion) {
            if ($scheduledKey) {
                Cache::put($scheduledKey, true, now()->addSeconds($scheduledTtl));
            }
            $delaySeconds = max(1, $this->maxWaitSeconds - $secondsSinceLast);
            self::dispatch($this->conexaoId, $this->clienteLeadId, $dispatchPayload, $this->cacheKey, $this->isMedia, $this->debounceSeconds, $this->maxWaitSeconds)
                ->delay(now()->addSeconds($delaySeconds))
                ->onQueue('processarconversa');
            $this->logSilentReturn('debounce_obsolete_tick', [
                'cache_key' => $this->cacheKey,
                'expected_version' => (int) $expectedVersion,
                'current_version' => $currentVersion,
                'delay_seconds' => $delaySeconds,
            ]);
            return;
        }

        if ($secondsSinceLast < $this->maxWaitSeconds) {
            $delaySeconds = max(1, $this->maxWaitSeconds - $secondsSinceLast);
            if ($scheduledKey) {
                Cache::put($scheduledKey, true, now()->addSeconds($scheduledTtl));
            }
            self::dispatch($this->conexaoId, $this->clienteLeadId, $dispatchPayload, $this->cacheKey, $this->isMedia, $this->debounceSeconds, $this->maxWaitSeconds)
                ->delay(now()->addSeconds($delaySeconds))
                ->onQueue('processarconversa');
            $this->logSilentReturn('debounce_waiting', [
                'cache_key' => $this->cacheKey,
                'last_at' => $lastAt->toIso8601String(),
                'seconds_since_last' => $secondsSinceLast,
                'delay_seconds' => $delaySeconds,
                'max_wait_seconds' => $this->maxWaitSeconds,
            ]);
            return;
        }

        $combined = implode("\n", $buffer['messages']);
        $payload = is_array($buffer['data'] ?? null) ? $buffer['data'] : $this->payload;
        $payload['is_media'] = $this->isMedia;
        $payload['text'] = $combined;
        $lastAtSent = (int) ($buffer['last_at'] ?? $now->timestamp);

        try {
            $this->sendDebouncedPayload($conexao, $payload);
        } catch (\Throwable $exception) {
            Log::channel('process_job')->warning('Falha ao enviar payload debounced.', $this->logContext([
                'cache_key' => $this->cacheKey,
                'error' => $exception->getMessage(),
            ]));
            if ($scheduledKey) {
                Cache::put($scheduledKey, true, now()->addSeconds($scheduledTtl));
            }
            self::dispatch($this->conexaoId, $this->clienteLeadId, $dispatchPayload, $this->cacheKey, $this->isMedia, $this->debounceSeconds, $this->maxWaitSeconds)
                ->delay(now()->addSeconds($this->debounceSeconds))
                ->onQueue('processarconversa');
            return;
        }

        $releaseDelay = null;
        try {
            Cache::lock($lockKey, 3)->block(2, function () use ($lastAtSent, $scheduledKey, $scheduledTtl, &$releaseDelay) {
                $current = Cache::get($this->cacheKey);
                if (empty($current)) {
                    if ($scheduledKey) {
                        Cache::forget($scheduledKey);
                    }
                    return;
                }

                $currentLastAt = (int) ($current['last_at'] ?? 0);
                if ($currentLastAt > $lastAtSent) {
                    $now = Carbon::now()->timestamp;
                    $secondsSinceLast = max(0, $now - $currentLastAt);
                    $releaseDelay = max(1, $this->maxWaitSeconds - $secondsSinceLast);
                    if ($scheduledKey) {
                        Cache::put($scheduledKey, true, now()->addSeconds($scheduledTtl));
                    }
                    return;
                }

                Cache::forget($this->cacheKey);
                if ($scheduledKey) {
                    Cache::forget($scheduledKey);
                }
            });
        } catch (LockTimeoutException $exception) {
            if ($scheduledKey) {
                Cache::put($scheduledKey, true, now()->addSeconds($scheduledTtl));
            }
            self::dispatch($this->conexaoId, $this->clienteLeadId, $dispatchPayload, $this->cacheKey, $this->isMedia, $this->debounceSeconds, $this->maxWaitSeconds)
                ->delay(now()->addSeconds($this->debounceSeconds))
                ->onQueue('processarconversa');
            return;
        }

        if ($releaseDelay !== null) {
            if ($scheduledKey) {
                Cache::put($scheduledKey, true, now()->addSeconds($scheduledTtl));
            }
            self::dispatch($this->conexaoId, $this->clienteLeadId, $dispatchPayload, $this->cacheKey, $this->isMedia, $this->debounceSeconds, $this->maxWaitSeconds)
                ->delay(now()->addSeconds($releaseDelay))
                ->onQueue('processarconversa');
            return;
        }
    }

    private function lockKeyFromCacheKey(?string $cacheKey): ?string
    {
        if (!$cacheKey) {
            return null;
        }

        return preg_replace('/^debounce:/', 'debounce-lock:', $cacheKey);
    }

    private function scheduledKeyFromCacheKey(?string $cacheKey): ?string
    {
        if (!$cacheKey) {
            return null;
        }

        return "{$cacheKey}:scheduled";
    }

    private function resolveMaxWaitSeconds(?Assistant $assistant): int
    {
        $delay = (int) ($assistant?->delay ?? 0);
        return $delay > 0 ? $delay : 25;
    }

    private function sendDebouncedPayload(Conexao $conexao, array $payload): void
    {
        $assistant = $conexao->assistant;
        if (!$assistant) {
            $this->logSilentReturn('assistente_nao_encontrado', [
                'cache_key' => $this->cacheKey,
            ]);
            return;
        }

        $lead = $this->loadLeadFromPayload($payload);
        if (!$lead) {
            $this->logSilentReturn('lead_nao_encontrado', [
                'cache_key' => $this->cacheKey,
            ]);
            return;
        }

        $assistantLead = $this->ensureAssistantLead($lead, $assistant);
        if (!$assistantLead) {
            $this->logSilentReturn('assistant_lead_nao_encontrado', [
                'cache_key' => $this->cacheKey,
            ]);
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

        $conexao = Conexao::with(['cliente', 'assistant', 'credential.iaplataforma', 'iamodelo', 'whatsappApi', 'whatsappCloudAccount'])->find($this->conexaoId);
        if (!$conexao) {
            Log::channel('process_job')->error('Conexao não encontrada para ProcessIncomingMessageJob.', $this->logContext([
                'conexao_id' => $this->conexaoId,
            ]));
            return null;
        }

        if (!$conexao->is_active) {
            Log::channel('process_job')->info('Conexao inativa ignorada no ProcessIncomingMessageJob.', $this->logContext([
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
        $phoneCandidates = app(PhoneNumberNormalizer::class)->buildLeadPhoneLookupCandidates($phone);
        if (empty($phoneCandidates)) {
            return null;
        }
        $canonicalPhone = $phoneCandidates[0];

        $lead = $this->clienteLead;
        if (!$lead) {
            $lead = ClienteLead::where('cliente_id', $this->conexao->cliente_id)
                ->whereIn('phone', $phoneCandidates)
                ->first();
        }

        if (!$lead) {
            try {
                $lead = ClienteLead::create([
                    'cliente_id' => $this->conexao->cliente_id,
                    'phone' => $canonicalPhone,
                    'name' => $leadName,
                    'info' => null,
                    'bot_enabled' => true,
                ]);
            } catch (UniqueConstraintViolationException $exception) {
                if (!$this->isLeadPhoneUniqueViolation($exception)) {
                    throw $exception;
                }

                $lead = ClienteLead::where('cliente_id', $this->conexao->cliente_id)
                    ->whereIn('phone', $phoneCandidates)
                    ->first();

                if (!$lead) {
                    throw $exception;
                }
            }

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
        $phone = (string) ($payload['phone'] ?? '');
        $token = $this->conexao?->whatsapp_api_key;

        $logContext = array_filter([
            'conexao_id' => $payload['conexao_id'] ?? $this->conexao?->id,
            'assistant_id' => $payload['assistant_id'] ?? null,
            'lead_id' => $payload['lead_id'] ?? null,
            'phone' => $phone,
        ], function ($value) {
            return $value !== null && $value !== '';
        });

        $this->sendPresence($token, $phone, array_merge($logContext, [
            'assistant_lead_id' => $assistantLead->id,
        ]));

        $handlers = $this->buildToolHandlers($payload, $this->conexao, $this->clienteLead);
        $orchestrator = new IAOrchestratorService();
        $result = $orchestrator->handleMessage($this->conexao, $assistant, $lead, $assistantLead, $payload, $handlers);
        $this->persistAssistantLeadResponse($assistantLead, $result);

        if (!$result->ok || !is_string($result->text) || trim($result->text) === '') {
            Log::channel('process_job')->warning('IAOrchestratorService sem resposta final.', $this->logContext(array_merge($logContext, [
                'provider' => $result->provider,
                'error' => $result->error,
            ])));
            if ($result->provider === 'openai') {
                $this->handleOpenAIErrorFallback($result, $payload, $lead, $logContext);
            }
            return;
        }

        $this->sendText($token, $phone, $result->text, $logContext);
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

    private function sendPresence(?string $token, string $phone, array $logContext = []): void
    {
        if ($phone === '') {
            return;
        }

        $providerSlug = $this->resolveWhatsappProviderSlug();

        if ($providerSlug === 'uazapi') {
            $providerToken = $token ?: $this->conexao?->whatsapp_api_key;
            if (!$providerToken) {
                return;
            }

            try {
                $presenceResult = (new UazapiService())->messagePresence($providerToken, $phone);
                if (!empty($presenceResult['error'])) {
                    Log::channel('process_job')->warning('Falha ao enviar presence via Uazapi.', $this->logContext(array_merge($logContext, [
                        'response' => $presenceResult,
                    ])));
                }
            } catch (\Throwable $exception) {
                Log::channel('process_job')->error('Erro inesperado ao chamar messagePresence.', $this->logContext(array_merge($logContext, [
                    'error' => $exception->getMessage(),
                ])));
            }

            return;
        }

        if ($providerSlug === 'api_oficial') {
            $instanceId = $this->resolveApiOficialInstanceId();
            if (!$instanceId) {
                Log::channel('process_job')->warning('Instância ausente para presence via API Oficial.', $this->logContext($logContext));
                return;
            }

            try {
                $presenceResult = (new EvolutionAPIOficial())->messagePresence($instanceId, $phone);
                if (!empty($presenceResult['error'])) {
                    Log::channel('process_job')->warning('Falha ao enviar presence via API Oficial.', $this->logContext(array_merge($logContext, [
                        'response' => $presenceResult,
                    ])));
                }
            } catch (\Throwable $exception) {
                Log::channel('process_job')->error('Erro inesperado ao chamar messagePresence da API Oficial.', $this->logContext(array_merge($logContext, [
                    'error' => $exception->getMessage(),
                ])));
            }
        }
    }

    private function sendText(?string $token, string $phone, string $message, array $logContext = []): void
    {
        if ($phone === '') {
            Log::channel('process_job')->warning('Telefone ausente para envio da resposta.', $this->logContext($logContext));
            return;
        }

        $providerSlug = $this->resolveWhatsappProviderSlug();

        if ($providerSlug === 'uazapi') {
            $providerToken = $token ?: $this->conexao?->whatsapp_api_key;
            if (!$providerToken) {
                Log::channel('process_job')->warning('Token ausente para envio via Uazapi.', $this->logContext($logContext));
                return;
            }

            $uazapi = new UazapiService();
            $sendResult = $uazapi->sendText($providerToken, $phone, $message);
            if (!empty($sendResult['error'])) {
                Log::channel('process_job')->error('Falha ao enviar mensagem via Uazapi.', $this->logContext(array_merge($logContext, [
                    'response' => $sendResult,
                ])));
                $this->throwTransient('Falha ao enviar mensagem via Uazapi.', $logContext);
            }

            return;
        }

        if ($providerSlug === 'api_oficial') {
            $instanceId = $this->resolveApiOficialInstanceId();
            if (!$instanceId) {
                Log::channel('process_job')->warning('Instância ausente para envio via API Oficial.', $this->logContext($logContext));
                return;
            }

            $service = new EvolutionAPIOficial();
            $sendResult = $service->sendText($instanceId, $phone, $message);
            if (!empty($sendResult['error'])) {
                Log::channel('process_job')->error('Falha ao enviar mensagem via API Oficial.', $this->logContext(array_merge($logContext, [
                    'response' => $sendResult,
                ])));
                $this->throwTransient('Falha ao enviar mensagem via API Oficial.', $logContext);
            }

            return;
        }

        if ($providerSlug === 'whatsapp_cloud') {
            $service = new WhatsappCloudApiService();
            $sendResult = $service->sendText($phone, $message, $this->resolveWhatsappCloudSendOptions($token));
            if (!empty($sendResult['error'])) {
                Log::channel('process_job')->error('Falha ao enviar mensagem via WhatsApp Cloud API.', $this->logContext(array_merge($logContext, [
                    'response' => $sendResult,
                ])));
                $this->throwTransient('Falha ao enviar mensagem via WhatsApp Cloud API.', $logContext);
            }

            return;
        }

        Log::channel('process_job')->warning('Provedor WhatsApp não suportado para envio de texto.', $this->logContext(array_merge($logContext, [
            'provider_slug' => $providerSlug,
        ])));
    }

    private function resolveWhatsappProviderSlug(): string
    {
        $slug = Str::lower((string) ($this->conexao?->whatsappApi?->slug ?? ''));
        if ($slug !== '') {
            return $slug;
        }

        $apiId = (int) ($this->conexao?->whatsapp_api_id ?? 0);
        if ($apiId > 0) {
            $fallbackSlug = WhatsappApi::query()->whereKey($apiId)->value('slug');
            if (is_string($fallbackSlug)) {
                $fallbackSlug = Str::lower(trim($fallbackSlug));
                if ($fallbackSlug !== '') {
                    return $fallbackSlug;
                }
            }
        }

        return 'uazapi';
    }

    private function resolveApiOficialInstanceId(): ?string
    {
        $instanceId = $this->conexao?->id;
        if (!$instanceId) {
            return null;
        }

        return (string) $instanceId;
    }

    private function resolveWhatsappCloudSendOptions(?string $token = null): array
    {
        $options = [];

        $accessToken = trim((string) ($token ?: ($this->conexao?->whatsappCloudAccount?->access_token ?? '') ?: ($this->conexao?->whatsapp_api_key ?? '')));
        if ($accessToken !== '') {
            $options['access_token'] = $accessToken;
        }

        $phoneNumberId = $this->resolveWhatsappCloudPhoneNumberId();
        if ($phoneNumberId !== null) {
            $options['phone_number_id'] = $phoneNumberId;
        }

        return $options;
    }

    private function resolveWhatsappCloudPhoneNumberId(): ?string
    {
        $fromAccount = trim((string) ($this->conexao?->whatsappCloudAccount?->phone_number_id ?? ''));
        if ($fromAccount !== '' && preg_match('/^\d+$/', $fromAccount)) {
            return $fromAccount;
        }

        $fromInfo = $this->readConexaoInfoValue('phone_number_id');
        if ($fromInfo !== null) {
            return $fromInfo;
        }

        $fromPhoneField = trim((string) ($this->conexao?->phone ?? ''));
        if ($fromPhoneField !== '' && preg_match('/^\d+$/', $fromPhoneField)) {
            return $fromPhoneField;
        }

        return null;
    }

    private function readConexaoInfoValue(string $key): ?string
    {
        $raw = $this->conexao?->informacoes;
        if (!is_string($raw) || trim($raw) === '') {
            return null;
        }

        $decoded = json_decode($raw, true);
        if (!is_array($decoded)) {
            return null;
        }

        $value = $decoded[$key] ?? null;
        if (!is_scalar($value)) {
            return null;
        }

        $value = trim((string) $value);
        return $value !== '' ? $value : null;
    }

    private function handleOpenAIErrorFallback(IAResult $result, array $payload, ClienteLead $lead, array $logContext): void
    {
        $token = $this->conexao?->whatsapp_api_key;
        $phone = (string) ($payload['phone'] ?? '');

        $this->sendOpenAIFallbackMessage($token, $phone, $logContext);
        $this->notifyAdminOpenAIError($result, $lead, $payload, $logContext);
    }

    private function sendOpenAIFallbackMessage(?string $token, string $phone, array $logContext): void
    {
        try {
            $this->sendText($token, $phone, self::OPENAI_FALLBACK_MESSAGE, $logContext);
        } catch (\Throwable $exception) {
            Log::channel('process_job')->warning('Falha ao enviar mensagem de fallback OpenAI.', $this->logContext(array_merge($logContext, [
                'error' => $exception->getMessage(),
            ])));
        }
    }

    private function notifyAdminOpenAIError(IAResult $result, ClienteLead $lead, array $payload, array $logContext): void
    {
        $token = $this->conexao?->whatsapp_api_key;
        if (!$token && $this->resolveWhatsappProviderSlug() === 'uazapi') {
            Log::channel('process_job')->warning('Token ausente para notificar admin.', $this->logContext($logContext));
            return;
        }

        $payloadString = $this->buildOpenAIErrorPayloadString($result);
        $clienteId = $lead->cliente_id ?? $this->conexao?->cliente_id;
        $userId = $lead->cliente?->user_id ?? $this->conexao?->cliente?->user_id;
        $notifyKey = $this->openAIErrorNotifyKey($payloadString, $lead->id ?? null, $clienteId, $userId);

        $created = Cache::add($notifyKey, true, now()->addSeconds(self::OPENAI_ERROR_NOTIFY_TTL_SECONDS));
        if (!$created) {
            return;
        }

        $message = "OpenAI erro\nlead_id: " . ($lead->id ?? '-') .
            "\ncliente_id: " . ($clienteId ?? '-') .
            "\nuser_id: " . ($userId ?? '-') .
            "\nuser_email: " . ($lead->cliente?->user?->email ?? $this->conexao?->cliente?->user?->email ?? '-') .
            "\nassistant_id: " . ($payload['assistant_id'] ?? '-') .
            "\nconexao_id: " . ($payload['conexao_id'] ?? '-') .
            "\nconv_id: " . ($payload['conversation_id'] ?? '-') .
            "\nphone: " . ($payload['phone'] ?? '-') .
            "\npayload: " . $payloadString;

        $notified = $this->notifyOpenAIErrorUser($token, $lead, $message, $logContext);
        $this->notifyOpenAIErrorDev($token, $message, $logContext);

        if (!$notified) {
            $this->notifyOpenAIErrorAdmin($token, $message, $logContext);
        }
    }

    private function notifyOpenAIErrorUser(?string $token, ClienteLead $lead, string $message, array $logContext): bool
    {
        $user = $lead->cliente?->user ?? $this->conexao?->cliente?->user;
        if (!$user || !is_string($user->mobile_phone) || trim($user->mobile_phone) === '') {
            return false;
        }

        $userPhone = preg_replace('/\D/', '', $user->mobile_phone);
        if ($userPhone === '') {
            return false;
        }

        try {
            $this->sendText($token, $userPhone, $message, array_merge($logContext, [
                'user_id' => $user->id,
            ]));
            return true;
        } catch (\Throwable $exception) {
            Log::channel('process_job')->warning('Falha ao notificar usuÃ¡rio sobre erro OpenAI.', $this->logContext(array_merge($logContext, [
                'error' => $exception->getMessage(),
                'user_id' => $user->id,
            ])));
            return false;
        }
    }

    private function notifyOpenAIErrorAdmin(?string $token, string $message, array $logContext): void
    {
        $admin = User::where('is_admin', true)->orderBy('id')->first();
        if (!$admin || !is_string($admin->mobile_phone) || trim($admin->mobile_phone) === '') {
            Log::channel('process_job')->warning('Admin nÃ£o encontrado ou sem telefone para notificaÃ§Ã£o.', $this->logContext($logContext));
            return;
        }

        $adminPhone = preg_replace('/\D/', '', $admin->mobile_phone);
        if ($adminPhone === '') {
            Log::channel('process_job')->warning('Telefone do admin invÃ¡lido para notificaÃ§Ã£o.', $this->logContext($logContext));
            return;
        }

        try {
            $this->sendText($token, $adminPhone, $message, array_merge($logContext, [
                'admin_id' => $admin->id,
            ]));
        } catch (\Throwable $exception) {
            Log::channel('process_job')->warning('Falha ao notificar admin sobre erro OpenAI.', $this->logContext(array_merge($logContext, [
                'error' => $exception->getMessage(),
                'admin_id' => $admin->id,
            ])));
        }
    }

    private function notifyOpenAIErrorDev(?string $token, string $message, array $logContext): void
    {
        $devPhoneRaw = config('services.dev.whatsapp');
        if (!is_string($devPhoneRaw) || trim($devPhoneRaw) === '') {
            $devPhoneRaw = '5562995772922';
        }

        $devPhone = preg_replace('/\D/', '', $devPhoneRaw);
        if ($devPhone === '') {
            Log::channel('process_job')->warning('DEV_WHATSAPP invalido para notificacao.', $this->logContext($logContext));
            return;
        }

        try {
            $this->sendText($token, $devPhone, $message, array_merge($logContext, [
                'dev_phone' => $devPhone,
            ]));
        } catch (\Throwable $exception) {
            Log::channel('process_job')->warning('Falha ao notificar DEV_WHATSAPP sobre erro OpenAI.', $this->logContext(array_merge($logContext, [
                'error' => $exception->getMessage(),
                'dev_phone' => $devPhone,
            ])));
        }
    }

    private function buildOpenAIErrorPayloadString(IAResult $result): string
    {
        $payload = [
            'error' => $result->error,
            'raw' => $result->raw,
        ];

        $encoded = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        return $encoded !== false ? $encoded : (string) ($result->error ?? 'erro');
    }

    private function openAIErrorNotifyKey(string $payload, ?int $leadId, ?int $clienteId, ?int $userId): string
    {
        $hash = sha1($payload);
        $leadPart = $leadId !== null ? (string) $leadId : '0';
        $clientePart = $clienteId !== null ? (string) $clienteId : '0';
        $userPart = $userId !== null ? (string) $userId : '0';

        return "openai-error-notify:{$leadPart}:{$clientePart}:{$userPart}:{$hash}";
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
            'registrar_campo_personalizado' => function (array $arguments, array $context) use ($lead) {
                return $this->handleRegistrarCampoPersonalizado($lead, $arguments);
            },
            'enviar_post' => function (array $arguments, array $context) use ($payload, $conexao) {
                return $this->handleEnviarPost($arguments, $payload, $conexao);
            },
            'agendar_msg' => function (array $arguments, array $context) use ($lead, $conexao, $payload) {
                return $this->handleAgendarMsg($lead, $conexao, $payload, $arguments);
            },
            'gerenciar_agenda' => function (array $arguments, array $context) {
                return 'Funcao nao suportada no Uazapi.';
            },
            'aplicar_tags' => function (array $arguments, array $context) use ($lead) {
                try {
                    if (!$lead) {
                        return '⚠️ Lead não encontrado.';
                    }
                    $cliente = $lead->cliente;
                    $userId = $cliente->user_id ?? null;
                    if (!$userId) {
                        return '⚠️ Cliente sem usuário associado.';
                    }

                    $tags = collect($arguments['tags'] ?? [])
                        ->map(fn ($tag) => trim((string) $tag))
                        ->filter()
                        ->unique()
                        ->values();

                    if ($tags->isEmpty()) {
                        return '⚠️ Nenhuma tag informada.';
                    }

                    $existing = Tag::where('user_id', $userId)
                        ->whereIn('name', $tags)
                        ->get();

                    if ($existing->isEmpty()) {
                        return '⚠️ Nenhuma das tags informadas existe para este usuário.';
                    }

                    $lead->tags()->syncWithoutDetaching($existing->pluck('id')->all());

                    $aplicadas = $existing->pluck('name')->implode(', ');
                    $faltantes = $tags->diff($existing->pluck('name'))->values();

                    $msg = '✅ Tags aplicadas: ' . $aplicadas;
                    if ($faltantes->isNotEmpty()) {
                        $msg .= '. Não encontrei: ' . $faltantes->implode(', ');
                    }

                    return $msg;
                } catch (\Throwable $e) {
                    Log::channel('process_job')->error('Erro ao aplicar tags via tool.', [
                        'error' => $e->getMessage(),
                        'args' => $arguments,
                        'lead_id' => $lead?->id,
                    ]);
                    return '❌ Não foi possível aplicar as tags.';
                }
            },
            'inscrever_sequencia' => function (array $arguments, array $context) use ($lead, $payload, $conexao) {
                try {
                    if (!$lead || !$lead->bot_enabled) {
                        return ['output' => '⚠️ Lead indisponível ou bot desativado.'];
                    }

                    $sequenceId = $arguments['sequence_id'] ?? null;
                    if (!$sequenceId) {
                        return ['output' => '⚠️ ID da sequência não informado.'];
                    }

                    $cliente = $lead->cliente;
                    $userId = $cliente?->user_id;
                    $clienteId = $cliente?->id;
                    if (!$userId || !$clienteId) {
                        return ['output' => '⚠️ Cliente sem usuário associado.'];
                    }

                    $sequence = Sequence::where('id', $sequenceId)
                        ->where('user_id', $userId)
                        ->where('cliente_id', $clienteId)
                        ->where('active', true)
                        ->first();

                    if (!$sequence) {
                        return ['output' => '⚠️ Sequência não encontrada ou inativa.'];
                    }

                    $existing = SequenceChat::where('sequence_id', $sequence->id)
                        ->where('cliente_lead_id', $lead->id)
                        ->first();

                    if ($existing) {
                        return ['output' => 'ℹ️ Este lead já está inscrito nesta sequência.'];
                    }

                    $assistantId = isset($payload['assistant_id']) && $payload['assistant_id'] !== ''
                        ? (int) $payload['assistant_id']
                        : ($conexao?->assistant_id ? (int) $conexao->assistant_id : null);
                    $conexaoId = $conexao?->id ? (int) $conexao->id : null;

                    try {
                        SequenceChat::create([
                            'sequence_id' => $sequence->id,
                            'cliente_lead_id' => $lead->id,
                            'assistant_id' => $assistantId,
                            'conexao_id' => $conexaoId,
                            'status' => 'em_andamento',
                            'iniciado_em' => now('America/Sao_Paulo'),
                            'proximo_envio_em' => null,
                            'criado_por' => 'assistant',
                        ]);
                    } catch (UniqueConstraintViolationException $e) {
                        if ($this->isSequenceLeadUniqueViolation($e)) {
                            return ['output' => 'ℹ️ Este lead já está inscrito nesta sequência.'];
                        }

                        throw $e;
                    }

                    return ['output' => '✅ Lead inscrito na sequência com sucesso.'];
                } catch (\Throwable $e) {
                    Log::error('Erro ao inscrever em sequência (tool): ' . $e->getMessage(), [
                        'args' => $arguments,
                        'lead_id' => $lead?->id,
                    ]);
                    return ['output' => '❌ Não foi possível inscrever na sequência.'];
                }
            },
        ];
    }

    private function isSequenceLeadUniqueViolation(\Throwable $exception): bool
    {
        $message = Str::lower($exception->getMessage());

        return Str::contains($message, [
            'sequence_chats_sequence_id_cliente_lead_id_unique',
            'key (sequence_id, cliente_lead_id)',
        ]);
    }

    private function isLeadPhoneUniqueViolation(\Throwable $exception): bool
    {
        $message = Str::lower($exception->getMessage());

        return Str::contains($message, [
            'cliente_lead_cliente_id_phone_unique',
            'key (cliente_id, phone)',
        ]);
    }

    private function handleAgendarMsg(?ClienteLead $lead, ?Conexao $conexao, array $payload, array $arguments): string
    {
        try {
            if (!$lead) {
                return 'Lead nao encontrado para agendamento.';
            }

            if (!$conexao) {
                return 'Conexao nao encontrada para agendamento.';
            }

            $isScheduledExecution = ($payload['bypass_debounce'] ?? false) === true
                || Str::startsWith((string) ($payload['event_id'] ?? ''), 'scheduled:');

            if ($isScheduledExecution) {
                return 'Agendamento bloqueado para evitar recursao.';
            }

            $mensagem = trim((string) ($arguments['mensagem'] ?? $arguments['instrucao'] ?? ''));
            $scheduledForRaw = trim((string) ($arguments['scheduled_for'] ?? $arguments['data_hora'] ?? ''));

            if ($mensagem === '') {
                return 'Mensagem obrigatoria para agendamento.';
            }

            if (Str::length($mensagem) > 2000) {
                return 'Mensagem excede limite de 2000 caracteres.';
            }

            if ($scheduledForRaw === '') {
                return 'Data/hora obrigatoria para agendamento.';
            }

            $lead->loadMissing(['cliente', 'assistantLeads']);
            $ownerUserId = (int) ($lead->cliente->user_id ?? 0);
            if ($ownerUserId <= 0) {
                return 'Lead sem usuario associado.';
            }

            $owner = User::find($ownerUserId);
            if (!$owner) {
                return 'Usuario dono do lead nao encontrado.';
            }

            $assistantId = isset($payload['assistant_id']) && $payload['assistant_id'] !== ''
                ? (int) $payload['assistant_id']
                : ($conexao->assistant_id ? (int) $conexao->assistant_id : 0);

            if ($assistantId <= 0) {
                return 'Assistente nao identificado para agendamento.';
            }

            /** @var ScheduledMessageService $scheduledMessageService */
            $scheduledMessageService = app(ScheduledMessageService::class);
            $timezone = $scheduledMessageService->resolveTimezoneForUser($owner);
            $scheduledForUtc = $scheduledMessageService->parseScheduledForToUtc($scheduledForRaw, $timezone);

            if (!$scheduledForUtc) {
                return "Data/hora invalida. Use YYYY-MM-DD HH:mm no fuso {$timezone}.";
            }

            $nowUtc = Carbon::now('UTC');
            if ($scheduledForUtc->lte($nowUtc)) {
                return 'Agendamento deve ser uma data futura.';
            }

            $maxUtc = Carbon::now($timezone)->addDays(90)->setTimezone('UTC');
            if ($scheduledForUtc->gt($maxUtc)) {
                return 'O limite maximo para agendamento e de 90 dias.';
            }

            $context = $scheduledMessageService->resolveDispatchContext(
                $lead,
                $assistantId,
                $ownerUserId,
                $conexao->id ? (int) $conexao->id : null
            );

            if (!($context['ok'] ?? false)) {
                return (string) ($context['message'] ?? 'Nao foi possivel validar o contexto de envio.');
            }

            $resolvedConexao = $context['conexao'] ?? null;
            if (!$resolvedConexao instanceof Conexao) {
                return 'Conexao nao encontrada para o agendamento.';
            }

            $scheduledForKey = $scheduledForUtc->copy()->format('Y-m-d H:i:s');
            $duplicate = ScheduledMessage::query()
                ->where('cliente_lead_id', $lead->id)
                ->where('assistant_id', $assistantId)
                ->where('status', 'pending')
                ->where('mensagem', $mensagem)
                ->where('scheduled_for', $scheduledForKey)
                ->first();

            if ($duplicate) {
                $scheduledLabel = $duplicate->scheduled_for?->copy()->setTimezone($timezone)->format('d/m/Y H:i')
                    ?? $scheduledForUtc->copy()->setTimezone($timezone)->format('d/m/Y H:i');

                return "Ja existe um agendamento pendente igual para {$scheduledLabel} (id {$duplicate->id}).";
            }

            $scheduledMessage = ScheduledMessage::create([
                'cliente_lead_id' => $lead->id,
                'assistant_id' => $assistantId,
                'conexao_id' => $resolvedConexao->id,
                'mensagem' => $mensagem,
                'scheduled_for' => $scheduledForUtc,
                'status' => 'pending',
                'event_id' => sprintf('scheduled:lead:%d:%s', $lead->id, (string) Str::uuid()),
                'created_by_user_id' => $ownerUserId,
            ]);

            Log::channel('process_job')->info('Agendamento criado via tool agendar_msg.', $this->logContext([
                'scheduled_message_id' => $scheduledMessage->id,
                'lead_id' => $lead->id,
                'assistant_id' => $assistantId,
                'conexao_id' => $resolvedConexao->id,
                'scheduled_for_utc' => $scheduledForUtc->copy()->toIso8601String(),
                'timezone' => $timezone,
            ]));

            return sprintf(
                'Agendamento criado com sucesso (id %d) para %s (%s).',
                $scheduledMessage->id,
                $scheduledForUtc->copy()->setTimezone($timezone)->format('d/m/Y H:i'),
                $timezone
            );
        } catch (\Throwable $exception) {
            Log::channel('process_job')->error('Falha ao criar agendamento via tool agendar_msg.', $this->logContext([
                'args' => $arguments,
                'lead_id' => $lead?->id,
                'conexao_id' => $conexao?->id,
                'error' => $exception->getMessage(),
            ]));

            return 'Nao foi possivel criar o agendamento.';
        }
    }

    private function handleEnviarMedia(?string $token, ?string $phone, array $arguments): string
    {
        $url = $arguments['url'] ?? null;
        if (!is_string($url) || $url === '') {
            return 'URL inválida para envio de mídia.';
        }

        if (!$phone) {
            return 'Telefone ausente para envio de mídia.';
        }

        $type = $arguments['type'] ?? null;
        $text = $arguments['text'] ?? null;
        $docName = $arguments['docName'] ?? null;

        $finalType = $type ? Str::lower((string) $type) : $this->resolveMediaType($url);
        $finalType = $this->normalizeMediaType($finalType);

        if (!$this->isAllowedMediaType($finalType)) {
            return 'Tipo de midia nao suportado. Envie imagem, video, audio ou PDF.';
        }

        if (!$this->isAllowedMediaUrl($url, $finalType)) {
            return 'URL nao suportada. Envie apenas imagem, video, audio ou PDF.';
        }
        
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

        $providerSlug = $this->resolveWhatsappProviderSlug();

        if ($providerSlug === 'uazapi') {
            $providerToken = $token ?: $this->conexao?->whatsapp_api_key;
            if (!$providerToken) {
                return 'Token ausente para envio de mídia.';
            }

            $uazapi = new UazapiService();
            $response = $uazapi->sendMedia($providerToken, $phone, $finalType, $url, $options);

            if (!empty($response['error'])) {
                Log::channel('process_job')->error('Falha ao enviar mídia via Uazapi.', $this->logContext([
                    'response' => $response,
                ]));
                return 'Falha ao enviar mídia.';
            }

            return 'Midia enviada para a fila de envio.';
        }

        if ($providerSlug === 'api_oficial') {
            $instanceId = $this->resolveApiOficialInstanceId();
            if (!$instanceId) {
                return 'Instância ausente para envio de mídia.';
            }

            $service = new EvolutionAPIOficial();
            $response = $service->sendMedia($instanceId, $phone, (string) $finalType, $url, $options);
            if (!empty($response['error'])) {
                Log::channel('process_job')->error('Falha ao enviar mídia via API Oficial.', $this->logContext([
                    'response' => $response,
                ]));
                return 'Falha ao enviar mídia.';
            }

            return 'Midia enviada para a fila de envio.';
        }

        if ($providerSlug === 'whatsapp_cloud') {
            $service = new WhatsappCloudApiService();
            $cloudOptions = $this->resolveWhatsappCloudSendOptions($token);
            $caption = is_string($text) && trim($text) !== '' ? trim($text) : null;
            $filename = isset($options['docName']) && is_string($options['docName']) && trim($options['docName']) !== ''
                ? trim($options['docName'])
                : null;

            $response = match ($finalType) {
                'image' => $service->sendImage($phone, $url, $caption, $cloudOptions),
                'video' => $service->sendVideo($phone, $url, $caption, $cloudOptions),
                'audio', 'ptt' => $service->sendAudioPtt($phone, $url, $cloudOptions),
                'document' => $service->sendDocumentPdf($phone, $url, $filename, $caption, $cloudOptions),
                default => [
                    'error' => true,
                    'status' => 422,
                    'body' => ['message' => 'Tipo de mídia não suportado pela WhatsApp Cloud API.'],
                ],
            };

            if (!empty($response['error'])) {
                Log::channel('process_job')->error('Falha ao enviar mídia via WhatsApp Cloud API.', $this->logContext([
                    'response' => $response,
                    'type' => $finalType,
                ]));
                return 'Falha ao enviar mídia.';
            }

            return 'Midia enviada para a fila de envio.';
        }

        return 'Integração não suportada para envio de mídia.';
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

    private function normalizeMediaType(?string $type): ?string
    {
        if (!is_string($type) || trim($type) === '') {
            return null;
        }

        $type = Str::lower(trim($type));
        if ($type === 'pdf') {
            return 'document';
        }

        return $type;
    }

    private function isAllowedMediaType(?string $type): bool
    {
        return in_array($type, ['image', 'video', 'ptt', 'audio', 'document'], true);
    }

    private function isAllowedMediaUrl(string $url, string $type): bool
    {
        $lower = Str::lower($url);
        if (str_starts_with($lower, 'data:')) {
            return match ($type) {
                'image' => str_starts_with($lower, 'data:image/'),
                'video' => str_starts_with($lower, 'data:video/'),
                'ptt' => str_starts_with($lower, 'data:audio/'),
                'audio' => str_starts_with($lower, 'data:audio/'),
                'document' => str_starts_with($lower, 'data:application/pdf'),
                default => false,
            };
        }

        $path = parse_url($lower, PHP_URL_PATH) ?? '';
        $ext = pathinfo($path, PATHINFO_EXTENSION);
        if ($ext === '') {
            return false;
        }

        return match ($type) {
            'image' => in_array($ext, ['jpg', 'jpeg', 'png', 'webp'], true),
            'video' => in_array($ext, ['mp4'], true),
            'ptt' => in_array($ext, ['mp3', 'ogg'], true),
            'audio' => in_array($ext, ['mp3', 'ogg'], true),
            'document' => $ext === 'pdf',
            default => false,
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

        $mensagem = trim($mensagem);
        if ($mensagem === '') {
            return 'Mensagem vazia para notificar administrador.';
        }

        $normalized = $this->normalizeAdminPhoneList($numeros);
        $numerosSanitizados = $normalized['valid_numbers'];

        if (empty($numerosSanitizados)) {
            return 'Nenhum número válido para notificar.';
        }

        if (!empty($normalized['invalid_raw'])) {
            Log::channel('process_job')->warning('Numeros invalidos ignorados na tool notificar_adm.', $this->logContext([
                'tool' => 'notificar_adm',
                'invalid_count' => count($normalized['invalid_raw']),
                'invalid_samples' => array_slice($normalized['invalid_raw'], 0, 5),
            ]));
        }

        $total = 0;
        $errors = 0;
        foreach ($numerosSanitizados as $numero) {
            $total++;
            try {
                $this->sendText($token, $numero, $mensagem, [
                    'tool' => 'notificar_adm',
                ]);
            } catch (\Throwable $exception) {
                $errors++;
                Log::channel('process_job')->warning('Falha ao notificar administrador via tool.', $this->logContext([
                    'error' => $exception->getMessage(),
                    'phone' => $numero,
                ]));
            }
        }

        $sent = $total - $errors;
        $invalidCount = count($normalized['invalid_raw']);

        if ($total === 0) {
            return 'Nenhum número válido para notificar.';
        }

        if ($errors === 0 && $invalidCount === 0) {
            return "Notificação enviada ({$sent}/{$total}).";
        }

        if ($errors === 0 && $invalidCount > 0) {
            return "Notificação enviada ({$sent}/{$total}). Inválidos ignorados: {$invalidCount}.";
        }

        return "Notificação parcial (enviados: {$sent}, falhas: {$errors}, inválidos: {$invalidCount}).";
    }

    /**
     * @return array{
     *   valid_numbers:array<int,string>,
     *   invalid_raw:array<int,string>,
     *   invalid_normalized:array<int,string>,
     *   total_input:int
     * }
     */
    private function normalizeAdminPhoneList(array $rawNumbers): array
    {
        $validNumbers = [];
        $invalidRaw = [];
        $invalidNormalized = [];
        $seen = [];

        foreach ($rawNumbers as $rawNumber) {
            $rawString = trim((string) $rawNumber);

            if ($rawString === '') {
                $invalidRaw[] = '';
                continue;
            }

            $normalized = $this->normalizeAdminPhone($rawString);
            if ($normalized === null) {
                $invalidRaw[] = $rawString;
                $digitsOnly = preg_replace('/\D/', '', $rawString);
                if (is_string($digitsOnly) && $digitsOnly !== '') {
                    $invalidNormalized[] = $digitsOnly;
                }
                continue;
            }

            if (!isset($seen[$normalized])) {
                $seen[$normalized] = true;
                $validNumbers[] = $normalized;
            }
        }

        return [
            'valid_numbers' => $validNumbers,
            'invalid_raw' => $invalidRaw,
            'invalid_normalized' => $invalidNormalized,
            'total_input' => count($rawNumbers),
        ];
    }

    private function normalizeAdminPhone(string $raw): ?string
    {
        $raw = trim($raw);
        if ($raw === '') {
            return null;
        }

        $digits = preg_replace('/\D/', '', $raw);
        if (!is_string($digits) || $digits === '') {
            return null;
        }

        if (!str_starts_with($digits, '55')) {
            $digits = '55' . $digits;
        }

        $length = strlen($digits);
        if (!in_array($length, [12, 13], true)) {
            return null;
        }

        return $digits;
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

    private function handleRegistrarCampoPersonalizado(?ClienteLead $lead, array $arguments): string
    {
        try {
            if (!$lead) {
                return 'Lead nao encontrado para registrar campo personalizado.';
            }

            $allowedFields = $this->resolveAllowedLeadCustomFields($lead);
            if (empty($allowedFields)) {
                return 'Nenhum campo personalizado valido disponivel para este lead.';
            }

            $rows = $arguments['campos'] ?? null;
            if (!is_array($rows) || empty($rows)) {
                return 'Nenhum campo informado para registro.';
            }

            $pending = [];
            $saved = [];
            $ignoredEmpty = [];
            $invalid = [];
            $seen = [];

            foreach ($rows as $row) {
                if (!is_array($row)) {
                    $invalid[] = 'item_invalido';
                    continue;
                }

                $fieldName = Str::lower(trim((string) ($row['campo'] ?? '')));
                $value = trim((string) ($row['valor'] ?? ''));

                if ($fieldName === '') {
                    $invalid[] = 'item_sem_campo';
                    continue;
                }

                if (isset($seen[$fieldName])) {
                    $invalid[] = $fieldName;
                    continue;
                }

                $seen[$fieldName] = true;

                if ($value === '') {
                    $ignoredEmpty[] = $fieldName;
                    continue;
                }

                $field = $allowedFields[$fieldName] ?? null;
                if (!$field instanceof WhatsappCloudCustomField) {
                    $invalid[] = $fieldName;
                    continue;
                }

                $pending[$fieldName] = [
                    'field' => $field,
                    'value' => $value,
                ];
            }

            if (!empty($pending)) {
                $fieldIds = array_map(
                    fn (array $item): int => (int) $item['field']->id,
                    array_values($pending)
                );

                $existing = $lead->customFieldValues()
                    ->whereIn('whatsapp_cloud_custom_field_id', $fieldIds)
                    ->get()
                    ->keyBy('whatsapp_cloud_custom_field_id');

                foreach ($pending as $fieldName => $item) {
                    /** @var WhatsappCloudCustomField $field */
                    $field = $item['field'];
                    $value = $item['value'];
                    $current = $existing->get((int) $field->id);

                    if ($current) {
                        if (trim((string) ($current->value ?? '')) !== $value) {
                            $current->update(['value' => $value]);
                        }
                    } else {
                        $lead->customFieldValues()->create([
                            'whatsapp_cloud_custom_field_id' => $field->id,
                            'value' => $value,
                        ]);
                    }

                    $saved[] = $fieldName;
                }
            }

            return $this->buildRegistrarCampoPersonalizadoSummary($saved, $ignoredEmpty, $invalid);
        } catch (\Throwable $exception) {
            Log::channel('process_job')->error('Falha ao registrar campo personalizado via tool.', $this->logContext([
                'args' => $arguments,
                'lead_id' => $lead?->id,
                'error' => $exception->getMessage(),
            ]));

            return 'Nao foi possivel registrar os campos personalizados.';
        }
    }

    /**
     * @return array<string, WhatsappCloudCustomField>
     */
    private function resolveAllowedLeadCustomFields(?ClienteLead $lead): array
    {
        if (!$lead) {
            return [];
        }

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
            ->get()
            ->mapWithKeys(function (WhatsappCloudCustomField $field) {
                $name = Str::lower(trim((string) $field->name));
                return $name !== '' ? [$name => $field] : [];
            })
            ->all();
    }

    private function buildRegistrarCampoPersonalizadoSummary(array $saved, array $ignoredEmpty, array $invalid): string
    {
        $parts = [];

        $saved = array_values(array_unique(array_filter(array_map(
            fn ($value) => trim((string) $value),
            $saved
        ))));
        $ignoredEmpty = array_values(array_unique(array_filter(array_map(
            fn ($value) => trim((string) $value),
            $ignoredEmpty
        ))));
        $invalid = array_values(array_unique(array_filter(array_map(
            fn ($value) => trim((string) $value),
            $invalid
        ))));

        if (!empty($saved)) {
            $parts[] = 'Campos salvos: ' . implode(', ', $saved) . '.';
        }

        if (!empty($ignoredEmpty)) {
            $parts[] = 'Ignorados por valor vazio: ' . implode(', ', $ignoredEmpty) . '.';
        }

        if (!empty($invalid)) {
            $parts[] = 'Invalidos: ' . implode(', ', $invalid) . '.';
        }

        if (empty($parts)) {
            return 'Nenhum campo valido informado.';
        }

        return implode(' ', $parts);
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

    public function uniqueId(): string
    {
        if ($this->cacheKey) {
            return $this->cacheKey;
        }

        $eventId = $this->payload['event_id'] ?? null;
        if (is_string($eventId) && $eventId !== '') {
            return 'event:' . $eventId;
        }

        $phone = (string) ($this->payload['phone'] ?? '');
        $payloadHash = sha1(json_encode($this->payload));

        return "msg:{$this->conexaoId}:{$phone}:{$payloadHash}";
    }

    public function uniqueFor(): int
    {
        return max(30, $this->maxWaitSeconds + 60);
    }

    private function logSilentReturn(string $reason, array $extra = []): void
    {
        Log::channel('process_job')->info('ProcessIncomingMessageJob concluido sem resposta ao usuario.', $this->logContext(array_merge([
            'reason' => $reason,
        ], $extra)));
    }

    public function failed(\Throwable $exception): void
    {
        Log::channel('process_job')->error('ProcessIncomingMessageJob failed', $this->logContext([
            'error' => $exception->getMessage(),
            'exception' => get_class($exception),
        ]));
    }
}
