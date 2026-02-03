<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Disponibilidade extends Model
{
    use HasFactory;
    
    protected $fillable = [
        'agenda_id',
        'data',
        'inicio',
        'fim',
        'ocupado',
        'nome',
        'telefone',
        'observacoes',
    ];

    public function agenda()
    {
        return $this->belongsTo(Agenda::class);
    }

    public function chat()
    {
        // Relacionamento com chats removido.
        return null;
    }
}
