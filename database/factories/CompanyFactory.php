<?php

namespace Database\Factories;

use App\Enums\CompanyStatus;
use App\Models\Company;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

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
        $name = fake('nl_NL')->company();

        return [
            'user_id' => User::factory(),
            'name' => $name,
            'slug' => Str::slug($name).'-'.fake()->unique()->numerify('####'),
            'tagline' => fake('nl_NL')->sentence(4),
            'description' => fake('nl_NL')->paragraphs(3, true),
            'email' => fake('nl_NL')->unique()->companyEmail(),
            'phone' => fake('nl_NL')->phoneNumber(),
            'website' => fake()->url(),
            'location' => fake()->randomElement(['Utrecht', 'Den Haag', 'Amsterdam', 'Eindhoven', 'Rotterdam']),
            'linkedin_url' => fake()->url(),
            'facebook_url' => fake()->url(),
            'instagram_url' => fake()->url(),
            'video_url' => fake()->url(),
            'status' => CompanyStatus::Active,
            'is_featured' => fake()->boolean(20),
        ];
    }
}
