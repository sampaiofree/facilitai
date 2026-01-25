<?php

namespace App\Jobs;

use App\Models\AssistantLead;
use App\Models\ClienteLead;
use App\Models\Conexao;
use App\Services\OpenAIService;
use App\Services\MediaDecryptService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class UazapiJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1;
    public int $timeout = 60;
    public int $backoff = 15;

    protected array $payload;
    protected ?string $cacheKey;
    protected bool $isMedia;
    protected int $debounceSeconds;
    protected int $maxWaitSeconds;

    public function __construct(array $payload, ?string $cacheKey = null, bool $isMedia = false, int $debounceSeconds = 5, int $maxWaitSeconds = 40)
    {
        $this->payload = $payload;
        $this->cacheKey = $cacheKey;
        $this->isMedia = $isMedia;
        $this->debounceSeconds = $debounceSeconds;
        $this->maxWaitSeconds = $maxWaitSeconds;
    }

    public function handle(): void
    {
        // Se esta execução veio de um re-dispatch para aplicar debounce, use o fluxo dedicado.
        if (!empty($this->cacheKey)) {
            $this->handleDebounce($service);
            return;
        }

        $evento = (string) ($this->payload['evento'] ?? '');
        if ($evento !== 'messages') {
            return;
        }

        $tipoMensagem = (string) ($this->payload['tipo'] ?? '');
        $payload = is_array($this->payload['payload'] ?? null) ? $this->payload['payload'] : [];

        // Extrai os dados brutos recebidos e prepara os campos usados a seguir.
        $chat = Arr::get($payload, 'chat', []);
        $message = Arr::get($payload, 'message', []);

        $chatFields = [
            'id',
            'phone',
            'wa_chatid',
            'wa_fastid',
            'wa_lastMessageTextVote',
            'wa_lastMessageType',
            'wa_lastMsgTimestamp',
        ];

        $sharedMessageFields = [
            'fromMe',
            'isGroup',
            'chatid',
            'sender',
            'sender_pn',
            'content',
            'text',
            'messageType',
            'senderName',
        ];

        $mediaFieldsMap = [
            'AudioMessage' => [
                'content.URL',
                'content.fileSHA256',
                'content.fileEncSHA256',
                'content.mediaKey',
                'content.directPath',
            ],
            'ImageMessage' => [
                'content.URL',
                'content.mimetype',
                'content.fileSHA256',
                'content.fileEncSHA256',
                'content.mediaKey',
                'content.directPath',
                'content.JPEGThumbnail',
            ],
            'DocumentMessage' => [
                'content.URL',
                'content.mimetype',
                'content.fileSHA256',
                'content.fileEncSHA256',
                'content.mediaKey',
                'content.directPath',
                'content.JPEGThumbnail',
            ],
        ];

        $messageFields = $sharedMessageFields;
        if (isset($mediaFieldsMap[$tipoMensagem])) {
            $messageFields = array_merge($messageFields, $mediaFieldsMap[$tipoMensagem]);
        }

        $chatSummary = $this->pluckKeys($chat, $chatFields);
        $messageSummary = $this->pluckKeys($message, $messageFields);
        $tipoNormalizado = $this->normalizeTipo($tipoMensagem, $message);
        $token = Arr::get($payload, 'token');

        $fromMe = Arr::get($message, 'fromMe');
        $text = Arr::get($message, 'text', '');
        $eventId = Arr::get($message, 'id') ?? Arr::get($message, 'messageid');
        $messageTimestamp = Arr::get($message, 'messageTimestamp') ?? Arr::get($chat, 'wa_lastMsgTimestamp');
        $messageType = Arr::get($message, 'messageType') ?? Arr::get($message, 'type');

        // Valida se existe uma conexão ativa para esse token e se o usuário ainda está ativo.
        $conexao = $token ? Conexao::with('cliente')->where('whatsapp_api_key', $token)->first() : null;
        if (!$conexao) {
            return;
        }

        if (!$this->userStatus($conexao)) {
            return;
        }

        $data = [
            'evento' => $evento,
            'tipo' => $tipoNormalizado,
            'token' => $token,
            'chat' => $chatSummary,
            'message' => $messageSummary,
            'received_at' => $this->payload['received_at'] ?? null,
        ];

        // Ignora mensagens de grupo e payloads sem token válido antes de prosseguir.
        $isGroup = Arr::get($message, 'isGroup');
        if ($isGroup === true) {
            return;
        }

        if (empty($token)) {
            return;
        }

        $phone = $this->resolveWhatsappNumber($message, $chat);
        if (!$phone) {
            return;
        }

        $data['phone'] = $phone;
        $data['conexao_id'] = $conexao->id;
        $data['assistant_version'] = $conexao->assistant?->version ?? 1;

        $leadName = $this->resolveLeadName($message, $chat, $phone);

        $clienteLead = ClienteLead::firstOrNew([
            'cliente_id' => $conexao->cliente_id,
            'phone' => $phone,
        ]);

        if (!$clienteLead->exists) {
            $clienteLead->fill([
                'name' => $leadName,
                'info' => null,
                'bot_enabled' => true,
            ]);
            $clienteLead->save();
        }

        if ($fromMe === true) {
            $botEnabled = str_contains($text, '#');
            $clienteLead->bot_enabled = $botEnabled;
            $clienteLead->save();
            return;
        }

        if (!$clienteLead->bot_enabled) {
            return;
        }

        $dedupKeySeed = $eventId ?: hash('sha256', json_encode([
            $conexao->id,
            $phone,
            $messageTimestamp,
            $text,
            $messageType,
        ]));
        $dedupKey = "uazapi:webhook:{$conexao->id}:{$dedupKeySeed}";
        if (!Cache::add($dedupKey, true, now()->addMinutes(10))) {
            return;
        }

        $lead = $clienteLead;
        $lead->fill([
            'name' => $leadName,
            'info' => null,
        ]);
        if ($lead->isDirty()) {
            $lead->save();
        }

        $openAiService = null;
        if ($conexao->assistant_id) {
            $assistantLead = AssistantLead::where('lead_id', $lead->id)
                ->where('assistant_id', $conexao->assistant_id)
                ->first();

            if (!$assistantLead || empty($assistantLead->conv_id)) {
                $openAiService = $this->createOpenAIService($conexao);
                if (!$openAiService) {
                    Log::channel('uazapijob')->error('Falha ao inicializar OpenAIService para criar conversa.', [
                        'conexao_id' => $conexao->id,
                        'assistant_id' => $conexao->assistant_id,
                        'lead_id' => $lead->id,
                    ]);
                    return;
                }

                $convId = $openAiService->createConversation();
                if (!$convId) {
                    Log::channel('uazapijob')->error('Falha ao criar conversation na OpenAI.', [
                        'conexao_id' => $conexao->id,
                        'assistant_id' => $conexao->assistant_id,
                        'lead_id' => $lead->id,
                    ]);
                    return;
                }

                if (!$assistantLead) {
                    AssistantLead::create([
                        'lead_id' => $lead->id,
                        'assistant_id' => $conexao->assistant_id,
                        'version' => $conexao->assistant?->version ?? 1,
                        'conv_id' => $convId,
                    ]);
                } else {
                    $assistantLead->conv_id = $convId;
                    $assistantLead->save();
                }
            }
        }

        $openAiService = $openAiService ?? $this->createOpenAIService($conexao);
        if (!$openAiService) {
            Log::channel('uazapijob')->error('Falha ao inicializar OpenAIService para processar mensagem.', [
                'conexao_id' => $conexao->id,
                'assistant_id' => $conexao->assistant_id,
                'lead_id' => $lead->id,
            ]);
            return;
        }

        // Verifica se o payload contém mídia para descriptografar antes de enviar à OpenAI.
        if ($this->isMediaType($tipoNormalizado)) {
            $mediaDecrypt = new MediaDecryptService();
            $mediaPayload = $mediaDecrypt->decrypt($message, $tipoNormalizado);
            if ($mediaPayload) {
                $data['media'] = $mediaPayload;
            }
        }

        // Mensagens com mídia são processadas imediatamente.
        if ($tipoNormalizado !== 'text') {
            $data['is_media'] = true;
            $openAiService->handle($data);
            return;
        }

        // Se o cache estiver indisponível, processamos o texto na hora (sem debounce).
        if (!$this->cacheDisponivel()) {
            $data['is_media'] = false;
            $openAiService->handle($data);
            return;
        }

        // Armazena o buffer de conversas para aplicar debounce nas mensagens subsequentes.
        $cacheKey = "uazapi:conv:{$conexao->id}:{$phone}";
        $buffer = Cache::get($cacheKey, []);
        $agora = Carbon::now()->timestamp;

        if (empty($buffer)) {
            $buffer = [
                'started_at' => $agora,
                'last_at' => $agora,
                'messages' => [$text],
                'data' => $data,
            ];
        } else {
            $buffer['last_at'] = $agora;
            $buffer['messages'][] = $text;
            $buffer['messages'] = array_slice($buffer['messages'], -10);
            $buffer['data'] = $data;
        }

        Cache::put($cacheKey, $buffer, now()->addSeconds(120));

        // Re-dispatch para cuidar do debounce nos próximos segundos.
        self::dispatch($data, $cacheKey, false, $this->debounceSeconds, $this->maxWaitSeconds)
            ->delay(now()->addSeconds($this->debounceSeconds));
    }

    private function handleDebounce(): void
    {
        $buffer = Cache::get($this->cacheKey);
        if (empty($buffer) || empty($buffer['messages'])) {
            return;
        }

        $now = Carbon::now();
        $lastAt = Carbon::createFromTimestamp($buffer['last_at'] ?? $now->timestamp);
        $startedAt = Carbon::createFromTimestamp($buffer['started_at'] ?? $now->timestamp);

        // Verifica se ainda deve esperar mais mensagens antes de enviar (debounce).
        if ($lastAt->gt($now->subSeconds($this->debounceSeconds)) && $startedAt->gt($now->subSeconds($this->maxWaitSeconds))) {
            self::dispatch($this->payload, $this->cacheKey, $this->isMedia, $this->debounceSeconds, $this->maxWaitSeconds)
                ->delay(now()->addSeconds($this->debounceSeconds));
            return;
        }

        $combined = implode("\n", $buffer['messages']);
        $payload = is_array($buffer['data'] ?? null) ? $buffer['data'] : $this->payload;
        $payload['is_media'] = $this->isMedia;

        if (!isset($payload['message']) || !is_array($payload['message'])) {
            $payload['message'] = [];
        }

        if ($combined !== '') {
            $payload['message']['text'] = $combined;
            $payload['combined_text'] = $combined;
        }

        Cache::forget($this->cacheKey);

        // Finalmente entrega o payload combinado para o serviço responsável.
        $openAiService = $this->createOpenAIServiceFromPayload($payload);
        if (!$openAiService) {
            return;
        }
        $openAiService->handle($payload);
    }

    private function pluckKeys(array $source, array $keys): array
    {
        // Extrai somente os campos relevantes de cada payload para controle interno.
        $result = [];
        foreach ($keys as $key) {
            if (Arr::has($source, $key)) {
                $result[$key] = Arr::get($source, $key);
            }
        }

        return $result;
    }

    private function resolveLeadName(array $message, array $chat, string $fallback): string
    {
        // Normaliza o nome do destinatário, com fallback para o número caso nada seja fornecido.
        $candidate = Arr::get($message, 'senderName')
            ?? Arr::get($message, 'sender')
            ?? Arr::get($chat, 'wa_lastMessageSender')
            ?? Arr::get($chat, 'senderName')
            ?? $fallback;

        $normalized = trim((string) $candidate);
        return $normalized !== '' ? $normalized : $fallback;
    }

    private function isMediaType(string $tipo): bool
    {
        $tipoLower = Str::lower($tipo);
        return str_contains($tipoLower, 'audio')
            || str_contains($tipoLower, 'image')
            || str_contains($tipoLower, 'video')
            || str_contains($tipoLower, 'document');
    }

    private function resolveWhatsappNumber(array $message, array $chat): ?string
    {
        // Tenta obter um número normalize considerando campos conhecidos de mensagem/chat.
        $candidates = [
            Arr::get($message, 'sender_pn'),
            Arr::get($message, 'chatid'),
            Arr::get($message, 'sender'),
            Arr::get($chat, 'wa_chatid'),
            Arr::get($chat, 'wa_lastMessageSender'),
        ];

        foreach ($candidates as $candidate) {
            $normalized = $this->normalizeWhatsappNumber($candidate);
            if ($normalized) {
                return $normalized;
            }
        }

        return null;
    }

    private function normalizeWhatsappNumber(?string $value): ?string
    {
        // Remove o sufixo '@s.whatsapp.net' e valida o tamanho do número.
        if (empty($value)) {
            return null;
        }

        $value = trim($value);
        $lower = Str::lower($value);

        if (!Str::endsWith($lower, '@s.whatsapp.net')) {
            return null;
        }

        $number = str_replace('@s.whatsapp.net', '', $lower);
        $digits = preg_replace('/\\D/', '', $number);

        if (strlen($digits) < 11 || strlen($digits) > 14) {
            return null;
        }

        return $digits;
    }

    private function normalizeTipo(string $tipoMensagem, array $message): string
    {
        // Garante que sempre temos um tipo definido, com fallback para 'unknown'.
        $value = trim($tipoMensagem);
        if ($value === '') {
            $value = (string) (Arr::get($message, 'messageType') ?? Arr::get($message, 'type'));
        }

        $lower = Str::lower($value);
        if (in_array($lower, ['text', 'conversation'], true)) {
            return 'text';
        }

        return $value !== '' ? $value : 'unknown';
    }

    private function cacheDisponivel(): bool
    {
        // Verifica se podemos usar o cache para implementar debounce; falha silenciosa se não houver.
        try {
            $key = 'uazapi_cache_test_' . uniqid();
            Cache::put($key, 1, 5);
            Cache::forget($key);
            return true;
        } catch (\Throwable $e) {
            return false;
        }
    }

    private function userStatus(Conexao $conexao): bool
    {
        // Garante que a conexão pertence a um cliente com usuário válido antes de continuar.
        return !empty($conexao->cliente) && !empty($conexao->cliente->user_id);
    }

    private function createOpenAIService(Conexao $conexao): ?OpenAIService
    {
        try {
            return new OpenAIService($conexao);
        } catch (\Throwable $exception) {
            Log::channel('uazapijob')->error('OpenAIService initialization failed', [
                'conexao_id' => $conexao->id,
                'error' => $exception->getMessage(),
            ]);
        }

        return null;
    }

    private function createOpenAIServiceFromPayload(array $payload): ?OpenAIService
    {
        $conexaoId = Arr::get($payload, 'conexao_id');
        if (!$conexaoId) {
            Log::channel('uazapijob')->error('OpenAIService payload missing conexao_id');
            return null;
        }

        $conexao = Conexao::find($conexaoId);
        if (!$conexao) {
            Log::channel('uazapijob')->error('OpenAIService conexao not found for payload', [
                'conexao_id' => $conexaoId,
            ]);
            return null;
        }

        return $this->createOpenAIService($conexao);
    }
}
