<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ClienteLeadCustomField extends Model
{
    use HasFactory;

    protected $fillable = [
        'cliente_lead_id',
        'whatsapp_cloud_custom_field_id',
        'value',
    ];

    public function lead()
    {
        return $this->belongsTo(ClienteLead::class, 'cliente_lead_id');
    }

    public function customField()
    {
        return $this->belongsTo(WhatsappCloudCustomField::class, 'whatsapp_cloud_custom_field_id');
    }
}
