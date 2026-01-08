<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SystemErrorLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'context',
        'function_name',
        'message',
        'instance_id',
        'user_id',
        'chat_id',
        'conversation_id',
        'payload',
    ];

    protected $casts = [
        'payload' => 'array',
    ];
}
