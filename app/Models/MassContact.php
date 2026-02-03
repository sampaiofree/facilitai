<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class MassContact extends Model
{
    use HasFactory;

    protected $fillable = [
        'campaign_id',
        'numero',
        'status',
        'tentativa',
        'enviado_em',
    ];

    public function campaign()
    {
        return $this->belongsTo(MassCampaign::class, 'campaign_id');
    }

    public function chat()
    {
        // Relacionamento com chats removido.
        return null;
    }
}
