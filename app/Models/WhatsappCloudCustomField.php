<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class WhatsappCloudCustomField extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'cliente_id',
        'name',
        'label',
        'sample_value',
        'description',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function cliente(): BelongsTo
    {
        return $this->belongsTo(Cliente::class);
    }

    public function leadValues(): HasMany
    {
        return $this->hasMany(ClienteLeadCustomField::class, 'whatsapp_cloud_custom_field_id');
    }
}
