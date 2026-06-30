<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    protected $fillable = [
        'order_id',
        'provider',
        'provider_payment_id',
        'status',
        'amount_cents',
        'currency',
        'paid_at',
        'raw_payload'
    ];

    protected $casts = [
        'raw_payload' => 'array'
    ];
}
