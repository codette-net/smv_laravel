<?php

namespace Database\Seeders;

use App\Models\Package;
use Illuminate\Database\Seeder;

class PackageSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        foreach (config('commercial.packages') as $slug => $attributes) {
            Package::query()->firstOrCreate(
                ['slug' => $slug],
                $attributes,
            );
        }
    }
}
