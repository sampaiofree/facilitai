<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use App\Models\SequenceChat;
use App\Models\SequenceLog;
use App\Models\SequenceStep;
use App\Services\ConversationsService;
use Illuminate\Support\Carbon;

class ProcessSequences extends Command
{
    protected $signature = 'sequences:process';
    protected $description = 'Processa inscrições de sequências e remove chats que não atendem critérios de tags';

    public function handle(): int
    {
        $intervaloMinutos = 2;
        $ultimoEnvioLocal = null;

        SequenceChat::with(['sequence', 'chat.tags', 'chat.instance'])
            ->where('status', 'em_andamento')
            ->where(function ($q) {
                $agoraLocal = Carbon::now('America/Sao_Paulo');
                $q->whereNull('proximo_envio_em')
                  ->orWhere('proximo_envio_em', '<=', $agoraLocal);
            })
            ->chunk(100, function ($batch) use (&$ultimoEnvioLocal, $intervaloMinutos) {
                foreach ($batch as $inscricao) {
                    $seq = $inscricao->sequence;
                    if (!$seq) {
                        
                        $inscricao->delete();
                        continue;
                    }

                    $tagsChat = $inscricao->chat?->tags?->pluck('name')->map(fn ($t) => mb_strtolower($t))->unique() ?? collect();
                    $incluir = collect($seq->tags_incluir ?? [])->map(fn ($t) => mb_strtolower($t))->filter();
                    $excluir = collect($seq->tags_excluir ?? [])->map(fn ($t) => mb_strtolower($t))->filter();

                    if (!$this->atendeTags($tagsChat, $incluir, $excluir)) {
                        
                        $this->cancelarPorTags($inscricao, $incluir, $excluir, $tagsChat);
                        continue;
                    }

                    $step = $this->obterPassoAtual($inscricao);
                    if (!$step) {
                        
                        $inscricao->status = 'concluida';
                        $inscricao->save();
                        continue;
                    }

                    $agoraLocal = Carbon::now('America/Sao_Paulo');
                    if ($inscricao->proximo_envio_em && $inscricao->proximo_envio_em->gt($agoraLocal)) {
                        
                        continue;
                    }

                    // Espacamento minimo entre disparos consecutivos para evitar spam
                    $proximoPermitido = $ultimoEnvioLocal?->copy()->addMinutes($intervaloMinutos);
                    if ($proximoPermitido && $agoraLocal->lt($proximoPermitido)) {
                        
                        $inscricao->proximo_envio_em = $proximoPermitido->clone()->setTimezone('UTC');
                        $inscricao->save();
                        continue;
                    }

                    if (!$this->prontoParaDisparar($inscricao, $step, $agoraLocal)) {
                        // reagendado para próxima janela válida
                        
                        continue;
                    }

                    if (!$inscricao->chat || !$inscricao->chat->bot_enabled) {
                        $this->cancelarInscricao($inscricao, $step, 'Bot desativado ou chat ausente.');
                        continue;
                    }

                    // Disparo
                    try {
                        $mensagem = $step->prompt;
                        $telefone = $inscricao->chat->contact;
                        $instancia = $inscricao->chat->instance_id;

                        $service = new ConversationsService($mensagem, $telefone, $instancia);
                        
                        $result = $service->enviarMSG();
                        if ($result === false) {
                            
                            continue;
                        }

                        $this->log($inscricao, $step, 'sucesso', 'Passo enviado.');
                        $momentoEnvio = Carbon::now('America/Sao_Paulo');
                        $ultimoEnvioLocal = $momentoEnvio;
                        $this->avancarPasso($inscricao, $step, $momentoEnvio);
                    } catch (\Throwable $e) {
                        Log::error('Erro ao disparar sequência', [
                            'inscricao_id' => $inscricao->id,
                            'step_id' => $step->id,
                            'error' => $e->getMessage(),
                        ]);
                        $this->log($inscricao, $step, 'erro', 'Erro ao enviar: ' . $e->getMessage());
                        $momentoErro = Carbon::now('America/Sao_Paulo');
                        $this->avancarPasso($inscricao, $step, $momentoErro, true);
                    }
                }
            });

        $this->info('Processamento concluído.');
        return Command::SUCCESS;
    }

    private function atendeTags($tagsChat, $incluir, $excluir): bool
    {
        if ($incluir->isNotEmpty() && $incluir->diff($tagsChat)->isNotEmpty()) {
            return false;
        }

        if ($excluir->isNotEmpty() && $tagsChat->intersect($excluir)->isNotEmpty()) {
            return false;
        }

        return true;
    }

