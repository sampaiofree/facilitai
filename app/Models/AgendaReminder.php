<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AgendaReminder extends Model
{
    use HasFactory;

    protected $fillable = [
        'agenda_id',
        'disponibilidade_id',
        'telefone',
        'instance_id',
        'mensagem_template',
        'offset_minutos',
        'agendado_em',
        'disparo_em',
        'status',
        'tentativas',
        'last_error',
        'sent_at',
    ];
}
