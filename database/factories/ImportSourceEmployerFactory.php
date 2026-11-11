<?php

namespace Database\Factories;

use App\Enums\ImportEmployerIdentityType;
use App\Models\Company;
use App\Models\ImportSource;
use App\Models\ImportSourceEmployer;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<ImportSourceEmployer> */
class ImportSourceEmployerFactory extends Factory
{
    public function definition(): array
    {
        return [
            'import_source_id' => ImportSource::factory(),
            'company_id' => Company::factory(),
            'identity_type' => ImportEmployerIdentityType::ProviderId,
            'external_employer_id' => fake()->unique()->uuid(),
            'source_name' => fake()->company(),
            'is_active' => true,
        ];
    }
}
