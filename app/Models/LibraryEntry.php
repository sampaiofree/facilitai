<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use App\Models\Cliente;

class LibraryEntry extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'cliente_id',
        'title',
        'slug',
        'content',
        'public_edit_token',
        'public_edit_password_hash',
        'public_edit_enabled',
    ];

    protected $casts = [
        'public_edit_enabled' => 'boolean',
    ];

    protected $hidden = [
        'public_edit_password_hash',
    ];

    protected static function booted(): void
    {
        static::creating(function (LibraryEntry $entry) {
            if (!$entry->public_edit_token) {
                $entry->public_edit_token = (string) Str::uuid();
            }
        });
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function cliente()
    {
        return $this->belongsTo(Cliente::class);
    }
}
