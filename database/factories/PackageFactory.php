<?php

namespace Database\Factories;

use App\Enums\PackageType;
use App\Models\Package;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Package>
 */
class PackageFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->words(2, true),
            'slug' => fake()->unique()->slug(),
            'description' => fake()->sentence(),
            'features' => [fake()->sentence()],
            'price_cents' => fake()->numberBetween(1_000, 100_000),
            'currency' => 'EUR',
            'vat_rate_basis_points' => 2100,
            'valid_from' => now()->subMonth()->toDateString(),
            'valid_until' => now()->addYear()->toDateString(),
            'package_type' => PackageType::Placement,
            'vacancy_duration_days' => 60,
            'placement_quantity' => 1,
            'is_quote_only' => false,
            'checkout_eligible' => true,
            'is_featured' => false,
            'commercial_priority' => 0,
            'cta_label' => 'Kies pakket',
            'operational_notes' => null,
            'includes_featured' => false,
            'includes_social_campaign' => false,
            'includes_newsletter' => false,
            'is_active' => true,
            'sort_order' => 0,
        ];
    }
}
