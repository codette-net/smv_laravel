<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    /** @use HasFactory<\Database\Factories\OrderFactory> */
    use HasFactory;

    protected $fillable = [
        'user_id',
        'company_id',
        'order_number',
        'status',
        'subtotal_cents',
        'vat_cents',
        'total_cents',
        'currency'
    ];
}
