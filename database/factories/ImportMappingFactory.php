<?php

namespace Database\Factories;

use App\Models\ImportMapping;
use App\Models\ImportSource;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<ImportMapping> */
class ImportMappingFactory extends Factory
{
    public function definition(): array
    {
        return ['import_source_id' => ImportSource::factory(), 'name' => 'Standaard mapping', 'is_active' => true, 'is_default' => true];
    }
}
