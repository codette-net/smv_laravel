<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * Class User
 *
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

        foreach (['orders.cancel', 'orders.refund'] as $permissionName) {
            $permission = Permission::firstOrCreate([
                'name' => $permissionName,
                'guard_name' => 'web',
            ]);

            Role::query()
                ->whereIn('name', ['super-admin', 'admin', 'editor'])
                ->get()
                ->each(fn (Role $role) => $role->givePermissionTo($permission));
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
