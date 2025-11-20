<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Crypt; // <-- Importante

class Credential extends Model
{
    use HasFactory;

    /**
     * Os atributos que podem ser preenchidos em massa.
     */
    protected $fillable = [
        'name',
        'label',
        'token',
    ];

    /**
     * Criptografa o token automaticamente antes de salvar no banco.
     */
    public function setTokenAttribute($value)
    {
        $this->attributes['token'] = Crypt::encryptString($value);
    }

    /**
     * Descriptografa o token automaticamente ao acessá-lo.
     */
    public function getTokenAttribute($value)
    {
        try {
            return Crypt::decryptString($value);
        } catch (\Exception $e) {
            return '******'; // Retorna um placeholder em caso de erro de descriptografia
        }
    }
}