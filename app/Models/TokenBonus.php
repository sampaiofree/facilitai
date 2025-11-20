<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TokenBonus extends Model
{
    use HasFactory;

    protected $table = 'tokens_bonus';

    protected $fillable = [
        'user_id',
        'informacoes',
        'tokens',
        'inicio',
        'fim',
        'hotmart',
    ];

    protected $dates = [
        'inicio',
        'fim',
    ];

    // Relacionamento com o usuário
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
