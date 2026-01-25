<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UazapiChat extends Model
{
    use HasFactory;

    protected $table = 'uazapi_chat';

    protected $fillable = [
        'nome',
        'phone',
        'uazapi_instance_id',
        'bot_enabled',
        'conv_id',
        'version',
        'informacoes',
        'aguardando_atendimento',
    ];

    public function instance()
    {
        return $this->belongsTo(UazapiInstance::class, 'uazapi_instance_id');
    }

    public function scopeByInstance($query, string $instanceId)
    {
        return $query->where('uazapi_instance_id', $instanceId);
    }
}
