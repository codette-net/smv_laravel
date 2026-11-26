<?php

namespace App\Models;

use App\Enums\AdvertisingPackage;
use App\Enums\PackageType;
use Database\Factories\OrderItemFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
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
        'package_type_snapshot',
        'placement_quantity_snapshot',
        'vacancy_duration_days_snapshot',
        'commercial_priority_snapshot',
        'entitlement_snapshot',
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
            'placement_quantity_snapshot' => 'integer',
            'vacancy_duration_days_snapshot' => 'integer',
            'commercial_priority_snapshot' => 'integer',
            'entitlement_snapshot' => 'array',
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

    public function placementCredits(): HasMany
    {
        return $this->hasMany(PlacementCredit::class);
    }

    public function isPlacementProduct(): bool
    {
        return in_array($this->product_code, [
            AdvertisingPackage::Standard->value,
            AdvertisingPackage::Superior->value,
        ], true);
    }

    public function hasCompletePlacementEntitlementSnapshot(): bool
    {
        return $this->package_type_snapshot === PackageType::Placement->value
            && $this->quantity > 0
            && $this->placement_quantity_snapshot !== null
            && $this->placement_quantity_snapshot > 0
            && $this->vacancy_duration_days_snapshot !== null
            && $this->vacancy_duration_days_snapshot > 0
            && $this->commercial_priority_snapshot !== null;
    }
}
