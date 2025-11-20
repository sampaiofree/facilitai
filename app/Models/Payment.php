<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;


class Payment extends Model
{
    use HasFactory;

    /**
     * Os atributos que podem ser preenchidos em massa.
     */
    protected $fillable = [
        'user_id',
        'instance_id',
        'gateway',
        'gateway_transaction_id',
        'payer_email',
        'status',
        'amount',
        'payload',
        'expires_at', // <-- ADICIONE ESTA LINHA
        'credential_default', // <-- E ESTA
        'assistant_slots',
    ];

    protected static function boot()
    {
        parent::boot();

        // Define um evento que dispara ANTES de um novo registro ser criado.
        static::creating(function ($payment) {
            // Se a data de vencimento não for definida manualmente,
            // define-a para 31 dias no futuro a partir de agora.
            if (empty($payment->expires_at)) {
                $payment->expires_at = Carbon::now()->addDays(31);
            }
        });
    }

    /**
     * Os atributos que devem ser convertidos para tipos nativos.
     * Isso garante que o 'payload' seja sempre um array/objeto, e não uma string.
     */
    protected $casts = [
        'payload' => 'array',
    ];

    /**
     * Relacionamento: Um pagamento pertence a um usuário (User).
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Relacionamento: Um pagamento pertence a uma instância (Instance).
     */
    public function instance()
    {
        return $this->belongsTo(Instance::class);
    }

    public function assistants()
    {
        // Um pagamento pode dar origem a múltiplos assistentes (se assistant_slots > 1)
        return $this->hasMany(Assistant::class);
    }
}