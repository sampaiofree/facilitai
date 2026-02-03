<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Conexao;

class Cliente extends Authenticatable
{
    use HasFactory, SoftDeletes;

    protected $table = 'clientes';

    protected $fillable = [
        'user_id',
        'nome',
        'email',
        'telefone',
        'password',
        'is_active',
        'last_login_at',
        'metadata',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'last_login_at' => 'datetime',
        'metadata' => 'array',
        'password' => 'hashed',
    ];

    protected $hidden = [
        'password',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function conexoes()
    {
        return $this->hasMany(Conexao::class, 'cliente_id');
    }
}
