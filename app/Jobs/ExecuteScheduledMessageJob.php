<?php

namespace App\Jobs;

use App\Models\ScheduledMessage;
use App\Services\ScheduledMessageService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class ExecuteScheduledMessageJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 4;
    public int $timeout = 360;

    public function __construct(private readonly int $scheduledMessageId)
    {
    }

    public function backoff(): array
    {
        return [120, 600, 1800];
    }

    public function handle(ScheduledMessageService $scheduledMessageService): void
    {
        $scheduledMessage = ScheduledMessage::with('clienteLead.cliente')
            ->find($this->scheduledMessageId);

        if (!$scheduledMessage) {
            return;
        }

        if (in_array($scheduledMessage->status, ['sent', 'canceled'], true)) {
            return;
        }

        $scheduledMessage->attempts = max($scheduledMessage->attempts, $this->attempts());
        $scheduledMessage->save();

        $context = $scheduledMessageService->resolveDispatchContext(
            $scheduledMessage->clienteLead,
            (int) $scheduledMessage->assistant_id,
            (int) $scheduledMessage->created_by_user_id,
            $scheduledMessage->conexao_id ? (int) $scheduledMessage->conexao_id : null
        );

        if (!$context['ok']) {
            $this->markAsFailedWithoutRetry($scheduledMessage, (string) ($context['message'] ?? 'Falha de validacao de regra.'));
            return;
        }

        /** @var \App\Models\Conexao $conexao */
        $conexao = $context['conexao'];
        /** @var \App\Models\ClienteLead $lead */
        $lead = $context['lead'];
        /** @var string $phone */
        $phone = $context['phone'];

        $payload = [
            'phone' => $phone,
            'text' => $scheduledMessage->mensagem,
            'tipo' => 'text',
            'from_me' => false,
            'is_group' => false,
            'lead_name' => $lead->name ?? $phone,
            'openai_role' => 'system',
            'event_id' => $scheduledMessage->event_id,
            'message_timestamp' => Carbon::now('UTC')->valueOf(),
            'message_type' => 'conversation',
            'bypass_debounce' => true,
        ];

        ProcessIncomingMessageJob::dispatchSync($conexao->id, $lead->id, $payload);

        $scheduledMessage->update([
            'status' => 'sent',
            'sent_at' => Carbon::now('UTC'),
            'failed_at' => null,
            'error_message' => null,
            'attempts' => max($scheduledMessage->attempts, $this->attempts()),
        ]);
    }

    public function failed(\Throwable $exception): void
    {
        $scheduledMessage = ScheduledMessage::find($this->scheduledMessageId);
        if (!$scheduledMessage) {
            return;
        }

        if (in_array($scheduledMessage->status, ['sent', 'canceled'], true)) {
            return;
        }

        $scheduledMessage->update([
            'status' => 'failed',
            'failed_at' => Carbon::now('UTC'),
            'error_message' => Str::limit($exception->getMessage(), 1900),
            'attempts' => max($scheduledMessage->attempts, $this->attempts()),
        ]);

        Log::channel('process_job')->error('ExecuteScheduledMessageJob failed.', [
            'scheduled_message_id' => $this->scheduledMessageId,
            'error' => $exception->getMessage(),
            'attempt' => $this->attempts(),
        ]);
    }

    private function markAsFailedWithoutRetry(ScheduledMessage $scheduledMessage, string $message): void
    {
        $scheduledMessage->update([
            'status' => 'failed',
            'failed_at' => Carbon::now('UTC'),
            'error_message' => Str::limit($message, 1900),
            'attempts' => max($scheduledMessage->attempts, $this->attempts()),
        ]);
    }
}

