<?php

namespace App\Jobs;

use App\Models\Instance;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class ProvisionInstanceJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    // Propriedade para guardar a instância que estamos processando
    public $instance;

    /**
     * Create a new job instance.
     */
    public function __construct(Instance $instance)
    {
        $this->instance = $instance;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            // --- PASSO 1: OBTER PROXY DA WEBSHARE ---
            // Usa a chave do arquivo de configuração
            $proxyResponse = Http::withHeaders([
                'Authorization' => 'Bearer ' . config('services.webshare.key')
            ])->get('https://proxy.webshare.io/api/v2/proxy/list/');

            if ($proxyResponse->failed()) {
                throw new \Exception('Falha ao obter proxy do Webshare.');
            }
            
            $proxyData = $proxyResponse->json()['results'][0];

            // --- PASSO 2: CRIAR INSTÂNCIA NO EVOLUTION ---
            // Usa a chave e a URL do arquivo de configuração
            $evolutionUrl = config('services.evolution.url') . '/instance/create';

            $evolutionResponse = Http::withHeaders([
                'apiKey' => config('services.evolution.key')
            ])->post($evolutionUrl, [
                'instanceName' => $this->instance->id,
                'token' => null,
                'qrcode' => true,
                'proxy' => "http://{$proxyData['username']}:{$proxyData['password']}@{$proxyData['proxy_address']}:{$proxyData['ports']['http']}"
            ]);

            if ($evolutionResponse->failed()) {
                throw new \Exception('Falha ao criar instância no Evolution. Resposta: ' . $evolutionResponse->body());
            }

            $evolutionData = $evolutionResponse->json();

            // --- PASSO 3: ATUALIZAR NOSSO BANCO DE DADOS (código inalterado) ---
            $this->instance->update([
                'evolution_api_key' => $evolutionData['instance']['token'],
                'proxy_ip' => $proxyData['proxy_address'],
                'proxy_port' => $proxyData['ports']['http'],
                'proxy_username' => $proxyData['username'],
                'proxy_password' => $proxyData['password'],
                'proxy_provider' => 'webshare',
                'status' => 'active',
            ]);

        } catch (Throwable $e) {
            Log::error('Erro ao provisionar instância ID ' . $this->instance->id . ': ' . $e->getMessage());
            $this->instance->update(['status' => 'error']);
        }
    }
}