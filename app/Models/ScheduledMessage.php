<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ScheduledMessage extends Model
{
    use HasFactory;

    protected $fillable = [
        'cliente_lead_id',
        'assistant_id',
        'conexao_id',
        'mensagem',
        'scheduled_for',
        'status',
        'event_id',
        'queued_at',
        'sent_at',
        'failed_at',
        'canceled_at',
        'error_message',
        'attempts',
        'created_by_user_id',
    ];

    protected $casts = [
        'scheduled_for' => 'datetime',
        'queued_at' => 'datetime',
        'sent_at' => 'datetime',
        'failed_at' => 'datetime',
        'canceled_at' => 'datetime',
        'attempts' => 'integer',
    ];

    public function clienteLead()
    {
        return $this->belongsTo(ClienteLead::class, 'cliente_lead_id');
    }

    public function assistant()
    {
        return $this->belongsTo(Assistant::class);
    }

    public function conexao()
    {
        return $this->belongsTo(Conexao::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }
}

