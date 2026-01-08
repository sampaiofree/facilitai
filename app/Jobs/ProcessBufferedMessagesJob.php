<?php

namespace App\Jobs;

use App\Models\Chat;
use App\Models\Instance;
use App\Services\OpenAIService;
use Illuminate\Bus\Queueable;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

class ProcessBufferedMessagesJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1;
    public int $timeout = 180;
    public int $backoff = 30;

    public Instance $instance;
    public Chat $chat;
    public string $assistantId;

    public function __construct(Instance $instance, Chat $chat, string $assistantId)
    {
        $this->instance = $instance;
        $this->chat = $chat;
        $this->assistantId = $assistantId;
    }

    public function handle(): void
    {
        $key = "chat:{$this->chat->thread_id}:buffer";
        $jobKey = "chat:{$this->chat->thread_id}:job_scheduled"; 

        $mensagens = Cache::pull($key, []);
        Cache::forget($jobKey);

        if (empty($mensagens)) {
            
            return;
        }

        $openai = new OpenAIService($this->instance->credential->token);
        $conteudosInterpretados = [];

        foreach ($mensagens as $msg) {
            $interpreted = $this->interpretarMensagem($msg, $openai);
            if ($interpreted) {
                $conteudosInterpretados[] = $interpreted;
            }
        }

        $conteudoFinal = implode("\n", $conteudosInterpretados);
        

        $result = $openai->processMessage($this->assistantId, $this->chat->thread_id, $conteudoFinal, $this->instance->id, $this->chat->contact);

        //SEMPRE SALVAR NOVA THREAD
        if ($this->chat->thread_id !== $result['thread_id']) {
            $this->chat->thread_id = $result['thread_id'];
            $this->chat->save();
        }
        $this->chat->touch();

        $resposta = $result['response']; 

        if (!empty($resposta)) {
            $url = config('services.evolution.url') . "/message/sendText/{$this->instance->id}";
            $payload = [
                'number' => $this->chat->contact,
                'text' => $resposta,
            ];

            
            
            

            $response = Http::withHeaders([
                'apiKey' => config('services.evolution.key')
            ])->post($url, $payload);

            // Loga o status e o corpo completo da resposta
            
            
        }
    }


    private function interpretarMensagem(array $msg, OpenAIService $openai): ?string
    {
        $type = $msg['type'] ?? '';
        $data = $msg['data'] ?? [];

        if ($type === 'conversation') {
            return $data['conversation'] ?? null;
        }

        if ($type === 'imageMessage') {
            $caption = $data['imageMessage']['caption'] ?? '';
            $base64 = $data['base64'] ?? null;

            

            if ($base64) {
                try {
                    $descricao = $openai->descreverImagemBase64($base64);
                    return trim($caption . "\n[Descrição da imagem: {$descricao}]");
                } catch (\Throwable $e) {
                    Log::error("Erro ao descrever imagem: " . $e->getMessage());
                    return $caption ?: '[Imagem recebida]';
                }
            } else {
                return $caption ?: '[Imagem recebida sem conteúdo]';
            }
        }

        if ($type === 'audioMessage') {
            $base64 = $data['base64'] ?? null;

            if ($base64) {
                try {
                    $transcricao = $openai->transcreverAudioBase64($base64);
                    return trim("[Transcrição do áudio]: {$transcricao}");
                } catch (\Throwable $e) {
                    Log::error("Erro ao transcrever áudio: " . $e->getMessage());
                    return '[Áudio recebido, mas não foi possível transcrever]';
                }
            } else {
                return '[Áudio recebido sem conteúdo]';
            }
        }

        return null;
    }
}
