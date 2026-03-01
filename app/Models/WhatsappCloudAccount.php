<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Crypt;

class WhatsappCloudAccount extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'name',
        'phone_number_id',
        'business_account_id',
        'app_id',
        'app_secret',
        'access_token',
        'is_default',
    ];

    protected $casts = [
        'is_default' => 'boolean',
    ];

    public function setAccessTokenAttribute($value): void
    {
        $value = trim((string) $value);
        if ($value === '') {
            return;
        }

        $this->attributes['access_token'] = Crypt::encryptString($value);
    }

    public function getAccessTokenAttribute($value): string
    {
        try {
            return Crypt::decryptString((string) $value);
        } catch (\Throwable) {
            return (string) $value;
        }
    }

    public function setAppSecretAttribute($value): void
    {
        $value = trim((string) $value);
        if ($value === '') {
            return;
        }

        $this->attributes['app_secret'] = Crypt::encryptString($value);
    }

    public function getAppSecretAttribute($value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        try {
            return Crypt::decryptString((string) $value);
        } catch (\Throwable) {
            return (string) $value;
        }
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function templates(): HasMany
    {
        return $this->hasMany(WhatsappCloudTemplate::class);
    }

    public function conexoes(): HasMany
    {
        return $this->hasMany(Conexao::class, 'whatsapp_cloud_account_id');
    }

    public function campaigns(): HasMany
    {
        return $this->hasMany(WhatsappCloudCampaign::class, 'whatsapp_cloud_account_id');
    }
}
