<?php

namespace App\Services;

use App\Models\MassCampaign;
use App\Models\MassContact;
use App\Jobs\MassSendJob;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class MassSendService
{
    /**
     * Cria uma campanha, salva os contatos e dispara os jobs
     */
    public function criarCampanha(array $dados, string $caminhoCsv): MassCampaign
    {
        try {
            DB::beginTransaction();

            // 1️⃣ Cria a campanha
            $campanha = MassCampaign::create([
                'user_id' => Auth::id(),
                'instance_id' => $dados['instance_id'],
                'nome' => $dados['nome'] ?? 'Campanha ' . now()->format('d/m H:i'),
                'tipo_envio' => $dados['tipo_envio'],
                'usar_ia' => isset($dados['usar_ia']) && $dados['usar_ia'] == true,
                'mensagem' => $dados['mensagem'] ?? null,
                'intervalo_segundos' => (int)$dados['intervalo_segundos'] ?? 5,
                'status' => 'pendente',
            ]);

            // 2️⃣ Importa os números do CSV
            $numeros = $this->lerCsv($caminhoCsv);
            $total = 0;

            foreach ($numeros as $numero) {
                if (!$numero) continue;

                MassContact::create([
                    'campaign_id' => $campanha->id,
                    'numero' => $numero,
                    'status' => 'pendente',
                ]);

                $total++;
            }

            $campanha->update(['total_contatos' => $total]);
            DB::commit();

            // 3️⃣ Enfileira os envios (um job por contato)
            $this->dispararJobs($campanha);

            return $campanha;
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('MassSendService erro ao criar campanha', ['erro' => $e->getMessage()]);
            throw $e;
        }
    }

    /**
     * Lê um CSV e retorna um array de números
     */
    protected function lerCsv(string $caminho): array
    {
        $linhas = [];
        if (!file_exists($caminho)) {
            Log::error('CSV não encontrado', ['path' => $caminho]);
            return $linhas;
        }

        if (($handle = fopen($caminho, 'r')) !== false) {
            while (($data = fgetcsv($handle, 1000, ';')) !== false) {
                foreach ($data as $celula) {
                    $celula = trim($celula);
                    if ($celula) {
                        $numero = $this->formatarNumero($celula); //preg_replace('/\D/', '', $celula);

                        // ✅ só aceita números com pelo menos 10 dígitos
                        if (strlen($numero) >= 10) {
                            $linhas[] = $numero;
                        } else {
                            Log::warning('Número ignorado por ser curto', ['numero' => $celula]);
                        }
                    }
                }
            }
            fclose($handle);
        }

        return $linhas;
    }


    /**
     * Padroniza o número (remove caracteres não numéricos)
     */
    protected function formatarNumero(string $numero): string
    {
        $numero = preg_replace('/\D/', '', $numero);

        // Exemplo: 5562999998888 → 13 dígitos (já com DDI)
        $tamanho = strlen($numero);

        // Caso: número brasileiro sem DDI (geralmente 10 ou 11 dígitos)
        if ($tamanho <= 11) {
            $numero = '55' . $numero;
        }

        // Caso: número internacional já com DDI
        // Mantém como está (ex: 5491123456789, 351912345678, 59898765432)

        return $numero;
    } 


    /**
     * Dispara os jobs para a fila Redis
     */
    /*protected function dispararJobs(MassCampaign $campanha): void
    {
        foreach ($campanha->contatos as $contato) {
            MassSendJob::dispatch($contato->id)->delay(now()->addSeconds((int)$campanha->intervalo_segundos));
        }

        $campanha->update(['status' => 'executando']);
    }*/

    protected function dispararJobs(MassCampaign $campanha): void
    {
        $interval = max(1, (int) $campanha->intervalo_segundos);

        $i = 0;
        foreach ($campanha->contatos()->orderBy('id')->get() as $contato) {
            $offset = $i * $interval; // 0s, 5s, 10s, 15s...
            MassSendJob::dispatch($contato->id)
                ->delay(now()->addSeconds($offset));
            $i++;
        }

        $campanha->update(['status' => 'executando']);
    }

} 
