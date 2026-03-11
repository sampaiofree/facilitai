<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GrupoConjuntoItem extends Model
{
    use HasFactory;

    protected $table = 'grupo_conjunto_itens';

    protected $fillable = [
        'grupo_conjunto_id',
        'group_jid',
        'group_name',
    ];

    public function conjunto()
    {
        return $this->belongsTo(GrupoConjunto::class, 'grupo_conjunto_id');
    }
}
