<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Models\User;
use App\Models\Vacancy;

class DemoSeeder extends DatabaseSeeder {

    public function run(): void
    {
        User::factory(10)->create();

        Company::factory(6)->create();

        Vacancy::factory(30)->create();
    }
}
