<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TokensOpenAI extends Model
{
    use HasFactory;

    /**
     * O nome da tabela associada ao modelo.
     *
     * @var string
     */
    protected $table = 'tokens_openai';

    /**
     * Os atributos que podem ser preenchidos em massa.
     */
    protected $fillable = [
        'instance_id',
        'credential_id',
        'conv_id',
        'contact',
        'tokens',
        'resp_id', 
        'user_id',
    ];

    /**
     * Relacionamento: Um registro de token pertence a uma instância.
     */
    public function instance()
    {
        return $this->belongsTo(Instance::class);
    }

    public function credential()
    {
        return $this->belongsTo(Credential::class);
    }
    
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    protected static function booted()
    {
        static::deleting(function ($model) {
            // Impede exclusão direta de tokens
            return false;
        });
    }
}