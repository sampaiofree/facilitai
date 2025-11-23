<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SequenceLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'sequence_chat_id',
        'sequence_step_id',
        'status',
        'message',
    ];

    public function sequenceChat()
    {
        return $this->belongsTo(SequenceChat::class);
    }

    public function sequenceStep()
    {
        return $this->belongsTo(SequenceStep::class);
    }
}
