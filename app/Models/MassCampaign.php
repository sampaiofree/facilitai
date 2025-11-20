<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class MassCampaign extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'instance_id',
        'nome',
        'tipo_envio',
        'usar_ia',
        'mensagem',
        'intervalo_segundos',
        'total_contatos',
        'enviados',
        'falhas',
        'status',
    ];

    public function contatos()
    {
        return $this->hasMany(MassContact::class, 'campaign_id');
    }

    public function instance()
    {
        return $this->belongsTo(Instance::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
