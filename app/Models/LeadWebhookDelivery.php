<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LeadWebhookDelivery extends Model
{
    use HasFactory;

    protected $fillable = [
        'lead_webhook_link_id',
        'status',
        'payload',
        'payload_hash',
        'cliente_lead_id',
        'resolved_phone',
        'result',
        'error_message',
        'processed_at',
    ];

    protected $casts = [
        'payload' => 'array',
        'result' => 'array',
        'processed_at' => 'datetime',
    ];

    public function link(): BelongsTo
    {
        return $this->belongsTo(LeadWebhookLink::class, 'lead_webhook_link_id');
    }

    public function lead(): BelongsTo
    {
        return $this->belongsTo(ClienteLead::class, 'cliente_lead_id');
    }
}
