<?php

use App\Filament\Resources\Companies\CompanyResource;
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

test('company resource follows the Company policy for administrative roles', function () {
    collect(['super-admin', 'admin', 'editor', 'employer', 'candidate'])
        ->each(fn (string $role) => Role::firstOrCreate(['name' => $role, 'guard_name' => 'web']));

    $editor = User::factory()->create();
    $editor->assignRole('editor');
    $employer = User::factory()->create();
    $employer->assignRole('employer');
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $this->actingAs($editor);
    expect(CompanyResource::canViewAny())->toBeTrue()
        ->and(CompanyResource::canCreate())->toBeTrue();

    $this->actingAs($employer);
    expect(CompanyResource::canViewAny())->toBeFalse()
        ->and(CompanyResource::canCreate())->toBeFalse();

    $this->actingAs($admin);
    expect(CompanyResource::canDelete(Company::factory()->create()))->toBeTrue();

    $this->get(CompanyResource::getUrl())
        ->assertSuccessful();
});
