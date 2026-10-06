<?php

use App\Enums\CompanyStatus;
use App\Models\Company;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

function saveableCompany(array $attributes = []): Company
{
    return Company::factory()->create([
        'status' => CompanyStatus::Active,
        ...$attributes,
    ]);
}

function companySavingUser(string $role = 'candidate'): User
{
    Role::firstOrCreate(['name' => $role, 'guard_name' => 'web']);
    $user = User::factory()->create(['role' => $role]);
    $user->assignRole($role);

    return $user;
}

test('an authenticated user can save and unsave a public company idempotently', function () {
    $user = companySavingUser();
    $company = saveableCompany();

    $this->actingAs($user)->post(route('companies.save', $company))->assertRedirect();
    $this->post(route('companies.save', $company))->assertRedirect();

    expect($user->savedCompanies()->count())->toBe(1)
        ->and($company->savedByUsers()->sole()->is($user))->toBeTrue();

    $this->delete(route('companies.unsave', $company))->assertRedirect();
    expect($user->savedCompanies()->count())->toBe(0);
});

test('an authenticated company save can toggle asynchronously without a redirect', function () {
    $user = companySavingUser();
    $company = saveableCompany();

    $this->actingAs($user)
        ->postJson(route('companies.save', $company))
        ->assertOk()
        ->assertExactJson(['saved' => true]);

    expect($user->savedCompanies()->whereKey($company)->exists())->toBeTrue();

    $this->deleteJson(route('companies.unsave', $company))
        ->assertOk()
        ->assertExactJson(['saved' => false]);

    expect($user->savedCompanies()->whereKey($company)->exists())->toBeFalse();
});

test('all authenticated roles may save a company profile', function (string $role) {
    $user = companySavingUser($role);
    $company = saveableCompany();

    $this->actingAs($user)->post(route('companies.save', $company))->assertRedirect();
    expect($user->savedCompanies()->whereKey($company)->exists())->toBeTrue();
})->with(['candidate', 'employer', 'editor', 'admin']);

test('a non-public company cannot be newly saved or resolved for unrelated unsave', function () {
    $user = companySavingUser();
    $company = saveableCompany(['status' => CompanyStatus::Pending]);

    $this->actingAs($user)->post(route('companies.save', $company))->assertNotFound();
    $this->delete(route('companies.unsave', $company))->assertNotFound();
    expect($user->savedCompanies()->count())->toBe(0);
});

test('a guest company save resumes after login', function () {
    $company = saveableCompany();
    $user = User::factory()->create(['email' => 'bedrijf-volger@example.com', 'password' => 'Veilig123']);

    $this->post(route('companies.save', $company))
        ->assertRedirect(route('login'))
        ->assertSessionHas('saved_company.company_id', $company->id);

    $this->post(route('login.store'), [
        'email' => 'bedrijf-volger@example.com',
        'password' => 'Veilig123',
    ])->assertRedirect(route('bedrijven.show', $company));

    expect($user->savedCompanies()->whereKey($company)->exists())->toBeTrue();
});

test('a stale guest company intent is revalidated after authentication', function () {
    $company = saveableCompany();
    $user = User::factory()->create(['email' => 'later-bedrijf@example.com', 'password' => 'Veilig123']);
    $this->post(route('companies.save', $company));
    $company->update(['status' => CompanyStatus::Pending]);

    $this->post(route('login.store'), [
        'email' => 'later-bedrijf@example.com',
        'password' => 'Veilig123',
    ])->assertRedirect(route('account.saved-companies'))
        ->assertSessionHas('account_status', 'Dit bedrijf is niet meer beschikbaar en kon niet worden bewaard.');

    expect($user->savedCompanies()->count())->toBe(0);
});

test('company cards render an icon-only tooltip control with correct persisted state', function () {
    $user = companySavingUser();
    $company = saveableCompany(['name' => 'Bewaarde Werkgever']);
    $user->savedCompanies()->attach($company);

    $this->actingAs($user)->get(route('companies.index'))
        ->assertOk()
        ->assertSee('aria-label="Verwijder uit bewaarde bedrijven"', false)
        ->assertSee('fill="currentColor"', false)
        ->assertSee('x-on:submit.prevent="toggle"', false)
        ->assertSee('x-teleport="body"', false)
        ->assertDontSee('Bewaar vacature');
});

test('an unsaved company card renders an outlined bookmark and tooltip label', function () {
    $company = saveableCompany();

    $this->get(route('companies.index'))
        ->assertOk()
        ->assertSee('aria-label="Bewaar bedrijf"', false)
        ->assertSee('fill="none"', false);
});

test('a soft deleted saved company remains a private unavailable removable item', function () {
    $user = companySavingUser();
    $company = saveableCompany(['name' => 'Verborgen bedrijfsnaam']);
    $user->savedCompanies()->attach($company);
    $company->delete();

    $this->actingAs($user)->get(route('account.saved-companies'))
        ->assertOk()
        ->assertSee('Niet meer beschikbaar')
        ->assertDontSee('Verborgen bedrijfsnaam')
        ->assertSee('noindex, nofollow');

    $this->delete(route('companies.unsave', $company))->assertRedirect();
    expect(DB::table('saved_companies')->count())->toBe(0);
});

test('company card saved state is loaded in bulk', function () {
    $user = companySavingUser();
    Company::factory()->count(2)->create(['status' => CompanyStatus::Active]);

    DB::flushQueryLog();
    DB::enableQueryLog();
    $this->actingAs($user)->get(route('companies.index'))->assertOk();
    $smallListQueries = count(DB::getQueryLog());

    Company::factory()->count(8)->create(['status' => CompanyStatus::Active]);
    DB::flushQueryLog();
    $this->get(route('companies.index'))->assertOk();
    $largeListQueries = count(DB::getQueryLog());
    DB::disableQueryLog();

    expect($largeListQueries)->toBeLessThanOrEqual($smallListQueries + 1);
});
