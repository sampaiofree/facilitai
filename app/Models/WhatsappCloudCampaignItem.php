<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WhatsappCloudCampaignItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'whatsapp_cloud_campaign_id',
        'cliente_lead_id',
        'phone',
        'status',
        'attempts',
        'meta_message_id',
        'idempotency_key',
        'queued_at',
        'sent_at',
        'failed_at',
        'skipped_at',
        'resolved_variables',
        'rendered_message',
        'error_message',
        'meta_response',
    ];

    protected $casts = [
        'attempts' => 'integer',
        'queued_at' => 'datetime',
        'sent_at' => 'datetime',
        'failed_at' => 'datetime',
        'skipped_at' => 'datetime',
        'resolved_variables' => 'array',
        'meta_response' => 'array',
    ];

    public function campaign()
    {
        return $this->belongsTo(WhatsappCloudCampaign::class, 'whatsapp_cloud_campaign_id');
    }

    public function lead()
    {
        return $this->belongsTo(ClienteLead::class, 'cliente_lead_id');
    }
}

