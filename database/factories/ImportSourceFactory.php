<?php

namespace Database\Factories;

use App\Enums\ImportFormat;
use App\Enums\ImportTransport;
use App\Models\Company;
use App\Models\ImportSource;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ImportSource>
 */
class ImportSourceFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'name' => fake()->company(),
            'slug' => fake()->unique()->slug(2),
            'transport' => ImportTransport::Http,
            'format' => ImportFormat::Xml,
            'endpoint_url' => 'https://'.fake()->domainName().'/feed.xml',
            'credentials' => ['token' => fake()->sha256()],
            'configuration' => ['timeout' => 15],
            'selection_rules' => [['path' => 'function', 'operator' => 'contains_any', 'values' => ['Sales']]],
            'is_active' => true,
        ];
    }
}
