<?php

namespace App\Console\Commands;
use Illuminate\Console\Command;
use App\Models\LeadEmpresa;
use Illuminate\Support\Facades\Http;
use App\Services\OpenAIService;

class ColetarEmpresaNova extends Command
{
    protected $signature = 'coletar:empresa';
    protected $description = 'Consulta uma nova cidade e salva se for nova';

    public function handle(): void
     {

       $apiKey = 'AIzaSyDO7M1nSX8_yV1CQ1C1QIGBTjzrNTBgLns';

       $registros = LeadEmpresa::select('estado', 'cidade')->get();

        // Agrupar por estado => [cidade1, cidade2, ...]
        $cidadesOrganizadas = [];

        foreach ($registros as $item) {
            $estado = $item->estado;
            $cidade = $item->cidade;

            if (!$estado || !$cidade) continue;

            if (!isset($cidadesOrganizadas[$estado])) {
                $cidadesOrganizadas[$estado] = [];
            }

            // Evita cidades repetidas
            if (!in_array($cidade, $cidadesOrganizadas[$estado])) {
                $cidadesOrganizadas[$estado][] = $cidade;
            }
        }
        

        $resposta = Http::post('https://portalje.org/api/consultar/cidade', [
            'cidades_existentes' => $cidadesOrganizadas
        ]);

        
        $cidade = $resposta->json()['cidade'];
        $estado = $resposta->json()['estado'];
        
        //dd($resposta->json());
        

        // 2. PEGUE A BUSCA DA URL (ou defina um padrão)
        // Exemplo de como usar: http://seu-site.test/testar-maps?busca=restaurantes+em+sao+paulo
        $busca = "Empresas e Lojas em $cidade, $estado, Brasil";
        //dd($busca);
        

       // 3. FAÇA A REQUISIÇÃO PARA A API DO GOOGLE
        $response = Http::get('https://maps.googleapis.com/maps/api/place/textsearch/json', [
            'query' => $busca,
            'key' => $apiKey,
            'language' => 'pt-BR',
        ]);

        //dd($response->json());

        // 4. VERIFIQUE SE A REQUISIÇÃO FOI BEM SUCEDIDA E MOSTRE O RESULTADO
        if ($response->successful()) {
            // A função dd() do Laravel vai exibir o resultado de forma organizada e parar o script.
            // Pegamos apenas a chave 'results' que contém a lista de empresas.
            //dd($response->json()['results']);
            $empresas = $response->json()['results'];
        }

        foreach($empresas as $empresa){

            // --- PASSO 2: BUSCAR OS DETALHES DO PRIMEIRO LOCAL (Place Details) ---
            $detailsResponse = Http::get('https://maps.googleapis.com/maps/api/place/details/json', [
                'place_id' => $empresa['place_id'],
                'key' => $apiKey,
                'language' => 'pt-BR',
                'fields' => 'name,formatted_address,formatted_phone_number,international_phone_number' // Especifique os campos que você quer!
            ]);

            // Verifique se a busca por detalhes foi bem sucedida
            if ($detailsResponse->successful()) {
                // O dd() vai mostrar o resultado detalhado, agora com o telefone
                //dd($detailsResponse->json()['result']);

                $emp = $detailsResponse->json()['result'];

                if(isset($emp['international_phone_number'])){
                    $telefone = str_replace(['+', ' ', '-'], "", $emp['international_phone_number']);

                    LeadEmpresa::updateOrCreate(
                        ['telefone' => $telefone], // condição de busca
                        [
                            'nome' => $emp['name'] ?? null,
                            'cidade' => $cidade ?? null,
                            'estado' => $estado ?? null,
                        ]
                    );
                }


                
            }
            
        }
        
    }
}
