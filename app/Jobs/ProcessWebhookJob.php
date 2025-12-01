<?php

namespace App\Jobs;

use App\Models\Instance;
use App\Models\Chat;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use App\Services\OpenAIService;

class ProcessWebhookJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1;
    public int $timeout = 120;
    public int $backoff = 30;

    protected Instance $instance;
    protected string $contactNumber;
    protected array $webhookData;

    public function __construct(Instance $instance, string $contactNumber, array $webhookData)
    {
        $this->instance = $instance;
        $this->contactNumber = $contactNumber;
        $this->webhookData = $webhookData;
    }

    public function handle(): void
    {
        try {
            if (!$this->initialValidation()) {
                return;
            }

            $messageType = $this->webhookData['messageType'] ?? 'unknown';
            $messageData = $this->webhookData['message'] ?? null;


            if (!$messageData || $messageType === 'unknown' || $messageType === 'reactionMessage') {
                //Log::warning("ProcessWebhookJob:43");
                return;
            }

            $credential = $this->instance->credential;
            $assistantId = $this->instance->default_assistant_id;

            $chat = Chat::firstOrCreate(
                ['instance_id' => $this->instance->id, 'contact' => $this->contactNumber],
                ['user_id' => $this->instance->user_id, 'assistant_id' => $assistantId]
            );

            // CORREÇÃO: Garante que temos um thread_id ANTES de qualquer outra coisa
            // Se o thread_id for nulo, cria um imediatamente.
            if (is_null($chat->thread_id)) {
                try {
                    // Precisamos de uma forma de criar o thread sem enviar mensagem
                    $openai = new OpenAIService($credential->token);
                    $threadId = $openai->createThread(); // <--- PRECISAMOS DESTE NOVO MÉTODO
                    $chat->thread_id = $threadId;
                    $chat->save();
                    //Log::info("Novo thread_id {$threadId} criado e salvo para o chat.");
                } catch (\Exception $e) {
                    Log::error("Não foi possível criar um novo thread para o chat: " . $e->getMessage());
                    return; // Falha crítica, não podemos continuar.
                }
            }

            $key = "chat:{$chat->thread_id}:buffer";
            $messages = Cache::get($key, []);
            $messages[] = [
                'type' => $messageType,
                'data' => $messageData,
                'timestamp' => now()->timestamp,
            ];
            Cache::put($key, $messages, now()->addSeconds(15)); 

            $jobKey = "chat:{$chat->thread_id}:job_scheduled";
            if (!Cache::has($jobKey)) {
                Cache::put($jobKey, true, now()->addSeconds(10));
                \App\Jobs\ProcessBufferedMessagesJob::dispatch(
                    $this->instance,
                    $chat,
                    $assistantId
                )->delay(now()->addSeconds(5));
            }

        } catch (\Throwable $e) {
            Log::error("Erro no Job ProcessWebhookJob para contato {$this->contactNumber}: " . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
            ]);
        }
    }

    private function initialValidation(): bool
    {
        if (!$this->instance->default_assistant_id) {
            Log::warning("Instância {$this->instance->id} não possui um assistente vinculado. Job encerrado.");
            return false;
        }

        if (!$this->instance->credential) {
            Log::warning("Instância {$this->instance->id} não possui uma credencial vinculada. Job encerrado.");
            return false;
        }

        $chat = Chat::where('instance_id', $this->instance->id)
            ->where('contact', $this->contactNumber)
            ->first();

        if ($chat && $chat->bot_enabled === false) {
            Log::info("Bot desativado para o contato {$this->contactNumber}. Job encerrado.");
            return false;
        }

        return true;
    }
}
