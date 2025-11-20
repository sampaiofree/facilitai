<?php

namespace App\Jobs;

use App\Models\MassContact;
use App\Models\MassCampaign;
use App\Services\EvolutionService;
use App\Services\ConversationsService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class MassSendJob implements ShouldQueue 
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected int $contactId;

    /**
     * Cria um novo Job.
     */
    public function __construct(int $contactId)
    {
        $this->contactId = $contactId;
    }

    /**
     * Executa o Job.
     */
    public function handle(): void
    {
        // Busca o contato
        $contato = MassContact::find($this->contactId);

        if (!$contato) {
            Log::warning("MassSendJob: contato não encontrado ID {$this->contactId}");
            return;
        }

        $campanha = $contato->campaign;

        if (!$campanha) {
            Log::warning("MassSendJob: campanha não encontrada para contato {$contato->id}");
            return;
        }

        // Se a campanha foi pausada ou encerrada, cancela o envio
        if (in_array($campanha->status, ['pausado', 'concluido'])) {
            Log::info("MassSendJob: campanha pausada ou concluída ({$campanha->id}), cancelando envio");
            return;
        }

        $telefone = $contato->numero;
        $mensagem = $campanha->mensagem;
        $instancia = $campanha->instance_id;

        try {
            
            $service = new ConversationsService($mensagem, $telefone, $instancia);
            $service->enviarMSG();
            
            /*if ($campanha->usar_ia) {
                // ✅ Enviar usando IA
                $service = new ConversationsService($mensagem, $telefone, $instancia);
                $service->enviarMSG();
            } else {
                // ✅ Enviar direto pelo Evolution
                $evo = new EvolutionService();
                $evo->enviar_msg_evolution($telefone, $mensagem, $instancia);
            }*/

            // Marca o contato como enviado
            $contato->update([
                'status' => 'enviado',
                'tentativa' => $contato->tentativa + 1,
                'enviado_em' => now(),
            ]);

            // Atualiza contagem na campanha
            $campanha->increment('enviados');

            // Espera o intervalo definido antes do próximo envio
            //sleep($campanha->intervalo_segundos);

        } catch (\Throwable $e) {
            Log::error("MassSendJob erro: {$e->getMessage()}", [
                'campanha' => $campanha->id,
                'contato' => $contato->numero,
            ]);

            $contato->update([
                'status' => 'falhou',
                'tentativa' => $contato->tentativa + 1,
            ]);

            $campanha->increment('falhas');
        }

        // Se todos os contatos já foram enviados, marca campanha como concluída
        $total = $campanha->contatos()->count();
        $enviados = $campanha->contatos()->where('status', 'enviado')->count();

        if ($enviados >= $total) {
            $campanha->update(['status' => 'concluido']);
        }
    }
}
