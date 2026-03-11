<?php

namespace App\Jobs;

use App\Models\GrupoConjuntoMensagem;
use App\Services\GrupoConjuntoMensagemService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class ExecuteGrupoConjuntoMensagemJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 4;
    public int $timeout = 360;

    public function __construct(private readonly int $grupoConjuntoMensagemId)
    {
    }

    public function backoff(): array
    {
        return [120, 600, 1800];
    }

    public function handle(GrupoConjuntoMensagemService $service): void
    {
        $mensagem = GrupoConjuntoMensagem::find($this->grupoConjuntoMensagemId);

        if (!$mensagem) {
            return;
        }

        if (in_array((string) $mensagem->status, ['sent', 'canceled'], true)) {
            return;
        }

        $mensagem->attempts = max((int) $mensagem->attempts, $this->attempts());
        $mensagem->save();

        $service->dispatchAndPersist($mensagem, $this->attempts());
    }

    public function failed(\Throwable $exception): void
    {
        $mensagem = GrupoConjuntoMensagem::find($this->grupoConjuntoMensagemId);
        if (!$mensagem) {
            return;
        }

        if (in_array((string) $mensagem->status, ['sent', 'canceled'], true)) {
            return;
        }

        $mensagem->update([
            'status' => 'failed',
            'failed_at' => Carbon::now('UTC'),
            'error_message' => Str::limit($exception->getMessage(), 1900),
            'attempts' => max((int) $mensagem->attempts, $this->attempts()),
        ]);

        Log::channel('process_job')->error('ExecuteGrupoConjuntoMensagemJob failed.', [
            'grupo_conjunto_mensagem_id' => $this->grupoConjuntoMensagemId,
            'error' => $exception->getMessage(),
            'attempt' => $this->attempts(),
        ]);
    }
}
