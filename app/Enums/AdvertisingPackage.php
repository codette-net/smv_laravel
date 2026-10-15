<?php

namespace App\Enums;

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
        return match ($this) {
            self::Standard => '€ 189',
            self::Superior => '€ 398',
            self::Custom => 'Op aanvraag',
        };
    }

    public function priceCents(): ?int
    {
        return match ($this) {
            self::Standard => 18_900,
            self::Superior => 39_800,
            self::Custom => null,
        };
    }

    public function durationDays(): ?int
    {
        return $this === self::Custom ? null : 60;
    }

    public function introduction(): string
    {
        return match ($this) {
            self::Standard => 'Een gerichte plaatsing voor één sales- of marketingvacature.',
            self::Superior => 'Voor vacatures die in overleg aanvullende zichtbaarheid krijgen.',
            self::Custom => 'Voor meerdere vacatures, terugkerende werving of aanvullende campagneondersteuning.',
        };
    }

    /** @return array<int, string> */
    public function features(): array
    {
        return match ($this) {
            self::Standard => ['60 dagen zichtbaar', 'Reguliere positionering op SMV', 'Afstemming over aangeleverd materiaal'],
            self::Superior => ['60 dagen zichtbaar', 'Extra positionering en aandacht in overleg', 'Mogelijke nieuwsbrief- en socialinzet afgestemd op de functie'],
            self::Custom => ['Aanpak afgestemd op uw wervingsvraag', 'Ruimte voor meerdere plaatsingen', 'Aanvullende zichtbaarheid en jobmarketing bespreekbaar'],
        };
    }

    public function entersPlacementFlow(): bool
    {
        return $this !== self::Custom;
    }

    public function isRecommended(): bool
    {
        return $this === self::Superior;
    }

    /** @return array<int, array<string, mixed>> */
    public static function presentation(): array
    {
        return array_map(fn (self $package): array => [
            'value' => $package->value,
            'name' => $package->label(),
            'price' => $package->priceLabel(),
            'suffix' => $package->priceCents() === null ? null : 'excl. btw',
            'intro' => $package->introduction(),
            'features' => $package->features(),
            'featured' => $package->isRecommended(),
            'enters_flow' => $package->entersPlacementFlow(),
        ], self::cases());
    }
}
