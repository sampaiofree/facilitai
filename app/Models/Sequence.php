<?php

namespace App\Models;

use App\Models\Cliente;
use App\Models\Conexao;
use App\Models\SequenceChat;
use App\Models\SequenceLog;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Sequence extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'cliente_id',
        'conexao_id',
        'name',
        'description',
        'active',
        'tags_incluir',
        'tags_excluir',
    ];

    protected $casts = [
        'active' => 'boolean',
        'tags_incluir' => 'array',
        'tags_excluir' => 'array',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function steps()
    {
        return $this->hasMany(SequenceStep::class)->orderBy('ordem');
    }

    public function chats()
    {
        return $this->hasMany(SequenceChat::class);
    }

    public function logs()
    {
        return $this->hasManyThrough(SequenceLog::class, SequenceChat::class)
            ->orderBy('created_at', 'desc');
    }

    public function cliente()
    {
        return $this->belongsTo(Cliente::class);
    }

    public function conexao()
    {
        return $this->belongsTo(Conexao::class);
    }
}
