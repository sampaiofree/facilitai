<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class LeadWebhookLink extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id',
        'cliente_id',
        'conexao_id',
        'name',
        'token',
        'is_active',
        'config',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'config' => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function cliente(): BelongsTo
    {
        return $this->belongsTo(Cliente::class);
    }

    public function conexao(): BelongsTo
    {
        return $this->belongsTo(Conexao::class);
    }

    public function deliveries(): HasMany
    {
        return $this->hasMany(LeadWebhookDelivery::class, 'lead_webhook_link_id');
    }

    public function latestDelivery(): HasOne
    {
        return $this->hasOne(LeadWebhookDelivery::class, 'lead_webhook_link_id')->latestOfMany();
    }
}
