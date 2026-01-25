<?php

namespace App\Services;

use App\Models\Instance; // <-- IMPORTANTE: Precisamos do modelo Instance aqui
use App\Models\ProxyIpBan;
use App\Models\UazapiInstance; 
use App\Models\Conexao;
use Illuminate\Support\Facades\Http;

class WebshareService
{
    protected $apiKey;
    protected $apiUrl = 'https://proxy.webshare.io/api/v2/';

    public function __construct()
    {
        $this->apiKey = config('services.webshare.key');
    }

    /**
     * Encontra e retorna o primeiro proxy disponível que ainda não está em uso.
     *
     * @return array
     * @throws \Exception
     */
    public function getNewProxy(): array
    {
        // 1. Pega todos os IPs de proxy que já estão em uso no nosso banco de dados.
        // O método pluck é extremamente eficiente para isso.
        $usedIps = Instance::pluck('proxy_ip')->toArray();
        $usedIpsUazapi = UazapiInstance::pluck('proxy_ip')->toArray();
        $usedIpsConexao = Conexao::pluck('proxy_ip')->toArray();
        $bannedIps = ProxyIpBan::pluck('ip')->toArray();
        $blockedIps = array_filter(array_merge($usedIps, $bannedIps, $usedIpsUazapi, $usedIpsConexao));

        $page = 1;
        $pageSize = 25; // Podemos ajustar se necessário

        // 2. Inicia um loop que continuará até encontrarmos um proxy ou acabarem as opções.
        while (true) {
            
            // 3. Faz a chamada para a API do Webshare, pedindo a página atual.
            $response = Http::withHeaders([
                'Authorization' => 'Token ' . $this->apiKey,
            ])->get($this->apiUrl . 'proxy/list/', [
                'page' => $page,
                'page_size' => $pageSize,
                'mode' => 'direct',
            ]);

            if ($response->failed()) {
                throw new \Exception('Webshare API: Falha ao obter a lista de proxies. Status: ' . $response->status());
            }

            $proxiesOnPage = $response->json()['results'];

            // 4. Se a página não retornar nenhum proxy, significa que chegamos ao fim da lista.
            if (empty($proxiesOnPage)) {
                // Não há mais proxies para verificar em nenhuma página.
                throw new \Exception('Não foi possível encontrar um proxy disponível. Todos os proxies da sua conta Webshare parecem estar em uso.');
            }

            // 5. Itera sobre cada proxy retornado na página atual.
            foreach ($proxiesOnPage as $proxy) {
                // 6. Verifica se o IP do proxy atual NÃO está na nossa lista de IPs já usados.
                if (!in_array($proxy['proxy_address'], $blockedIps)) {
                    // SUCESSO! Encontramos um proxy livre.
                    // Retorna os dados dele imediatamente, encerrando o loop e a função.
                    return $proxy;
                }
            }

            // 7. Se o loop terminar e não encontrarmos um proxy livre,
            // significa que todos os 25 desta página estão em uso.
            // Incrementamos o número da página e o `while(true)` fará a próxima iteração.
            $page++;
        }
    }
}