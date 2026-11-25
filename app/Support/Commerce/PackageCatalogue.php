<?php

namespace App\Support\Commerce;

use App\Enums\AdvertisingPackage;
use App\Models\Package;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;

final class PackageCatalogue
{
    /** @return Collection<int, Package> */
    public function packages(): Collection
    {
        $persisted = Schema::hasTable('packages')
            ? Package::query()
                ->whereIn('slug', array_column(AdvertisingPackage::cases(), 'value'))
                ->get()
                ->keyBy('slug')
            : collect();

        return collect(AdvertisingPackage::cases())
            ->map(fn (AdvertisingPackage $package): Package => $persisted->get($package->value)
                ?? $this->defaultPackage($package))
            ->filter(fn (Package $package): bool => $package->is_active)
            ->sortBy('sort_order')
            ->values();
    }

    public function find(AdvertisingPackage|string $package): ?Package
    {
        $slug = $package instanceof AdvertisingPackage ? $package->value : $package;
        $identifier = AdvertisingPackage::tryFrom($slug);

        if ($identifier === null) {
            return Schema::hasTable('packages')
                ? Package::query()->where('slug', $slug)->first()
                : null;
        }

        return (Schema::hasTable('packages')
            ? Package::query()->where('slug', $slug)->first()
            : null) ?? $this->defaultPackage($identifier);
    }

    /** @return array<int, array<string, mixed>> */
    public function presentation(): array
    {
        return $this->packages()
            ->map(fn (Package $package): array => $package->presentation())
            ->all();
    }

    private function defaultPackage(AdvertisingPackage $package): Package
    {
        $attributes = config("commercial.packages.{$package->value}");

        return new Package([
            'slug' => $package->value,
            ...$attributes,
        ]);
    }
}
