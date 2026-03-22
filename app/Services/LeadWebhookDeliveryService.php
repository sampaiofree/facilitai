<?php

namespace App\Services;

use App\Models\LeadWebhookDelivery;
use App\Models\LeadWebhookLink;
use Illuminate\Support\Carbon;

class LeadWebhookDeliveryService
{
    public function __construct(
        protected LeadWebhookPayloadMapper $payloadMapper
    ) {
    }

    public function start(LeadWebhookLink $link, array $payload): LeadWebhookDelivery
    {
        return LeadWebhookDelivery::create([
            'lead_webhook_link_id' => $link->id,
            'status' => 'failed',
            'payload' => $payload,
            'payload_hash' => hash('sha256', $this->payloadMapper->canonicalJson($payload)),
        ]);
    }

    public function findRecentDuplicate(LeadWebhookDelivery $delivery, int $seconds = 60): ?LeadWebhookDelivery
    {
        return LeadWebhookDelivery::query()
            ->where('lead_webhook_link_id', $delivery->lead_webhook_link_id)
            ->where('payload_hash', $delivery->payload_hash)
            ->where('id', '!=', $delivery->id)
            ->where('created_at', '>=', Carbon::now()->subSeconds($seconds))
            ->latest('id')
            ->first();
    }

    public function markDuplicate(LeadWebhookDelivery $delivery, LeadWebhookDelivery $duplicateOf): LeadWebhookDelivery
    {
        $delivery->update([
            'status' => 'duplicate',
            'result' => [
                'duplicate_of_delivery_id' => $duplicateOf->id,
            ],
            'error_message' => null,
            'processed_at' => Carbon::now(),
        ]);

        return $delivery->fresh();
    }

    public function finalize(
        LeadWebhookDelivery $delivery,
        string $status,
        array $result = [],
        ?string $errorMessage = null,
        ?int $leadId = null,
        ?string $resolvedPhone = null
    ): LeadWebhookDelivery {
        $delivery->update([
            'status' => $status,
            'result' => $result === [] ? null : $result,
            'error_message' => $errorMessage,
            'cliente_lead_id' => $leadId,
            'resolved_phone' => $resolvedPhone,
            'processed_at' => Carbon::now(),
        ]);

        return $delivery->fresh();
    }
}
