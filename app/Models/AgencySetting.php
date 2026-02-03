<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\User;

class AgencySetting extends Model
{
    use HasFactory;

    protected $table = 'agency_settings';

    protected $fillable = [
        'user_id',
        'subdomain',
        'custom_domain',
        'domain_verified_at',
        'app_name',
        'logo_path',
        'favicon_path',
        'support_email',
        'support_whatsapp',
        'primary_color',
        'secondary_color',
        'timezone',
        'locale',
    ];

    protected $casts = [
        'domain_verified_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
