<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Assistant;
use App\Models\ClienteLead;

class AssistantLead extends Model
{
    use HasFactory;

    protected $table = 'assistant_lead';

    protected $fillable = [
        'lead_id',
        'assistant_id',
        'version',
        'conv_id',
        'webhook_payload',
        'assistant_response',
        'job_message',
    ];

    protected $casts = [
        'webhook_payload' => 'array',
        'assistant_response' => 'array',
    ];

    public function lead()
    {
        return $this->belongsTo(ClienteLead::class);
    }

    public function assistant()
    {
        return $this->belongsTo(Assistant::class);
    }
}
