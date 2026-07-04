<?php

namespace Database\Factories;

use App\Models\Company;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Alirezasedghi\LaravelImageFaker\ImageFaker;
use Alirezasedghi\LaravelImageFaker\Services\Picsum;


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
        $imageFaker = new ImageFaker(new Picsum());
        $logo = $imageFaker->image(storage_path('app/public/logos'));
        $cover = $imageFaker->image(storage_path('app/public/covers'));
        $name = fake('nl_NL')->company();
        $slug = str($name)->slug();
        return [
            'user_id' => User::query()->inRandomOrder()->first()->id,
            'name' => $name,
            'slug' => $slug,
            'tagline' => fake()->sentence(4),
            'description' => fake()->paragraph(10),
            'email' => fake()->unique()->safeEmail(),
            'phone' => fake()->phoneNumber(),
            'website'=> fake()->url(),
            'logo' => 'logos/' . basename($logo),
            'cover_image' => 'covers/' . basename($cover),
            'location' => fake()->randomElement(['Utrecht', 'Den Haag', 'Amsterdam', 'Eindhoven', 'Rotterdam']),
            'linkedin_url' => fake()->url(),
            'facebook_url' => fake()->url(),
            'instagram_url' => fake()->url(),
            'video_url' => fake()->url(),
            'status' => 'active',
        ];
    }
}
