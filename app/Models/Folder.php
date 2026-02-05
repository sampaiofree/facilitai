<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Folder extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'cliente_id',
        'name',
        'storage_used_mb',
        'storage_limit_mb',
    ];

    protected $casts = [
        'storage_used_mb' => 'integer',
        'storage_limit_mb' => 'integer',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function cliente()
    {
        return $this->belongsTo(Cliente::class);
    }

    public function images()
    {
        return $this->hasMany(Image::class);
    }
}
