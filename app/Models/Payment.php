<?php

namespace App\Models;

use App\Enums\PaymentStatus;
use Database\Factories\PaymentFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Payment extends Model
{
    /** @use HasFactory<PaymentFactory> */
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'order_id',
        'idempotency_key',
        'provider',
        'provider_payment_id',
        'provider_reference',
        'status',
        'amount_cents',
        'currency',
        'paid_at',
        'completed_at',
        'raw_payload',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'status' => PaymentStatus::class,
            'amount_cents' => 'integer',
            'paid_at' => 'datetime',
            'completed_at' => 'datetime',
            'raw_payload' => 'array',
            'metadata' => 'array',
        ];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function operations(): HasMany
    {
        return $this->hasMany(OrderOperation::class);
    }

    public function redirectUrl(): ?string
    {
        $url = $this->metadata['redirect_url'] ?? $this->metadata['checkout_url'] ?? null;

        return is_string($url) && filter_var($url, FILTER_VALIDATE_URL) ? $url : null;
    }
}
