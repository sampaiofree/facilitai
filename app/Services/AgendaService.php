<?php

namespace App\Services;

use App\Models\Agenda;
use App\Models\Disponibilidade;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class AgendaService
{
    /**
     * Retorna os horários disponíveis em uma agenda
     * dentro de um período de datas.
     */
   public function getDisponiveisPorPeriodo(int $agendaId, string $dataInicio, string $dataFim)
{
    // Define início e fim do período informado
    $inicio = Carbon::parse($dataInicio)->startOfDay();
    $fim = Carbon::parse($dataFim)->endOfDay();

    // Garante que o início nunca será anterior a hoje
    $hoje = Carbon::today();
    if ($inicio->lt($hoje)) {
        $inicio = $hoje;
    }

    return Disponibilidade::where('agenda_id', $agendaId)
        ->whereBetween('data', [$inicio->toDateString(), $fim->toDateString()])
        ->where('ocupado', false)
        ->where(function ($query) use ($hoje) {
            // Ignora horários cuja data já passou (inclusive hoje antes do horário atual)
            $query->where('data', '>', $hoje->toDateString())
                  ->orWhere(function ($q) use ($hoje) {
                      $q->where('data', $hoje->toDateString())
                        ->where('inicio', '>=', now()->format('H:i:s'));
                  });
        })
        ->orderBy('data')
        ->orderBy('inicio')
        ->get();
}


    /**
     * Preenche (agenda) um horário disponível.
     */
    public function preencherHorario(int $disponibilidadeId, string $nome, string $telefone, ?int $chatId = null)
    {
        $disp = Disponibilidade::find($disponibilidadeId);

        if (!$disp) {
            return ['success' => false, 'message' => 'Horário não encontrado.'];
        }

        if ($disp->ocupado) {
            return ['success' => false, 'message' => 'Horário já ocupado.'];
        }

        $disp->update([
            'nome' => $nome,
            'telefone' => $telefone,
            'chat_id' => $chatId,
            'ocupado' => true,
        ]);

        return ['success' => true, 'data' => $disp->fresh()];
    }

    /**
     * Cancela um horário, desde que pertença ao mesmo chat_id.
     */
    public function cancelarHorario(int $disponibilidadeId, ?int $chatId = null)
    {
        $disp = Disponibilidade::find($disponibilidadeId);

        if (!$disp) {
            return ['success' => false, 'message' => 'Horário não encontrado.'];
        }

        if (!$disp->ocupado) {
            return ['success' => false, 'message' => 'Este horário já está livre.'];
        }

        if ($disp->chat_id !== $chatId) {
            return ['success' => false, 'message' => 'Você não tem permissão para cancelar este horário.'];
        }

        $disp->update([
            'ocupado' => false,
            'nome' => null,
            'telefone' => null,
            'chat_id' => null,
        ]);

        return ['success' => true, 'data' => $disp];
    }

    /**
     * Método principal da tool para gerenciar qualquer operação na agenda.
     *
     * @param  string $acao        Ex: consultar, agendar, cancelar, alterar
     * @param  array  $dados       [
     *   'agenda_id' => int,
     *   'chat_id'   => int,
     *   'telefone'  => string,
     *   'nome'      => string,
     *   'data_inicio' => string,
     *   'data_fim'    => string,
     *   'disponibilidade_id' => int (quando aplicável)
     * ]
     * @return array
     */
    public function executarAcao(string $acao, array $dados)
{
    try {
        // 🔹 Converte o campo `mes` em intervalo de datas (caso exista)
        $mes = $dados['mes'] ?? now()->month;
        $ano = now()->year;

        $dataInicio = Carbon::create($ano, $mes, 1)->startOfMonth();
        $dataFim = $dataInicio->copy()->endOfMonth();

        switch (strtolower($acao)) {
            case 'consultar':
                $result = $this->getDisponiveisPorPeriodo(
                    $dados['agenda_id'],
                    $dataInicio->toDateString(),
                    $dataFim->toDateString()
                );

                return [
                    'success' => true,
                    'count' => $result->count(),
                    'data' => $result
                ];

            case 'agendar':
                if (empty($dados['disponibilidade_id']) || empty($dados['nome']) || empty($dados['telefone'])) {
                    return ['success' => false, 'message' => 'Campos obrigatórios ausentes para agendar.'];
                }

                return $this->preencherHorario(
                    $dados['disponibilidade_id'],
                    $dados['nome'],
                    $dados['telefone'],
                    $dados['chat_id'] ?? null
                );

            case 'cancelar':
                if (empty($dados['disponibilidade_id']) || empty($dados['chat_id'])) {
                    return ['success' => false, 'message' => 'ID da disponibilidade e chat_id são obrigatórios.'];
                }

                return $this->cancelarHorario(
                    $dados['disponibilidade_id'],
                    $dados['chat_id']
                );

            case 'alterar':
                if (empty($dados['disponibilidade_id']) || empty($dados['chat_id']) || empty($dados['nova_disponibilidade_id'])) {
                    return ['success' => false, 'message' => 'Campos obrigatórios ausentes para alterar.'];
                }

                $cancel = $this->cancelarHorario($dados['disponibilidade_id'], $dados['chat_id']);
                if (!$cancel['success']) {
                    return $cancel;
                }

                return $this->preencherHorario(
                    $dados['nova_disponibilidade_id'],
                    $dados['nome'] ?? '',
                    $dados['telefone'] ?? '',
                    $dados['chat_id']
                );

            default:
                return ['success' => false, 'message' => 'Ação inválida.'];
        }

    } catch (\Throwable $e) {
        Log::error('Erro em AgendaService@executarAcao', [
            'acao' => $acao,
            'dados' => $dados,
            'erro' => $e->getMessage(),
        ]);

        return ['success' => false, 'message' => 'Erro interno: ' . $e->getMessage()];
    }
}

}
