<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ClienteCrmPipelineLead extends Model
{
    use HasFactory;

    protected $fillable = [
        'pipeline_id',
        'column_id',
        'cliente_lead_id',
    ];

    public function pipeline()
    {
        return $this->belongsTo(ClienteCrmPipeline::class, 'pipeline_id');
    }

    public function column()
    {
        return $this->belongsTo(ClienteCrmPipelineColumn::class, 'column_id');
    }

    public function lead()
    {
        return $this->belongsTo(ClienteLead::class, 'cliente_lead_id');
    }
}
