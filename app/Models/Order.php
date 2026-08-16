<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

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

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function order_items(): hasMany
    {
        return $this->hasMany(OrderItem::class);
    }
    public function payment(): hasMany
    {
        return $this->hasMany(Payment::class);
    }
}
