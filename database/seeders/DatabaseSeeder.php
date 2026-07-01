<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Company;

use App\Models\Vacancy;
use Database\Factories\CompanyFactory;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            RoleSeeder::class,
            CategorySeeder::class,
            DemoSeeder::class,
        ]);

    }
}
