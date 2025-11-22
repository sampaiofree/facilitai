<?php

namespace App\Services;

use App\Models\Agenda;
use App\Models\Disponibilidade;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
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
     *   'data_inicio' => string (opcional),
     *   'data_fim'    => string (opcional),
     *   'horario'     => string (YYYY-MM-DD HH:mm) para agendar/cancelar/alterar,
     *   'duracao_minutos' => int (opcional),
     *   'disponibilidade_id' => int (quando aplicável)
     * ]
     * @return array
     */
    public function executarAcao(string $acao, array $dados)
{
    try {
        $agendaId = $dados['agenda_id'] ?? null;
        if (!$agendaId) {
            return ['success' => false, 'message' => 'Agenda não informada.'];
        }

        [$dataInicio, $dataFim] = $this->resolverIntervaloDatas($dados);

        $chatId = $dados['chat_id'] ?? null;
        $nome = $dados['nome'] ?? '';
        $telefone = $dados['telefone'] ?? '';
        $horario = $this->parseHorario($dados['horario'] ?? null);
        $horarioAntigo = $this->parseHorario($dados['horario_antigo'] ?? null);
        $duracaoMinutos = isset($dados['duracao_minutos']) ? (int)$dados['duracao_minutos'] : null;

        switch (strtolower($acao)) {
            case 'consultar':
                $result = $this->getDisponiveisPorPeriodo(
                    $agendaId,
                    $dataInicio->toDateString(),
                    $dataFim->toDateString()
                );

                return [
                    'success' => true,
                    'count' => $result->count(),
                    'data' => $result
                ];

            case 'agendar':
                return $this->agendarPorHorario(
                    $agendaId,
                    $horario,
                    $duracaoMinutos,
                    $nome,
                    $telefone,
                    $chatId,
                    $dados['disponibilidade_id'] ?? null
                );

            case 'cancelar':
                return $this->cancelarPorHorarioOuId(
                    $agendaId,
                    $horarioAntigo ?? $horario,
                    $chatId,
                    $dados['disponibilidade_id'] ?? null
                );

            case 'alterar':
                return $this->alterarHorario(
                    $agendaId,
                    $horarioAntigo ?? $horario,
                    $horario,
                    $duracaoMinutos,
                    $nome,
                    $telefone,
                    $chatId,
                    $dados['disponibilidade_id'] ?? null,
                    $dados['nova_disponibilidade_id'] ?? null
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

    private function resolverIntervaloDatas(array $dados): array
    {
        if (!empty($dados['data_inicio']) || !empty($dados['data_fim'])) {
            $inicio = Carbon::parse($dados['data_inicio'] ?? now())->startOfDay();
            $fim = Carbon::parse($dados['data_fim'] ?? $inicio)->endOfDay();

            if ($inicio->gt($fim)) {
                [$inicio, $fim] = [$fim, $inicio];
            }

            // limita a janelas curtas para evitar consultas muito grandes
            if ($inicio->diffInDays($fim) > 31) {
                $fim = $inicio->copy()->addDays(31)->endOfDay();
            }
        } else {
            $mes = $dados['mes'] ?? now()->month;
            $ano = now()->year;

            $inicio = Carbon::create($ano, $mes, 1)->startOfMonth();
            $fim = $inicio->copy()->endOfMonth();
        }

        return [$inicio, $fim];
    }

    private function parseHorario(?string $horario): ?Carbon
    {
        if (empty($horario)) {
            return null;
        }

        try {
            return Carbon::parse($horario);
        } catch (\Throwable $e) {
            return null;
        }
    }

    private function calcularDuracaoSlot(?Disponibilidade $slot): ?int
    {
        if (!$slot) {
            return null;
        }

        try {
            $inicio = Carbon::parse($slot->inicio);
            $fim = Carbon::parse($slot->fim);
            $minutos = $inicio->diffInMinutes($fim);
            return $minutos > 0 ? $minutos : null;
        } catch (\Throwable $e) {
            return null;
        }
    }

    private function encontrarSlotsConsecutivos(int $agendaId, Carbon $inicio, int $duracaoMinutos): array
    {
        // primeiro bloco
        $primeiro = Disponibilidade::where('agenda_id', $agendaId)
            ->where('data', $inicio->toDateString())
            ->where('inicio', $inicio->format('H:i'))
            ->where('ocupado', false)
            ->orderBy('id')
            ->first();

        if (!$primeiro) {
            return ['success' => false, 'message' => 'Horário indisponível.'];
        }

        $slotDuracao = $this->calcularDuracaoSlot($primeiro);
        if (!$slotDuracao) {
            return ['success' => false, 'message' => 'Não foi possível identificar a duração do slot.'];
        }

        $blocosNecessarios = max(1, (int) ceil($duracaoMinutos / $slotDuracao));
        $slotsSelecionados = [$primeiro];
        $proximoInicio = $inicio->copy()->addMinutes($slotDuracao);

        // busca blocos seguintes contíguos
        for ($i = 1; $i < $blocosNecessarios; $i++) {
            $slot = Disponibilidade::where('agenda_id', $agendaId)
                ->where('data', $proximoInicio->toDateString())
                ->where('inicio', $proximoInicio->format('H:i'))
                ->where('ocupado', false)
                ->orderBy('id')
                ->first();

            if (!$slot) {
                return [
                    'success' => false,
                    'message' => "Não há blocos suficientes para {$duracaoMinutos} minutos a partir de " . $inicio->format('d/m H:i')
                ];
            }

            $slotsSelecionados[] = $slot;
            $proximoInicio->addMinutes($slotDuracao);
        }

        return [
            'success' => true,
            'slots' => $slotsSelecionados,
            'slot_duracao' => $slotDuracao
        ];
    }

    private function agendarPorHorario(
        int $agendaId,
        ?Carbon $horario,
        ?int $duracaoMinutos,
        string $nome,
        string $telefone,
        ?int $chatId = null,
        ?int $disponibilidadeId = null
    ): array {
        if (!$horario && !$disponibilidadeId) {
            return ['success' => false, 'message' => 'Informe o horário para agendar.'];
        }

        if (empty($nome) || empty($telefone)) {
            return ['success' => false, 'message' => 'Nome e telefone são obrigatórios para agendar.'];
        }

        // Fallback legado: usar ID direto se horário não veio
        if (!$horario && $disponibilidadeId) {
            return $this->preencherHorario($disponibilidadeId, $nome, $telefone, $chatId);
        }

        // Se a duração não foi informada, usar a duração do slot base
        $duracaoMinutos = ($duracaoMinutos && $duracaoMinutos > 0) ? $duracaoMinutos : null;

        // Busca sequência de slots
        $encontro = $this->encontrarSlotsConsecutivos(
            $agendaId,
            $horario,
            $duracaoMinutos ?? 1
        );

        if (!$encontro['success']) {
            return $encontro;
        }

        $slotDuracao = $encontro['slot_duracao'];
        if (!$duracaoMinutos) {
            $duracaoMinutos = $slotDuracao;
        }

        $encontro = $this->encontrarSlotsConsecutivos(
            $agendaId,
            $horario,
            $duracaoMinutos
        );

        if (!$encontro['success']) {
            return $encontro;
        }

        $slotsSelecionados = $encontro['slots'];

        DB::transaction(function () use ($slotsSelecionados, $nome, $telefone, $chatId) {
            foreach ($slotsSelecionados as $slot) {
                $slot->update([
                    'nome' => $nome,
                    'telefone' => $telefone,
                    'chat_id' => $chatId,
                    'ocupado' => true,
                ]);
            }
        });

        $ultimoSlot = end($slotsSelecionados);

        return [
            'success' => true,
            'data' => [
                'data' => $horario->toDateString(),
                'inicio' => $horario->format('H:i'),
                'fim' => Carbon::parse($ultimoSlot->fim)->format('H:i'),
                'duracao_minutos' => $duracaoMinutos,
                'slot_ids' => collect($slotsSelecionados)->pluck('id')->all(),
            ]
        ];
    }

    private function cancelarPorHorarioOuId(
        int $agendaId,
        ?Carbon $horario,
        ?int $chatId = null,
        ?int $disponibilidadeId = null
    ): array {
        if ($disponibilidadeId) {
            return $this->cancelarHorario($disponibilidadeId, $chatId);
        }

        if (!$horario) {
            return ['success' => false, 'message' => 'Informe o horário a cancelar.'];
        }

        if (!$chatId) {
            return ['success' => false, 'message' => 'Chat não identificado para cancelar.'];
        }

        $primeiro = Disponibilidade::where('agenda_id', $agendaId)
            ->where('data', $horario->toDateString())
            ->where('inicio', $horario->format('H:i'))
            ->where('chat_id', $chatId)
            ->first();

        if (!$primeiro) {
            return ['success' => false, 'message' => 'Nenhum agendamento encontrado para este horário.'];
        }

        $slotDuracao = $this->calcularDuracaoSlot($primeiro);
        if (!$slotDuracao) {
            return ['success' => false, 'message' => 'Não foi possível identificar a duração do slot.'];
        }

        $todosDoChat = Disponibilidade::where('agenda_id', $agendaId)
            ->where('data', $horario->toDateString())
            ->where('chat_id', $chatId)
            ->orderBy('inicio')
            ->get();

        $idsParaLiberar = [];
        $esperado = $horario->copy();

        foreach ($todosDoChat as $slot) {
            if ($slot->inicio === $esperado->format('H:i')) {
                $idsParaLiberar[] = $slot->id;
                $esperado->addMinutes($slotDuracao);
            } elseif ($slot->inicio > $esperado->format('H:i')) {
                // rompe ao encontrar lacuna
                break;
            }
        }

        if (empty($idsParaLiberar)) {
            return ['success' => false, 'message' => 'Nenhum agendamento encontrado para este horário.'];
        }

        DB::transaction(function () use ($idsParaLiberar) {
            Disponibilidade::whereIn('id', $idsParaLiberar)->update([
                'ocupado' => false,
                'nome' => null,
                'telefone' => null,
                'chat_id' => null,
            ]);
        });

        return [
            'success' => true,
            'data' => [
                'cancelados' => $idsParaLiberar,
                'data' => $horario->toDateString(),
                'inicio' => $horario->format('H:i'),
            ]
        ];
    }

    private function alterarHorario(
        int $agendaId,
        ?Carbon $horarioAntigo,
        ?Carbon $horarioNovo,
        ?int $duracaoMinutos,
        string $nome,
        string $telefone,
        ?int $chatId = null,
        ?int $disponibilidadeAntiga = null,
        ?int $disponibilidadeNova = null
    ): array {
        if (!$horarioNovo && !$disponibilidadeNova) {
            return ['success' => false, 'message' => 'Informe o novo horário para alterar.'];
        }

        // Primeiro tenta reservar o novo horário
        $novo = $this->agendarPorHorario(
            $agendaId,
            $horarioNovo,
            $duracaoMinutos,
            $nome,
            $telefone,
            $chatId,
            $disponibilidadeNova
        );

        if (!$novo['success']) {
            return $novo;
        }

        // Depois cancela o antigo (se existir)
        if ($horarioAntigo || $disponibilidadeAntiga) {
            $cancel = $this->cancelarPorHorarioOuId(
                $agendaId,
                $horarioAntigo,
                $chatId,
                $disponibilidadeAntiga
            );

            if (!$cancel['success']) {
                // Reverte a nova reserva para evitar duplicidade
                if (!empty($novo['data']['slot_ids'])) {
                    $this->liberarSlots($novo['data']['slot_ids']);
                }

                return $cancel;
            }
        }

        return $novo;
    }

    private function liberarSlots(array $slotIds): void
    {
        if (empty($slotIds)) {
            return;
        }

        Disponibilidade::whereIn('id', $slotIds)->update([
            'ocupado' => false,
            'nome' => null,
            'telefone' => null,
            'chat_id' => null,
        ]);
    }

}
