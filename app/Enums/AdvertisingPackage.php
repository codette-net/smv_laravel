<?php

namespace App\Enums;

use App\Support\Commerce\PackageCatalogue;

enum AdvertisingPackage: string
{
    case Standard = 'standard';
    case Superior = 'superior';
    case Custom = 'custom';

    public function label(): string
    {
        return match ($this) {
            self::Standard => 'Standaard',
            self::Superior => 'Superior',
            self::Custom => 'Maatwerk',
        };
    }

    public function priceLabel(): string
    {
        return app(PackageCatalogue::class)->find($this)?->presentation()['price'] ?? 'Prijs op aanvraag';
    }

    public function priceCents(): ?int
    {
        return app(PackageCatalogue::class)->find($this)?->price_cents;
    }

    public function durationDays(): ?int
    {
        return app(PackageCatalogue::class)->find($this)?->vacancy_duration_days;
    }

    public function introduction(): string
    {
        return app(PackageCatalogue::class)->find($this)?->description ?? '';
    }

    /** @return array<int, string> */
    public function features(): array
    {
        return app(PackageCatalogue::class)->find($this)?->features ?? [];
    }

    public function entersPlacementFlow(): bool
    {
        return app(PackageCatalogue::class)->find($this)?->isAvailableForCheckout() ?? false;
    }

    public function isRecommended(): bool
    {
        return app(PackageCatalogue::class)->find($this)?->is_featured ?? false;
    }

    /** @return array<int, array<string, mixed>> */
    public static function presentation(): array
    {
        return app(PackageCatalogue::class)->presentation();
    }
}
