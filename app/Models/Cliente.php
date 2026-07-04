<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Conexao;
use App\Models\Credential;

class Cliente extends Authenticatable
{
    use HasFactory, SoftDeletes;

    protected $table = 'clientes';

    protected $fillable = [
        'user_id',
        'nome',
        'email',
        'telefone',
        'password',
        'is_active',
        'can_access_groups',
        'last_login_at',
        'metadata',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'can_access_groups' => 'boolean',
        'last_login_at' => 'datetime',
        'metadata' => 'array',
        'password' => 'hashed',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function conexoes()
    {
        return $this->hasMany(Conexao::class, 'cliente_id');
    }

    public function credentials()
    {
        return $this->hasMany(Credential::class, 'cliente_id');
    }

    public function whatsappCloudCampaigns()
    {
        return $this->hasMany(WhatsappCloudCampaign::class, 'cliente_id');
    }

    public function crmPipelines()
    {
        return $this->hasMany(ClienteCrmPipeline::class, 'cliente_id');
    }

    public function kommoAccounts()
    {
        return $this->hasMany(KommoAccount::class, 'cliente_id');
    }
}
