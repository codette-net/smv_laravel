<?php

namespace Database\Factories;

use App\Models\ImportMapping;
use App\Models\ImportMappingField;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<ImportMappingField> */
class ImportMappingFieldFactory extends Factory
{
    public function definition(): array
    {
        return ['import_mapping_id' => ImportMapping::factory(), 'destination_key' => 'vacancy.title', 'operation' => 'direct', 'source_paths' => ['title'], 'position' => 0];
    }
}
