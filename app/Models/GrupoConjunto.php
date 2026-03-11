<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GrupoConjunto extends Model
{
    use HasFactory;

    protected $table = 'grupo_conjuntos';

    protected $fillable = [
        'user_id',
        'conexao_id',
        'name',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function conexao()
    {
        return $this->belongsTo(Conexao::class);
    }

    public function items()
    {
        return $this->hasMany(GrupoConjuntoItem::class)->orderBy('group_name')->orderBy('group_jid');
    }

    public function mensagens()
    {
        return $this->hasMany(GrupoConjuntoMensagem::class)->orderByDesc('created_at');
    }
}
