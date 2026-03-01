<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WhatsappCloudTemplate extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'whatsapp_cloud_account_id',
        'conexao_id',
        'title',
        'template_name',
        'language_code',
        'category',
        'variables',
        'body_text',
        'footer_text',
        'buttons',
        'variable_examples',
        'status',
        'meta_template_id',
        'last_sync_error',
        'last_synced_at',
    ];

    protected $casts = [
        'variables' => 'array',
        'buttons' => 'array',
        'variable_examples' => 'array',
        'last_synced_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(WhatsappCloudAccount::class, 'whatsapp_cloud_account_id');
    }

    public function conexao(): BelongsTo
    {
        return $this->belongsTo(Conexao::class);
    }

    public function campaigns()
    {
        return $this->hasMany(WhatsappCloudCampaign::class, 'whatsapp_cloud_template_id');
    }
}
