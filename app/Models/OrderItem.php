<?php

namespace App\Models;

use Database\Factories\OrderItemFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class OrderItem extends Model
{
    /** @use HasFactory<OrderItemFactory> */
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'order_id',
        'package_id',
        'vacancy_id',
        'product_code',
        'title_snapshot',
        'price_cents',
        'currency',
        'vat_rate_basis_points',
        'quantity',
        'subtotal_cents',
        'vat_cents',
        'total_cents',
    ];

    protected function casts(): array
    {
        return [
            'price_cents' => 'integer',
            'vat_rate_basis_points' => 'integer',
            'quantity' => 'integer',
            'subtotal_cents' => 'integer',
            'vat_cents' => 'integer',
            'total_cents' => 'integer',
        ];
    }

    public function package(): BelongsTo
    {
        return $this->belongsTo(Package::class);
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function vacancy(): BelongsTo
    {
        return $this->belongsTo(Vacancy::class);
    }
}
