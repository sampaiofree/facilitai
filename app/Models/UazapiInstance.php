<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UazapiInstance extends Model
{
    use HasFactory;

    protected $table = 'uazapi_instance';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'token',
        'status',
        'name',
        'user_id',
        'proxy_ip',
        'assistant_id',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function assistant()
    {
        return $this->belongsTo(Assistant::class, 'assistant_id');
    }

    public function scopeByToken($query, string $token)
    {
        return $query->where('token', $token);
    }

    public static function findByToken(string $token): ?self
    {
        return self::where('token', $token)->first();
    }
}
