<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Image extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'path',
        'original_name',
        'size',
    ];

    // Adiciona o atributo 'url' ao modelo sem precisar de uma coluna no banco
    protected $appends = ['url'];

    // Relacionamento: Uma imagem pertence a um usuário
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // Acessor: Gera a URL pública completa para a imagem
    public function getUrlAttribute(): string
    {
        return Storage::disk('public')->url($this->path);
    }
}
