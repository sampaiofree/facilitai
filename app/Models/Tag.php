<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Tag extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'cliente_id',
        'name',
        'color',
        'description',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function cliente()
    {
        return $this->belongsTo(Cliente::class);
    }

    public function chats()
    {
        // Relacionamento com chats removido.
        return null;
    }

    public function clienteLeads()
    {
        return $this->belongsToMany(ClienteLead::class, 'cliente_lead_tag')->withTimestamps();
    }
}
