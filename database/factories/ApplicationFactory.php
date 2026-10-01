<?php

namespace Database\Factories;

use App\Enums\ApplicationStatus;
use App\Models\Application;
use App\Models\Vacancy;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Application>
 */
class ApplicationFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'vacancy_id' => Vacancy::factory(),
            'candidate_name' => fake('nl_NL')->name(),
            'candidate_email' => fake()->safeEmail(),
            'candidate_phone' => fake('nl_NL')->phoneNumber(),
            'candidate_location' => fake()->city(),
            'linkedin_url' => fake()->url(),
            'motivation' => fake('nl_NL')->paragraph(),
            'status' => ApplicationStatus::New,
        ];
    }
}
