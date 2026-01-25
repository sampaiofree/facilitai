<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Assistant;
use App\Models\Lead;

class AssistantLead extends Model
{
    use HasFactory;

    protected $table = 'assistant_lead';

    protected $fillable = [
        'lead_id',
        'assistant_id',
        'version',
        'conv_id',
    ];

    public function lead()
    {
        return $this->belongsTo(Lead::class);
    }

    public function assistant()
    {
        return $this->belongsTo(Assistant::class);
    }
}
