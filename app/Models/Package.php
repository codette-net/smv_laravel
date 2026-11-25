<?php

namespace App\Models;

use App\Enums\PackageType;
use Carbon\CarbonInterface;
use Database\Factories\PackageFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Package extends Model
{
    /** @use HasFactory<PackageFactory> */
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'package_type',
        'description',
        'features',
        'price_cents',
        'currency',
        'vat_rate_basis_points',
        'valid_from',
        'valid_until',
        'cta_label',
        'operational_notes',
        'vacancy_duration_days',
        'placement_quantity',
        'is_quote_only',
        'checkout_eligible',
        'is_featured',
        'includes_featured',
        'includes_social_campaign',
        'includes_newsletter',
        'is_active',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'price_cents' => 'integer',
            'vat_rate_basis_points' => 'integer',
            'vacancy_duration_days' => 'integer',
            'placement_quantity' => 'integer',
            'package_type' => PackageType::class,
            'features' => 'array',
            'valid_from' => 'date',
            'valid_until' => 'date',
            'is_quote_only' => 'boolean',
            'checkout_eligible' => 'boolean',
            'is_featured' => 'boolean',
            'includes_featured' => 'boolean',
            'includes_social_campaign' => 'boolean',
            'includes_newsletter' => 'boolean',
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    public function orderItems(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function hasApprovedPrice(?CarbonInterface $at = null): bool
    {
        $at ??= now();

        $baseRequirements = $this->is_active
            && $this->price_cents >= 0
            && $this->currency !== '';

        if (! $baseRequirements) {
            return false;
        }

        if ($this->valid_from !== null && $this->valid_from->copy()->startOfDay()->gt($at)) {
            return false;
        }

        if (! app()->environment('production')
            && config('commercial.allow_expired_prices_in_non_production')) {
            return true;
        }

        return $this->valid_until === null || $this->valid_until->copy()->endOfDay()->gte($at);
    }

    public function isAvailableForCheckout(?CarbonInterface $at = null): bool
    {
        return $this->hasApprovedPrice($at)
            && $this->checkout_eligible
            && ! $this->is_quote_only
            && $this->vat_rate_basis_points !== null
            && $this->vacancy_duration_days !== null
            && $this->vacancy_duration_days > 0
            && $this->placement_quantity > 0;
    }

    /** @return array<string, mixed> */
    public function presentation(): array
    {
        $hasApprovedPrice = $this->hasApprovedPrice();
        $currency = strtoupper($this->currency);
        $currencyLabel = $currency === 'EUR' ? '€' : $currency;

        return [
            'value' => $this->slug,
            'name' => $this->name,
            'price' => $hasApprovedPrice
                ? ($this->is_quote_only ? 'Vanaf ' : '').$currencyLabel.' '.number_format($this->price_cents / 100, 0, ',', '.')
                : 'Prijs op aanvraag',
            'suffix' => $hasApprovedPrice ? 'excl. btw' : null,
            'intro' => $this->description,
            'features' => $this->features ?? [],
            'featured' => $this->is_featured,
            'enters_flow' => $this->isAvailableForCheckout(),
            'cta_label' => $this->cta_label ?: ($this->is_quote_only ? 'Bespreek maatwerk' : 'Kies '.$this->name),
            'validity_label' => $hasApprovedPrice && $this->valid_until !== null
                ? 'Prijs geldig t/m '.$this->valid_until->format('d-m-Y')
                : null,
        ];
    }
}
