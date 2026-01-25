<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WhatsappApi extends Model
{
    protected $table = 'whatsapp_api';

    protected $fillable = [
        'nome',
        'descricao',
        'slug',
        'ativo',
    ];
}
