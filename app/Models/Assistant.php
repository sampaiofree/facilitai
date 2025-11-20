<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Assistant extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'payment_id',
        'credential_id',
        'openai_assistant_id',
        'name',
        'instructions',
        'systemPrompt',
        'developerPrompt',
        'delay', // Adicione este campo
        'modelo', // Adicione este campo
        'version',
    ];

    // Relacionamento: Um assistente pertence a um usuário
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // Relacionamento: Um assistente foi criado por um pagamento
    public function payment()
    {
        return $this->belongsTo(Payment::class);
    }

    // Relacionamento: Um assistente usa uma credencial
    public function credential()
    {
        return $this->belongsTo(Credential::class);
    } 

    public function instances()
    {
        return $this->hasMany(Instance::class, 'default_assistant_id', 'id');
    }

    public function chats()
    {
        return $this->hasMany(Chat::class, 'assistant_id', 'id');
    }


 
}