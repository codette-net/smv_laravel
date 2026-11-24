<?php

namespace Database\Factories;

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
            'price_cents' => fake()->numberBetween(1_000, 100_000),
            'currency' => 'EUR',
            'vacancy_duration_days' => 60,
            'includes_featured' => false,
            'includes_social_campaign' => false,
            'includes_newsletter' => false,
            'is_active' => true,
            'sort_order' => 0,
        ];
    }
}
