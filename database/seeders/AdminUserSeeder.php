<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $user = User::query()->updateOrCreate(
            [
                'email' => env('ADMIN_EMAIL', 'sojos@jos.com'),
            ],
            [
                'name' => 'SMV Super Admin',
                'password' => Hash::make(
                    env('ADMIN_PASSWORD', 'sojos')
                ),
                'email_verified_at' => now(),
            ],
        );

        $user->syncRoles(['super-admin']);
    }
}
