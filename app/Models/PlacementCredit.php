<?php

namespace App\Models;

use App\Enums\PlacementCreditStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PlacementCredit extends Model
{
    protected $fillable = [
        'company_id',
        'order_id',
        'order_item_id',
        'package_id',
        'vacancy_id',
        'grant_sequence',
        'product_code',
        'package_name_snapshot',
        'package_type_snapshot',
        'duration_days_snapshot',
        'commercial_priority_snapshot',
        'benefits_snapshot',
        'granted_at',
        'allocated_at',
        'activated_at',
        'expires_at',
        'reversed_at',
        'reversal_reason',
        'review_required_at',
        'review_reason',
    ];

    protected function casts(): array
    {
        return [
            'grant_sequence' => 'integer',
            'duration_days_snapshot' => 'integer',
            'commercial_priority_snapshot' => 'integer',
            'benefits_snapshot' => 'array',
            'granted_at' => 'datetime',
            'allocated_at' => 'datetime',
            'activated_at' => 'datetime',
            'expires_at' => 'datetime',
            'reversed_at' => 'datetime',
            'review_required_at' => 'datetime',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function orderItem(): BelongsTo
    {
        return $this->belongsTo(OrderItem::class);
    }

    public function package(): BelongsTo
    {
        return $this->belongsTo(Package::class);
    }

    public function vacancy(): BelongsTo
    {
        return $this->belongsTo(Vacancy::class);
    }

    public function events(): HasMany
    {
        return $this->hasMany(PlacementCreditEvent::class)->orderBy('occurred_at')->orderBy('id');
    }

    /** @param Builder<PlacementCredit> $query */
    public function scopeAvailable(Builder $query): Builder
    {
        return $query->whereNull('vacancy_id')->whereNull('reversed_at')->whereNull('review_required_at');
    }

    /** @param Builder<PlacementCredit> $query */
    public function scopeActive(Builder $query): Builder
    {
        return $query
            ->whereNull('reversed_at')
            ->whereNull('review_required_at')
            ->whereNotNull('activated_at')
            ->where('activated_at', '<=', now())
            ->where('expires_at', '>=', now());
    }

    public function status(): PlacementCreditStatus
    {
        if ($this->reversed_at !== null) {
            return PlacementCreditStatus::Reversed;
        }

        if ($this->review_required_at !== null) {
            return PlacementCreditStatus::RequiresReview;
        }

        if ($this->vacancy_id === null) {
            return PlacementCreditStatus::Available;
        }

        if ($this->activated_at === null) {
            return PlacementCreditStatus::Allocated;
        }

        if ($this->activated_at->isFuture()) {
            return PlacementCreditStatus::Scheduled;
        }

        return $this->expires_at !== null && $this->expires_at->isPast()
            ? PlacementCreditStatus::Expired
            : PlacementCreditStatus::Active;
    }
}
