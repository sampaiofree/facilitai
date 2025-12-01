<?php

namespace App\Jobs;

use App\Models\AgendaReminder;
use App\Services\ConversationsService;
use Carbon\Carbon;
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
        $reminder = AgendaReminder::find($this->reminderId);
        if (!$reminder) {
            return;
        }

        // Se ja saiu do fluxo esperado, nao reprocessa
        if (!in_array($reminder->status, ['processing', 'pendente'], true)) {
            return;
        }

        $reminder->status = 'processing';
        $reminder->tentativas = ($reminder->tentativas ?? 0) + 1;
        $reminder->save();

        try {
            $agendado = Carbon::parse($reminder->agendado_em, 'America/Sao_Paulo');
            $dataFormatada = $agendado->format('d/m/Y');
            $horaFormatada = $agendado->format('H:i');

            $template = $reminder->mensagem_template ?: self::DEFAULT_TEMPLATE;
            $mensagem = str_replace(
                ['{data}', '{hora}'],
                [$dataFormatada, $horaFormatada],
                $template
            );

            $service = new ConversationsService($mensagem, $reminder->telefone, $reminder->instance_id);
            $service->enviarMSG();

            $reminder->status = 'enviado';
            $reminder->sent_at = Carbon::now('America/Sao_Paulo');
            $reminder->last_error = null;
            $reminder->save();
        } catch (\Throwable $e) {
            $reminder->status = 'falhou';
            $reminder->last_error = $e->getMessage();
            $reminder->save();

            Log::error('Erro ao enviar lembrete de agenda', [
                'reminder_id' => $reminder->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
