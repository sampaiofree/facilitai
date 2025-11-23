<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

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

    public function getJanelaInicioAttribute($value)
    {
        return $this->formatTime($value);
    }

    public function setJanelaInicioAttribute($value): void
    {
        $this->attributes['janela_inicio'] = $this->formatTime($value);
    }

    public function getJanelaFimAttribute($value)
    {
        return $this->formatTime($value);
    }

    public function setJanelaFimAttribute($value): void
    {
        $this->attributes['janela_fim'] = $this->formatTime($value);
    }

    public function sequence()
    {
        return $this->belongsTo(Sequence::class);
    }

    private function formatTime($value): ?string
    {
        if (!$value) {
            return null;
        }

        if ($value instanceof \DateTimeInterface) {
            return Carbon::instance($value)->format('H:i');
        }

        if (preg_match('/^(\\d{2}:\\d{2})/', (string) $value, $matches)) {
            return $matches[1];
        }

        try {
            return Carbon::parse($value)->format('H:i');
        } catch (\Throwable $e) {
            return null;
        }
    }
}
