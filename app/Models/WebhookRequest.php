<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WebhookRequest extends Model
{
    use HasFactory;

    protected $fillable = [
        'instance_id',
        'remote_jid',
        'contact',
        'from_me',
        'message_type',
        'event_id',
        'message_timestamp',
        'message_text',
        'payload',
    ];

    protected $casts = [
        'from_me' => 'boolean',
        'payload' => 'array',
        'message_timestamp' => 'integer',
    ];

    public function getMessagePreviewAttribute(): string
    {
        $text = $this->message_text ?? data_get($this->payload, 'data.message.conversation', '');

        return \Illuminate\Support\Str::limit((string) $text, 120);
    }
}
