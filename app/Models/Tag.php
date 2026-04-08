<?php

namespace App\Models;

use App\Support\TagScope;
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

    protected $hidden = [
        'scope_key',
    ];

    protected static function booted(): void
    {
        static::saving(function (Tag $tag): void {
            if (!$tag->user_id) {
                return;
            }

            $tag->scope_key = TagScope::scopeKey((int) $tag->user_id, $tag->cliente_id ? (int) $tag->cliente_id : null);
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

    public function chats()
    {
        // Relacionamento com chats removido.
        return null;
    }

    public function clienteLeads()
    {
        return $this->belongsToMany(ClienteLead::class, 'cliente_lead_tag')->withTimestamps();
    }

    public function crmPipelineColumn()
    {
        return $this->hasOne(ClienteCrmPipelineColumn::class);
    }

    public function getScopeLabelAttribute(): string
    {
        return TagScope::scopeLabel($this->cliente?->nome, $this->cliente_id ? (int) $this->cliente_id : null);
    }

    public function getDisplayLabelAttribute(): string
    {
        return TagScope::displayLabel($this->name, $this->cliente?->nome, $this->cliente_id ? (int) $this->cliente_id : null);
    }
}
