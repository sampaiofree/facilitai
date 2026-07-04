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

    public function __construct(
        private readonly int $grupoConjuntoMensagemId,
        private readonly int $recipientIndex = 0
    ) {
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

        $result = $service->dispatchRecipientAndPersist(
            $mensagem,
            max(0, $this->recipientIndex),
            $this->attempts()
        );

        if (!empty($result['deferred'])) {
            self::dispatch($this->grupoConjuntoMensagemId, max(0, $this->recipientIndex))
                ->delay(now()->addSeconds((int) ($result['delay_seconds'] ?? 1)))
                ->onQueue('processarconversa');

            return;
        }

        if (!empty($result['completed'])) {
            return;
        }

        self::dispatch($this->grupoConjuntoMensagemId, max(0, $this->recipientIndex) + 1)
            ->delay(now()->addSeconds(random_int(5, 30)))
            ->onQueue('processarconversa');
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
