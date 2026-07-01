<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Illuminate\Database\Eloquent\Builder;
/**
 * Class User
 *
 * @package App\Models
 * @mixin Builder
 */

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $roles = [
            'super-admin',
            'admin',
            'editor',
            'employer',
            'candidate',
        ];

        foreach ($roles as $role) {
            Role::firstOrCreate([
                'name' => $role,
                'guard_name' => 'web',
            ]);
        }

        $admin = User::firstOrCreate(
            ['email' => 'josoj@jos.com'],
            [
                'name' => 'SMV Admin',
                'password' => bcrypt(config('app.dev_pw')),
            ]
        );

        $admin->assignRole('super-admin');
    }
}
