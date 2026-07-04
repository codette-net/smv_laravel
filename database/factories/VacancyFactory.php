<?php

namespace Database\Factories;

use App\Models\Company;
use App\Models\Vacancy;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Vacancy>
 */
class VacancyFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $title = fake('nl_NL')->jobTitle();
        $slug = str($title)->slug();
        $rate_min = fake()->numberBetween(20, 80);
        $rate_max = $rate_min + fake()->numberBetween(10, 20);
        return [
            'company_id' => fake()->numberBetween(1, 6),
            'title' => $title,
            'slug' => $slug,
            'location' => fake()->randomElement(['Utrecht', 'Den Haag', 'Amsterdam', 'Eindhoven', 'Rotterdam']),
            'description' => fake()->paragraph(10),
            'application_email' => fake()->unique()->safeEmail(),
            'application_url' => fake()->url(),
            'salary_min' => fake()->numberBetween(1000, 10000),
            'salary_max' => fake()->numberBetween(2000, 20000),
            'rate_min' => $rate_min,
            'rate_max' => $rate_max,
            'reference' => fake()->uuid(),
            'source_reference' => fake()->uuid(),
            'deadline_at' => fake()->dateTimeBetween('now', '+1 month'),
            'expires_at' => fake()->dateTimeBetween('+1 month', '+2 months'),
            'is_featured' => fake()->boolean(25),
            'is_filled' => fake()->boolean(15),
            'status' => fake()->randomElement(['draft', 'published', 'pending']),
            'source' => 'manual',
        ];
    }
}
