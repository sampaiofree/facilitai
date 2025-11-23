<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Sequence extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'name',
        'description',
        'active',
        'tags_incluir',
        'tags_excluir',
    ];

    protected $casts = [
        'active' => 'boolean',
        'tags_incluir' => 'array',
        'tags_excluir' => 'array',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function steps()
    {
        return $this->hasMany(SequenceStep::class)->orderBy('ordem');
    }

    public function chats()
    {
        return $this->hasMany(SequenceChat::class);
    }
}
