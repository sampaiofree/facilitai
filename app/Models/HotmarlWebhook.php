<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HotmarlWebhook extends Model
{
    use HasFactory;

    protected $fillable = [
        'event',
        'product_id',
        'buyer_email',
        'buyer_name',
        'buyer_first_name',
        'buyer_last_name',
        'buyer_checkout_phone_code',
        'buyer_checkout_phone',
        'status',
        'transaction',
        'offer_code',
        'full_payload',
    ];

    protected $casts = [
        'full_payload' => 'array',
    ];

    public function user() 
    {
        return $this->belongsTo(User::class, 'buyer_email', 'email');
    }
}