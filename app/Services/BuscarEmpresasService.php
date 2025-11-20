<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class BuscarEmpresasService
{
    protected string $apiKey;

    public function __construct()
    {
        $this->apiKey = "AIzaSyDO7M1nSX8_yV1CQ1C1QIGBTjzrNTBgLns"; //config('services.google.api_key'); // coloque sua chave em config/services.php
    }

    /**
     * Busca empresas e lojas em uma cidade e estado do Brasil
     */
    public function buscar(string $segmento, string $cidade, string $estado): array
    {
        $busca = "{$segmento} em {$cidade}, {$estado}, Brasil";

        $response = Http::get('https://maps.googleapis.com/maps/api/place/textsearch/json', [
            'query' => $busca,
            'key' => $this->apiKey,
            'language' => 'pt-BR',
        ]);

        if (!$response->successful()) {
            return [];
        }

        $empresas = $response->json()['results'] ?? [];
        $lista = [];

        foreach ($empresas as $empresa) {
            if (!isset($empresa['place_id'])) {
                continue;
            }

            // --- PASSO 2: BUSCAR OS DETALHES DO LOCAL ---
            $detailsResponse = Http::get('https://maps.googleapis.com/maps/api/place/details/json', [
                'place_id' => $empresa['place_id'],
                'key' => $this->apiKey,
                'language' => 'pt-BR',
                'fields' => 'name,formatted_address,formatted_phone_number,international_phone_number,website',
            ]);

            if (!$detailsResponse->successful()) {
                continue;
            }

            //dd($detailsResponse->json());

            $dados = $detailsResponse->json()['result'] ?? [];

            // --- Monta os dados limpos ---
            $lista[] = [
                'nome' => $dados['name'] ?? $empresa['name'] ?? null,
                'endereco' => $dados['formatted_address'] ?? null,
                'telefone' => isset($dados['international_phone_number'])
                    ? str_replace(['+', ' ', '-'], '', $dados['international_phone_number'])
                    : ($dados['formatted_phone_number'] ?? null),
                'website' => $dados['website'] ?? null,
            ];
        }

        return $lista;
    }
}
