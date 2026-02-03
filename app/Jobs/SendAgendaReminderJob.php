<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SendAgendaReminderJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private const DEFAULT_TEMPLATE = '[notificacao do sistema: envie uma mensagem lembrando o cliente do horario marcado em {data} as {hora}]';

    public function __construct(private readonly int $reminderId)
    {
    }

    public function handle(): void
    {
        Log::warning('SendAgendaReminderJob disabled because Chat functionality was removed.');
    }
}
