<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ClienteCrmPipeline extends Model
{
    use HasFactory;

    protected $fillable = [
        'cliente_id',
        'name',
        'position',
    ];

    protected $casts = [
        'position' => 'integer',
    ];

    public function cliente()
    {
        return $this->belongsTo(Cliente::class, 'cliente_id');
    }

    public function columns()
    {
        return $this->hasMany(ClienteCrmPipelineColumn::class, 'pipeline_id');
    }
}
