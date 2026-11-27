<?php

use App\Actions\Users\SyncStaffRoles;
use App\Filament\Resources\Users\Pages\CreateUser;
use App\Filament\Resources\Users\Pages\EditUser;
use App\Models\Company;
use App\Models\User;
use Filament\Panel;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    collect(['candidate', 'employer', 'editor', 'admin', 'super-admin'])
        ->each(fn (string $role) => Role::create(['name' => $role, 'guard_name' => 'web']));
});

function roleConsistencyUser(string ...$roles): User
{
    $user = User::factory()->create();
    $user->syncRoles($roles);

    return $user;
}

test('legacy role text never grants staff authorization', function (): void {
    $legacyAdmin = User::factory()->create(['role' => 'super-admin']);

    expect($legacyAdmin->getRoleNames())->toBeEmpty()
        ->and($legacyAdmin->canAccessPanel(Panel::make()))->toBeFalse()
        ->and(Gate::forUser($legacyAdmin)->allows('viewAny', User::class))->toBeFalse();
});

test('account context follows Company ownership and candidate activity independently of roles', function (): void {
    $candidate = roleConsistencyUser('candidate');
    $employer = roleConsistencyUser('candidate');
    Company::factory()->for($employer)->create();

    expect($candidate->accountContextLabels())->toBe(['Kandidaat'])
        ->and($employer->accountContextLabels())->toBe(['Werkgever'])
        ->and($employer->hasRole('employer'))->toBeFalse();

    $employer->savedCompanies()->attach(Company::factory()->create());

    expect($employer->accountContextLabels())->toBe(['Kandidaat', 'Werkgever']);
});

test('staff synchronization preserves non-staff roles and supports multiple staff roles', function (): void {
    $superAdmin = roleConsistencyUser('super-admin');
    $target = roleConsistencyUser('candidate', 'employer', 'editor');

    app(SyncStaffRoles::class)->execute($superAdmin, $target, ['admin', 'editor']);

    expect($target->fresh()->getRoleNames()->sort()->values()->all())
        ->toBe(['admin', 'candidate', 'editor', 'employer']);
});

test('ordinary admins cannot escalate roles or modify protected super administrators', function (): void {
    $admin = roleConsistencyUser('admin');
    $target = roleConsistencyUser('candidate');
    $superAdmin = roleConsistencyUser('super-admin');

    expect(fn () => app(SyncStaffRoles::class)->execute($admin, $target, ['super-admin']))
        ->toThrow(ValidationException::class)
        ->and(fn () => app(SyncStaffRoles::class)->execute($admin, $superAdmin, ['admin']))
        ->toThrow(AuthorizationException::class)
        ->and(Gate::forUser($admin)->allows('update', $target))->toBeTrue()
        ->and(Gate::forUser($admin)->allows('update', $superAdmin))->toBeFalse()
        ->and(Gate::forUser($admin)->allows('delete', $admin))->toBeFalse();
});

test('unsupported role names are rejected instead of being created', function (): void {
    $superAdmin = roleConsistencyUser('super-admin');
    $target = roleConsistencyUser('candidate');

    expect(fn () => app(SyncStaffRoles::class)->execute($superAdmin, $target, ['owner']))
        ->toThrow(ValidationException::class)
        ->and(Role::query()->where('name', 'owner')->exists())->toBeFalse();
});

test('the Filament create form stores a compatibility classification and actual staff roles separately', function (): void {
    $admin = roleConsistencyUser('admin');
    $this->actingAs($admin);

    Livewire::test(CreateUser::class)
        ->fillForm([
            'name' => 'Nieuwe Redacteur',
            'email' => 'nieuwe-redacteur@example.test',
            'password' => 'veilig-wachtwoord',
            'staff_roles' => ['editor'],
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $created = User::query()->where('email', 'nieuwe-redacteur@example.test')->firstOrFail();

    expect($created->role)->toBe('candidate')
        ->and($created->getRoleNames()->all())->toBe(['editor'])
        ->and($created->canAccessPanel(Panel::make()))->toBeTrue();
});

test('the Filament edit form changes staff roles without changing legacy or non-staff roles', function (): void {
    $superAdmin = roleConsistencyUser('super-admin');
    $target = roleConsistencyUser('candidate', 'editor');
    $target->update(['role' => 'employer']);
    $this->actingAs($superAdmin);

    Livewire::test(EditUser::class, ['record' => $target->getRouteKey()])
        ->fillForm(['staff_roles' => ['admin', 'editor']])
        ->call('save')
        ->assertHasNoFormErrors();

    expect($target->fresh()->role)->toBe('employer')
        ->and($target->getRoleNames()->sort()->values()->all())
        ->toBe(['admin', 'candidate', 'editor']);
});

test('candidate employer and editor roles cannot administer users', function (string $role): void {
    $actor = roleConsistencyUser($role);
    $target = roleConsistencyUser('candidate');

    expect(Gate::forUser($actor)->allows('viewAny', User::class))->toBeFalse()
        ->and(Gate::forUser($actor)->allows('create', User::class))->toBeFalse()
        ->and(Gate::forUser($actor)->allows('update', $target))->toBeFalse();
})->with(['candidate', 'employer', 'editor']);
