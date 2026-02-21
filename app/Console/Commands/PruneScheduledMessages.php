<?php

namespace App\Console\Commands;

use App\Models\ScheduledMessage;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

class PruneScheduledMessages extends Command
{
    protected $signature = 'scheduled-messages:prune {--days=180}';
    protected $description = 'Remove historico antigo de mensagens agendadas finalizadas/canceladas.';

    public function handle(): int
    {
        $days = max(1, (int) $this->option('days'));
        $cutoff = Carbon::now('UTC')->subDays($days);
        $totalDeleted = 0;

        while (true) {
            $ids = ScheduledMessage::query()
                ->where(function ($query) use ($cutoff) {
                    $query->where(function ($q) use ($cutoff) {
                        $q->where('status', 'sent')
                            ->where(function ($d) use ($cutoff) {
                                $d->where('sent_at', '<=', $cutoff)
                                    ->orWhere(function ($e) use ($cutoff) {
                                        $e->whereNull('sent_at')
                                            ->where('updated_at', '<=', $cutoff);
                                    });
                            });
                    })->orWhere(function ($q) use ($cutoff) {
                        $q->where('status', 'failed')
                            ->where(function ($d) use ($cutoff) {
                                $d->where('failed_at', '<=', $cutoff)
                                    ->orWhere(function ($e) use ($cutoff) {
                                        $e->whereNull('failed_at')
                                            ->where('updated_at', '<=', $cutoff);
                                    });
                            });
                    })->orWhere(function ($q) use ($cutoff) {
                        $q->where('status', 'canceled')
                            ->where(function ($d) use ($cutoff) {
                                $d->where('canceled_at', '<=', $cutoff)
                                    ->orWhere(function ($e) use ($cutoff) {
                                        $e->whereNull('canceled_at')
                                            ->where('updated_at', '<=', $cutoff);
                                    });
                            });
                    });
                })
                ->orderBy('id')
                ->limit(500)
                ->pluck('id');

            if ($ids->isEmpty()) {
                break;
            }

            $deleted = ScheduledMessage::whereIn('id', $ids)->delete();
            $totalDeleted += $deleted;
        }

        $this->info("Agendamentos removidos: {$totalDeleted}");

        return Command::SUCCESS;
    }
}

