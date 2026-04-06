<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Crypt;

class KommoAccount extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'cliente_id',
        'name',
        'subdomain',
        'access_token',
        'kommo_account_id',
        'kommo_account_name',
        'pipeline_id',
        'pipeline_name',
        'status_id',
        'status_name',
        'last_verified_at',
        'last_verification_error',
    ];

    protected $casts = [
        'last_verified_at' => 'datetime',
    ];

    protected $hidden = [
        'access_token',
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

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function cliente(): BelongsTo
    {
        return $this->belongsTo(Cliente::class);
    }
}
