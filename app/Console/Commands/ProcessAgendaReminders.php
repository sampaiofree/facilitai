<?php

namespace App\Console\Commands;

use App\Jobs\SendAgendaReminderJob;
use App\Models\AgendaReminder;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class ProcessAgendaReminders extends Command
{
    protected $signature = 'agenda:process-reminders {--chunk=100}';
    protected $description = 'Dispara lembretes de agenda pendentes';

    public function handle(): int
    {
        $chunk = (int) $this->option('chunk') ?: 100;
        $agoraLocal = Carbon::now('America/Sao_Paulo');
        $totalProcessados = 0;

        while (true) {
            $ids = AgendaReminder::query()
                ->where('status', 'pendente')
                ->where('disparo_em', '<=', $agoraLocal)
                ->orderBy('disparo_em')
                ->limit($chunk)
                ->pluck('id');

            if ($ids->isEmpty()) {
                break;
            }

            AgendaReminder::whereIn('id', $ids)
                ->where('status', 'pendente')
                ->update(['status' => 'processing']);

            $reminders = AgendaReminder::whereIn('id', $ids)->get();

            foreach ($reminders as $reminder) {
                dispatch(new SendAgendaReminderJob($reminder->id));
                $totalProcessados++;
            }
        }

        $this->info("Lembretes enfileirados: {$totalProcessados}");
        return Command::SUCCESS;
    }
}
