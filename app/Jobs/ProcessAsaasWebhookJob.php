<?php

namespace App\Jobs;

use App\Models\AsaasWebhook;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ProcessAsaasWebhookJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $timeout = 60;

    public function __construct(private array $webhookData)
    {
    }

    public function handle(): void
    {
        $eventId = (string) ($this->webhookData['id'] ?? '');
        $eventType = (string) ($this->webhookData['event'] ?? '');
        $webhookCreatedAt = $this->webhookData['dateCreated'] ?? now()->toDateTimeString();
        $paymentData = is_array($this->webhookData['payment'] ?? null) ? $this->webhookData['payment'] : [];

        if ($eventId === '' || $eventType === '') {
            Log::channel('asaas')->warning('ProcessAsaasWebhookJob ignorado por campos obrigatórios ausentes.', [
                'payload' => $this->webhookData,
            ]);
            return;
        }

        $attributes = [
            'event_type' => $eventType,
            'webhook_created_at' => $webhookCreatedAt,
            'payment_id' => $paymentData['id'] ?? null,
            'payment_created_at' => $paymentData['dateCreated'] ?? null,
            'customer_id' => $paymentData['customer'] ?? null,
            'value' => $paymentData['value'] ?? null,
            'description' => $paymentData['description'] ?? null,
            'billing_type' => $paymentData['billingType'] ?? null,
            'confirmed_at' => $paymentData['confirmedDate'] ?? null,
            'status' => $paymentData['status'] ?? null,
            'payment_at' => $paymentData['paymentDate'] ?? null,
            'client_payment_at' => $paymentData['clientPaymentDate'] ?? null,
            'invoice_url' => $paymentData['invoiceUrl'] ?? null,
            'external_reference' => $paymentData['externalReference'] ?? null,
            'transaction_receipt_url' => $paymentData['transactionReceiptUrl'] ?? null,
            'nosso_numero' => $paymentData['nossoNumero'] ?? null,
            'payload' => $this->webhookData,
        ];

        $record = AsaasWebhook::firstOrCreate(
            ['webhook_id' => $eventId],
            $attributes
        );

        if (!$record->wasRecentlyCreated) {
            Log::channel('asaas')->info('Evento webhook Asaas duplicado ignorado.', [
                'webhook_id' => $eventId,
                'event_type' => $eventType,
                'payment_id' => $paymentData['id'] ?? null,
            ]);
            return;
        }

        Log::channel('asaas')->info('Evento webhook Asaas processado com sucesso.', [
            'webhook_id' => $eventId,
            'event_type' => $eventType,
            'payment_id' => $paymentData['id'] ?? null,
        ]);
    }
}
