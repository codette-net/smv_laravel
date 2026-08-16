<?php

use App\Models\Company;
use App\Models\User;
use Filament\Panel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

test('only administrative and editorial roles can access the Filament panel', function (string $role, bool $allowed) {
    Role::create(['name' => $role, 'guard_name' => 'web']);
    $user = User::factory()->create();
    $user->assignRole($role);

    expect($user->canAccessPanel(Panel::make()))->toBe($allowed);
})->with([
    'super-admin' => ['super-admin', true],
    'admin' => ['admin', true],
    'editor' => ['editor', true],
    'employer' => ['employer', false],
    'candidate' => ['candidate', false],
]);

test('editors can maintain core content but cannot administer users or delete records', function () {
    Role::create(['name' => 'editor', 'guard_name' => 'web']);
    $editor = User::factory()->create();
    $editor->assignRole('editor');

    expect(Gate::forUser($editor)->allows('viewAny', Company::class))->toBeTrue()
        ->and(Gate::forUser($editor)->allows('create', Company::class))->toBeTrue()
        ->and(Gate::forUser($editor)->allows('delete', new Company))->toBeFalse()
        ->and(Gate::forUser($editor)->allows('viewAny', User::class))->toBeFalse();
});
