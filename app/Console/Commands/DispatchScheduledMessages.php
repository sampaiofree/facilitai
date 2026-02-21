<?php

namespace App\Console\Commands;

use App\Jobs\ExecuteScheduledMessageJob;
use App\Models\ScheduledMessage;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;

class DispatchScheduledMessages extends Command
{
    protected $signature = 'scheduled-messages:dispatch {--chunk=100}';
    protected $description = 'Enfileira mensagens agendadas pendentes no horario correto.';

    public function handle(): int
    {
        $chunk = max(1, (int) $this->option('chunk'));
        $lock = Cache::lock('scheduled_messages:dispatch', 55);

        if (!$lock->get()) {
            $this->info('Dispatcher de agendamentos ja esta em execucao.');
            return Command::SUCCESS;
        }

        $totalQueued = 0;

        try {
            while (true) {
                $nowUtc = Carbon::now('UTC');
                $ids = ScheduledMessage::query()
                    ->where('status', 'pending')
                    ->where('scheduled_for', '<=', $nowUtc)
                    ->orderBy('scheduled_for')
                    ->limit($chunk)
                    ->pluck('id');

                if ($ids->isEmpty()) {
                    break;
                }

                foreach ($ids as $id) {
                    $updated = ScheduledMessage::query()
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

                    ExecuteScheduledMessageJob::dispatch((int) $id)
                        ->onQueue('processarconversa');
                    $totalQueued++;
                }
            }
        } finally {
            optional($lock)->release();
        }

        $this->info("Agendamentos enfileirados: {$totalQueued}");

        return Command::SUCCESS;
    }
}

