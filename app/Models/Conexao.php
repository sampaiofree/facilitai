<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Assistant;
use App\Models\Cliente;
use App\Models\Credential;
use App\Models\Iamodelo;
use App\Models\WhatsappApi;
use App\Models\WhatsappCloudAccount;
use App\Models\WhatsappCloudConversationWindow;

class Conexao extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'conexoes';

    protected $fillable = [
        'name',
        'informacoes',
        'cliente_id',
        'status',
        'is_active',
        'phone',
        'proxy_ip',
        'proxy_port',
        'proxy_username',
        'proxy_password',
        'whatsapp_api_id',
        'whatsapp_cloud_account_id',
        'whatsapp_api_key',
        'credential_id',
        'assistant_id',
        'model',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    protected static function booted(): void
    {
        static::deleted(function (Conexao $conexao): void {
            if ($conexao->isForceDeleting() || $conexao->whatsapp_cloud_account_id === null) {
                return;
            }

            // Free the cloud account link on soft delete so it can be reused by a new conexão.
            $conexao->forceFill(['whatsapp_cloud_account_id' => null])->saveQuietly();
        });
    }

    public function cliente()
    {
        return $this->belongsTo(Cliente::class);
    }

    public function credential()
    {
        return $this->belongsTo(Credential::class);
    }

    public function whatsappApi()
    {
        return $this->belongsTo(WhatsappApi::class);
    }

    public function whatsappCloudAccount()
    {
        return $this->belongsTo(WhatsappCloudAccount::class, 'whatsapp_cloud_account_id');
    }

    public function assistant()
    {
        return $this->belongsTo(Assistant::class);
    }

    public function iamodelo()
    {
        return $this->belongsTo(Iamodelo::class, 'model');
    }

    public function sequenceChats()
    {
        return $this->hasMany(SequenceChat::class);
    }

    public function whatsappCloudConversationWindows()
    {
        return $this->hasMany(WhatsappCloudConversationWindow::class, 'conexao_id');
    }

    public function whatsappCloudCampaigns()
    {
        return $this->hasMany(WhatsappCloudCampaign::class, 'conexao_id');
    }
}
