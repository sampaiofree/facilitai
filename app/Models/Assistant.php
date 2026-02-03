<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Cliente;

class Assistant extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'cliente_id',
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
        'prompt_notificar_adm',
        'prompt_buscar_get',
        'prompt_enviar_media',
        'prompt_registrar_info_chat',
        'prompt_gerenciar_agenda',
        'prompt_aplicar_tags',
        'prompt_sequencia',
    ];

    // Relacionamento: Um assistente pertence a um usuário
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function cliente()
    {
        return $this->belongsTo(Cliente::class);
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
        // Relacionamento com chats removido.
        return null;
    }


 
}
