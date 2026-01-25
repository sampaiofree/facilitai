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

class Conexao extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'conexoes';

    protected $fillable = [
        'name',
        'informacoes',
        'cliente_id',
        'status',
        'phone',
        'proxy_ip',
        'proxy_port',
        'proxy_username',
        'proxy_password',
        'whatsapp_api_id',
        'whatsapp_api_key',
        'credential_id',
        'assistant_id',
        'model',
    ];

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

    public function assistant()
    {
        return $this->belongsTo(Assistant::class);
    }

    public function iamodelo()
    {
        return $this->belongsTo(Iamodelo::class, 'model');
    }
}
