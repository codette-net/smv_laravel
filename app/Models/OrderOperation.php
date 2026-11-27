<?php

namespace App\Models;

use App\Enums\OrderOperationStatus;
use App\Enums\OrderOperationType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderOperation extends Model
{
    protected $fillable = [
        'order_id',
        'payment_id',
        'operator_user_id',
        'type',
        'status',
        'reason',
        'amount_cents',
        'currency',
        'provider',
        'provider_reference',
        'provider_status',
        'idempotency_key',
        'failure_code',
        'failure_message',
        'metadata',
        'requested_at',
        'completed_at',
    ];

    protected function casts(): array
    {
        return [
            'type' => OrderOperationType::class,
            'status' => OrderOperationStatus::class,
            'amount_cents' => 'integer',
            'metadata' => 'array',
            'requested_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function payment(): BelongsTo
    {
        return $this->belongsTo(Payment::class);
    }

    public function operator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'operator_user_id');
    }

    public function providerSucceeded(): bool
    {
        return $this->provider_status === 'succeeded';
    }
}
