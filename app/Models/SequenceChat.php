<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SequenceChat extends Model
{
    use HasFactory;

    protected $fillable = [
        'sequence_id',
        'chat_id',
        'passo_atual_id',
        'status',
        'iniciado_em',
        'proximo_envio_em',
        'criado_por',
    ];

    protected $casts = [
        'iniciado_em' => 'datetime',
        'proximo_envio_em' => 'datetime',
    ];

    public function sequence()
    {
        return $this->belongsTo(Sequence::class);
    }

    public function chat()
    {
        return $this->belongsTo(Chat::class);
    }

    public function step()
    {
        return $this->belongsTo(SequenceStep::class, 'passo_atual_id');
    }

    public function logs()
    {
        return $this->hasMany(SequenceLog::class);
    }
}
