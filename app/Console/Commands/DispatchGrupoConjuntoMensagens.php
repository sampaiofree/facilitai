<?php

namespace App\Console\Commands;

use App\Jobs\ExecuteGrupoConjuntoMensagemJob;
use App\Models\GrupoConjuntoMensagem;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;

class DispatchGrupoConjuntoMensagens extends Command
{
    protected $signature = 'grupo-conjunto-mensagens:dispatch {--chunk=100}';
    protected $description = 'Enfileira mensagens de grupos pendentes no horario correto.';

    public function handle(): int
    {
        $chunk = max(1, (int) $this->option('chunk'));
        $lock = Cache::lock('grupo_conjunto_mensagens:dispatch', 55);

        if (!$lock->get()) {
            $this->info('Dispatcher de mensagens de grupos ja esta em execucao.');
            return Command::SUCCESS;
        }

        $totalQueued = 0;

        try {
            while (true) {
                $nowUtc = Carbon::now('UTC');
                $ids = GrupoConjuntoMensagem::query()
                    ->where('status', 'pending')
                    ->whereNotNull('scheduled_for')
                    ->where('scheduled_for', '<=', $nowUtc)
                    ->orderBy('scheduled_for')
                    ->limit($chunk)
                    ->pluck('id');

                if ($ids->isEmpty()) {
                    break;
                }

                foreach ($ids as $id) {
                    $updated = GrupoConjuntoMensagem::query()
                        ->whereKey($id)
                        ->where('status', 'pending')
                        ->update([
                            'status' => 'queued',
                            'queued_at' => $nowUtc,
                            'updated_at' => $nowUtc,
                        ]);

                    if ($updated !== 1) {
                        continue;
                    }

                    ExecuteGrupoConjuntoMensagemJob::dispatch((int) $id)
                        ->onQueue('processarconversa');
                    $totalQueued++;
                }
            }
        } finally {
            optional($lock)->release();
        }

        $this->info("Mensagens de grupos enfileiradas: {$totalQueued}");

        return Command::SUCCESS;
    }
}
