<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Chat extends Model
{
    use HasFactory;
    public $timestamps = true;
    /**
     * Os atributos que podem ser preenchidos em massa.
     */
    protected $fillable = [
        'user_id',
        'instance_id',
        'contact',
        'assistant_id',
        'thread_id',
        'bot_enabled',
        'conv_id',
        'version',
        'nome',
        'informacoes',
        'aguardando_atendimento',
    ];

    /**
     * Define os valores padrão para novos modelos.
     */
    protected $attributes = [
        'bot_enabled' => true,
    ]; 

    /**
     * Relacionamento: Um chat pertence a um usuário.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Relacionamento: Um chat pertence a uma instância.
     */
    public function instance()
    {
        return $this->belongsTo(Instance::class);
    }

    public function defaultAssistant()
    {
        return $this->belongsTo(Assistant::class, 'assistant_id', 'id');
    }

    public function defaultAssistantByOpenAi()
    {
        return $this->belongsTo(Assistant::class, 'assistant_id', 'openai_assistant_id');
    }

    public function getAssistenteAttribute()
    {
        return $this->defaultAssistant 
            ?? $this->defaultAssistantByOpenAi;
    }

    public function assistant()
    {
        return $this->belongsTo(Assistant::class, 'assistant_id', 'id');
    }

    public function tags()
    {
        return $this->belongsToMany(Tag::class)->withTimestamps();
    }

    public function sequenceChats()
    {
        return $this->hasMany(\App\Models\SequenceChat::class);
    }
}
