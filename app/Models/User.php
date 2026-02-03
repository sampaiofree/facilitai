<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Auth\Notifications\ResetPassword as ResetPasswordNotification;
use Illuminate\Auth\Notifications\VerifyEmail as VerifyEmailNotification;
use App\Models\Conexao;
use App\Models\Plan;
use App\Models\Sequence;

class User extends Authenticatable implements MustVerifyEmail 
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'is_admin',
        'cpf_cnpj',
        'customer_asaas_id',
        'mobile_phone',
        'plan_id',
        'storage_used_mb',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'storage_used_mb' => 'integer',
        ];
    }

    /**
     * Get all of the instances for the User
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function clientes()
    {
        return $this->hasMany(\App\Models\Cliente::class);
    }

    public function conexoes()
    {
        return $this->hasManyThrough(
            \App\Models\Conexao::class,
            \App\Models\Cliente::class,
            'user_id',
            'cliente_id',
            'id',
            'id'
        );
    }

    public function conexoesCount(): int
    {
        return Conexao::whereHas('cliente', fn ($q) => $q->where('user_id', $this->id))->count();
    }

    public function instances()
    {
        return $this->hasMany(Instance::class);
    }

    public function agendas()
    {
        return $this->hasMany(Agenda::class);
    }

    public function credentials()
    {
        return $this->hasMany(\App\Models\Credential::class);
    }

    public function payments()
    {
        return $this->hasMany(Payment::class);
    }

    public function assistants()
    {
        return $this->hasMany(Assistant::class);
    }

    /**
     * Verifica se o usuário tem permissão para acessar a área de gerenciamento de credenciais.
     * (Plano Premium)
     */
    // Em app/Models/User.php

    //AQUI VERIFICAMOS SE O USUÁRIO ESTÁ NO PLANO PAGO (TRUE) OU GRATUITO(FALSE)
    public function canManageCredentials(): bool //ACESSO A FERRAMENTAS ESPECIAIS 
    {
        return true;
    }

    /**
     * Calcula quantos "slots" para criar novos assistentes o usuário ainda tem.
     */
   // Em app/Models/User.php

    public function availableAssistantSlots(): int
    {
        return max(0, $this->slots() - $this->assistants()->count());
    }

    public function availableInstanceSlots(): int
    {
        return max(0, $this->slots() - $this->instances()->count());
    }

    public function availableAgendaSlots(): int
    {
        return max(0, $this->slots() - $this->agendas()->count());
    }


    public function slots(): int
    {
        if ($this->is_admin) {
            return 20;
        }

        $limit = $this->plan?->max_conexoes;
        if ($limit && $limit > 0) {
            return (int) $limit;
        }

        return 1;
    }

    /**
     * OBTÉM TODAS AS CONVERSAS (CHATS) ASSOCIADAS A ESTE USUÁRIO.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function chats() // <-- Chat removido
    {
        // Relacionamento suspenso.
        return null;
    }

    public function images()
    {
        return $this->hasMany(\App\Models\Image::class);
    }

    public function libraryEntries()
    {
        return $this->hasMany(\App\Models\LibraryEntry::class);
    }

    public function tags()
    {
        return $this->hasMany(\App\Models\Tag::class);
    }

    public function sequences()
    {
        return $this->hasMany(Sequence::class);
    }

    public function folders()
    {
        return $this->hasMany(\App\Models\Folder::class);
    }

    public function plan()
    {
        return $this->belongsTo(Plan::class);
    }

    public function hotmartWebhooks()
    {
        //RETORNA A OFERTA OU NULL
        return $this->hasOne(\App\Models\HotmarlWebhook::class, 'buyer_email', 'email')->whereIn('event', ['PURCHASE_COMPLETE', 'PURCHASE_APPROVED'])->latest('id')->first();

    }

     public function sendPasswordResetNotification($token)
    {
        $url = url(route('password.reset', [
            'token' => $token,
            'email' => $this->email,
        ], false));

        $this->notify(new class($url) extends ResetPasswordNotification {
            public $url;

            public function __construct($url)
            {
                $this->url = $url;
            }

            public function toMail($notifiable)
            {
                return (new \Illuminate\Notifications\Messages\MailMessage)
                    ->subject('Redefinição de senha')
                    ->line('Você está recebendo este e-mail porque recebemos uma solicitação de redefinição de senha para sua conta.')
                    ->action('Redefinir senha', $this->url)
                    ->line('Se você não solicitou uma redefinição de senha, nenhuma ação é necessária.');
            }
        });
    }

    public function sendEmailVerificationNotification()
    {
        $this->notify(new class extends VerifyEmailNotification {
            public function toMail($notifiable)
            {
                return (new \Illuminate\Notifications\Messages\MailMessage)
                    ->subject('Confirmação de e-mail')
                    ->line('Obrigado por se cadastrar! Clique no botão abaixo para confirmar seu endereço de e-mail.')
                    ->action('Confirmar e-mail', $this->verificationUrl($notifiable))
                    ->line('Se você não criou uma conta, ignore este e-mail.');
            }
        });
    }
 
    public function asaasWebhooks()
    {
        return $this->hasMany(\App\Models\AsaasWebhook::class, 'customer_id', 'customer_asaas_id');
    }

    public function totalTokens(): int
    {
        return $this->asaasWebhooks()
            ->whereIn('event_type', ['PAYMENT_RECEIVED', 'PAYMENT_CONFIRMED']) // só pagos
            ->sum('external_reference'); // external_reference = quantidade de tokens
    }

    public function tokensOpenAI()
    {
        return $this->hasMany(\App\Models\TokensOpenAI::class, 'user_id');
    }

    public function totalTokensUsed(): int
    {
        return $this->tokensOpenAI()
            ->whereNull('credential_id')
            ->sum('tokens') + $this->tokensViaInstance();
    }

    public function tokensViaInstance(): int
    {
        return \App\Models\TokensOpenAI::query()
            ->whereNull('credential_id')
            ->whereNull('user_id')
            ->whereHas('instance', function ($q) {
                $q->where('user_id', $this->id);
            })
            ->sum('tokens');
    }


    public function tokensAvailable(): int
    {
        return max(0, $this->totalTokens() + $this->tokensBonusValidos() - $this->totalTokensUsed());
    }

    public function tokensBonus()
    {
        return $this->hasMany(TokenBonus::class)->orderBy('inicio', 'asc');
    }

    public function tokensBonusValidos()
    {
        return $this->tokensBonus()
            ->whereDate('inicio', '<=', now())
            //->whereDate('fim', '>=', now())
            ->sum('tokens');
    }


}
 
