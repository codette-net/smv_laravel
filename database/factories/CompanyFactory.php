<?php

namespace Database\Factories;

use App\Models\Company;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Company>
 */
class CompanyFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => fake()->numberBetween(1, 10),
            'name' => fake()->company(),
            'slug' => fake()->slug(),
            'tagline' => fake()->sentence(4),
            'description' => fake()->paragraph(10),
            'email' => fake()->unique()->safeEmail(),
            'phone' => fake()->phoneNumber(),
            'website'=> fake()->url(),
            'logo' => fake()->imageUrl(),
            'cover_image' => fake()->imageUrl(),
            'location' => fake()->randomElement(['Utrecht', 'Den Haag', 'Amsterdam', 'Eindhoven', 'Rotterdam']),
            'linkedin_url' => fake()->url(),
            'facebook_url' => fake()->url(),
            'instagram_url' => fake()->url(),
            'video_url' => fake()->url(),
            'status' => 'active'
        ];
    }
}
