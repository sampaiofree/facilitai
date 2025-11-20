<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AsaasWebhook extends Model
{
    use HasFactory;

    protected $fillable = [
        'webhook_id',
        'event_type',
        'webhook_created_at',
        'payment_id',
        'payment_created_at',
        'customer_id',
        'value',
        'description',
        'billing_type',
        'confirmed_at',
        'status',
        'payment_at',
        'client_payment_at',
        'invoice_url',
        'external_reference',
        'transaction_receipt_url',
        'nosso_numero',
        'payload',
    ];

    /**
     * Define o campo 'payload' como um atributo JSON.
     * Isso faz com que ele seja automaticamente serializado e deserializado.
     */
    protected $casts = [
        'payload' => 'array',
        'webhook_created_at' => 'datetime',
        'payment_created_at' => 'date',
        'confirmed_at' => 'date',
        'payment_at' => 'date',
        'client_payment_at' => 'date',
    ];
}