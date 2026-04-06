<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ClienteCrmPipelineColumn extends Model
{
    use HasFactory;

    protected $fillable = [
        'pipeline_id',
        'tag_id',
        'position',
    ];

    protected $casts = [
        'position' => 'integer',
    ];

    public function pipeline()
    {
        return $this->belongsTo(ClienteCrmPipeline::class, 'pipeline_id');
    }

    public function tag()
    {
        return $this->belongsTo(Tag::class);
    }
}
