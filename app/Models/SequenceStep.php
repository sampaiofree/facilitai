<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SequenceStep extends Model
{
    use HasFactory;

    protected $fillable = [
        'sequence_id',
        'title',
        'ordem',
        'atraso_tipo',
        'atraso_valor',
        'janela_inicio',
        'janela_fim',
        'dias_semana',
        'prompt',
        'active',
    ];

    protected $casts = [
        'active' => 'boolean',
        'dias_semana' => 'array',
    ];

    public function sequence()
    {
        return $this->belongsTo(Sequence::class);
    }
}
