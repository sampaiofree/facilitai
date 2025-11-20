<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Agenda extends Model
{
    use HasFactory;
    protected $fillable = [
        'user_id',
        'titulo',
        'descricao',
        'slug',
        'limite_por_horario'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function disponibilidades()
    {
        return $this->hasMany(Disponibilidade::class);
    }
    
}