    private function cancelarPorTags(SequenceChat $inscricao, $incluir, $excluir, $tagsChat): void
    {
        $inscricao->status = 'cancelada';
        $inscricao->save();
        $this->log($inscricao, null, 'pulado', 'Cancelado por tags. Faltou: ' . $incluir->diff($tagsChat)->implode(', ') . '; Bloqueio: ' . $tagsChat->intersect($excluir)->implode(', '));
        
    }

    private function obterPassoAtual(SequenceChat $inscricao): ?SequenceStep
    {
        if ($inscricao->passo_atual_id) {
            return SequenceStep::find($inscricao->passo_atual_id);
        }

        $step = $inscricao->sequence?->steps()->where('active', true)->orderBy('ordem')->first();
        if ($step) {
            $inscricao->passo_atual_id = $step->id;
            $inicioLocal = $inscricao->iniciado_em ?? now('America/Sao_Paulo');
            $inscricao->iniciado_em = $inicioLocal;
            $proximoEnvio = $this->aplicarAtraso($step, $inicioLocal);
            $inscricao->proximo_envio_em = $proximoEnvio;
            $inscricao->save();
        }

        return $step;
    }

    private function prontoParaDisparar(SequenceChat $inscricao, SequenceStep $step, Carbon $now): bool
    {
        // horário/dia
        if ($step->janela_inicio || $step->janela_fim || $step->dias_semana) {
            if (!$this->estaNaJanela($step, $now)) {
                $inscricao->proximo_envio_em = $this->proximaJanela($step, $now);
                $inscricao->save();
                return false;
            }
        }

        return true;
    }

    private function estaNaJanela(SequenceStep $step, Carbon $ref): bool
    {
        $dow = strtolower($ref->locale('en')->isoFormat('ddd'));
        $dias = collect($step->dias_semana ?? [])->map(fn ($d) => strtolower($d));
        if ($dias->isNotEmpty() && !$dias->contains($dow)) {
            return false;
        }

        if ($step->janela_inicio && $step->janela_fim) {
            $hora = $ref->format('H:i');
            return $hora >= $step->janela_inicio && $hora <= $step->janela_fim;
        }

        return true;
    }

    private function proximaJanela(SequenceStep $step, Carbon $ref): Carbon
    {
        $dias = collect($step->dias_semana ?? [])->map(fn ($d) => strtolower($d));
        $start = $step->janela_inicio ?: '08:00';

        for ($i = 0; $i < 8; $i++) {
            $candidate = $ref->copy()->addDays($i);
            $dow = strtolower($candidate->locale('en')->isoFormat('ddd'));
            if ($dias->isNotEmpty() && !$dias->contains($dow)) {
                continue;
            }
            $candidate->setTimeFromTimeString($start);
            if ($candidate->gt($ref)) {
                return $candidate;
            }
        }

        return $ref->copy()->addDay()->setTimeFromTimeString($start);
    }

    private function avancarPasso(SequenceChat $inscricao, SequenceStep $atual, Carbon $now, bool $foiErro = false): void
    {
        $proximo = $inscricao->sequence?->steps()
            ->where('active', true)
            ->where('ordem', '>', $atual->ordem)
            ->orderBy('ordem')
            ->first();

        if (!$proximo) {
            $inscricao->status = 'concluida';
            $inscricao->proximo_envio_em = null;
            $inscricao->passo_atual_id = null;
            $inscricao->save();
            return;
        }

        $base = $now->copy();
        $proximoEnvio = $this->aplicarAtraso($proximo, $base);
        $inscricao->passo_atual_id = $proximo->id;
        $inscricao->proximo_envio_em = $proximoEnvio;
        $inscricao->save();
    }

    private function aplicarAtraso(SequenceStep $step, Carbon $base): Carbon
    {
        return match ($step->atraso_tipo) {
            'minuto' => $base->copy()->addMinutes($step->atraso_valor ?? 0),
            'dia' => $base->copy()->addDays($step->atraso_valor ?? 0),
            default => $base->copy()->addHours($step->atraso_valor ?? 0),
        };
    }

    private function log(SequenceChat $inscricao, ?SequenceStep $step, string $status, string $message): void
    {
        SequenceLog::create([
            'sequence_chat_id' => $inscricao->id,
            'sequence_step_id' => $step?->id,
            'status' => $status,
            'message' => $message,
        ]);
    }

    private function cancelarInscricao(SequenceChat $inscricao, ?SequenceStep $step, string $motivo): void
    {
        $inscricao->status = 'cancelada';
        $inscricao->proximo_envio_em = null;
        $inscricao->passo_atual_id = null;
        $inscricao->save();
        $this->log($inscricao, $step, 'pulado', $motivo);
    }
}
