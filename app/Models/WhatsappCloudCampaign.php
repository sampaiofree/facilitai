<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WhatsappCloudCampaign extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'created_by_user_id',
        'cliente_id',
        'conexao_id',
        'whatsapp_cloud_account_id',
        'whatsapp_cloud_template_id',
        'name',
        'mode',
        'status',
        'scheduled_for',
        'total_leads',
        'queued_count',
        'sent_count',
        'failed_count',
        'skipped_count',
        'started_at',
        'finished_at',
        'canceled_at',
        'filter_payload',
        'settings',
        'last_error',
    ];

    protected $casts = [
        'scheduled_for' => 'datetime',
        'started_at' => 'datetime',
        'finished_at' => 'datetime',
        'canceled_at' => 'datetime',
        'filter_payload' => 'array',
        'settings' => 'array',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    public function cliente()
    {
        return $this->belongsTo(Cliente::class, 'cliente_id');
    }

    public function conexao()
    {
        return $this->belongsTo(Conexao::class, 'conexao_id');
    }

    public function account()
    {
        return $this->belongsTo(WhatsappCloudAccount::class, 'whatsapp_cloud_account_id');
    }

    public function template()
    {
        return $this->belongsTo(WhatsappCloudTemplate::class, 'whatsapp_cloud_template_id');
    }

    public function items()
    {
        return $this->hasMany(WhatsappCloudCampaignItem::class, 'whatsapp_cloud_campaign_id');
    }
}

