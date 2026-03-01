<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WhatsappCloudConversationWindow extends Model
{
    use HasFactory;

    protected $fillable = [
        'cliente_lead_id',
        'conexao_id',
        'last_inbound_at',
        'last_outbound_at',
        'last_inbound_event_id',
    ];

    protected $casts = [
        'last_inbound_at' => 'datetime',
        'last_outbound_at' => 'datetime',
    ];

    public function lead(): BelongsTo
    {
        return $this->belongsTo(ClienteLead::class, 'cliente_lead_id');
    }

    public function conexao(): BelongsTo
    {
        return $this->belongsTo(Conexao::class, 'conexao_id');
    }
}

