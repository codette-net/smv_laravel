<?php

use App\Enums\CompanyStatus;
use App\Enums\VacancyStatus;
use App\Models\Company;
use App\Models\User;
use App\Models\Vacancy;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

function accountEmployer(): User
{
    Role::firstOrCreate(['name' => 'employer', 'guard_name' => 'web']);
    $user = User::factory()->create();
    $user->assignRole('employer');

    return $user;
}

test('the public account is authenticated and shows only owned companies and vacancies', function () {
    $owner = accountEmployer();
    $other = accountEmployer();
    $ownedCompany = Company::factory()->for($owner)->create(['name' => 'Eigen Bedrijf']);
    $foreignCompany = Company::factory()->for($other)->create(['name' => 'Ander Bedrijf']);
    Vacancy::factory()->for($ownedCompany)->create(['title' => 'Eigen vacature', 'status' => VacancyStatus::Draft]);
    Vacancy::factory()->for($foreignCompany)->create(['title' => 'Verborgen vacature', 'status' => VacancyStatus::Draft]);

    $this->get(route('account.index'))->assertRedirect(route('login'));

    $this->actingAs($owner)->get(route('account.index'))
        ->assertOk()
        ->assertSee('Eigen Bedrijf')
        ->assertSee('Eigen vacature')
        ->assertDontSee('Ander Bedrijf')
        ->assertDontSee('Verborgen vacature')
        ->assertSee('noindex, nofollow');

    $this->get(route('account.vacancies'))
        ->assertOk()
        ->assertSee('Eigen vacature')
        ->assertDontSee('Verborgen vacature');
});

test('an owner can update safe company profile fields and media without changing protected state', function () {
    Storage::fake('public');
    $owner = accountEmployer();
    $company = Company::factory()->for($owner)->create([
        'name' => 'Oude naam',
        'status' => CompanyStatus::Pending,
        'is_featured' => false,
    ]);
    $originalSlug = $company->slug;

    $this->actingAs($owner)->patch(route('account.companies.update', $company), [
        'name' => 'Nieuwe naam',
        'tagline' => 'Commercieel groeien met een sterk team.',
        'description' => 'Een volledige beschrijving van het bedrijf.',
        'location' => 'Utrecht',
        'website' => 'https://example.com',
        'email' => 'contact@example.com',
        'phone' => '030-1234567',
        'linkedin_url' => 'https://linkedin.com/company/example',
        'status' => CompanyStatus::Active->value,
        'is_featured' => true,
        'user_id' => accountEmployer()->id,
        'slug' => 'gemanipuleerd',
        'logo' => UploadedFile::fake()->image('logo.png'),
        'cover' => UploadedFile::fake()->image('cover.jpg', 1200, 400),
    ])->assertRedirect(route('account.index'));

    $company->refresh();
    expect($company->name)->toBe('Nieuwe naam')
        ->and($company->slug)->toBe($originalSlug)
        ->and($company->user_id)->toBe($owner->id)
        ->and($company->status)->toBe(CompanyStatus::Pending)
        ->and($company->is_featured)->toBeFalse()
        ->and($company->getFirstMedia('logo'))->not->toBeNull()
        ->and($company->getFirstMedia('cover'))->not->toBeNull()
        ->and($company->hasCompletePublicProfile())->toBeTrue();
});

test('company and vacancy account actions enforce ownership and protected lifecycle state', function () {
    $owner = accountEmployer();
    $other = accountEmployer();
    $company = Company::factory()->for($owner)->create();
    $vacancy = Vacancy::factory()->for($company)->create([
        'status' => VacancyStatus::Draft,
        'is_featured' => false,
    ]);

    $this->actingAs($other)
        ->get(route('account.companies.edit', $company))
        ->assertForbidden();
    $this->patch(route('account.companies.update', $company), ['name' => 'Overname'])
        ->assertForbidden();
    $this->get(route('vacancy-placement.edit', $vacancy))->assertForbidden();
    $this->post(route('vacancy-placement.submit', $vacancy), [
        'status' => VacancyStatus::Active->value,
        'is_featured' => true,
    ])->assertForbidden();

    expect($company->fresh()->name)->not->toBe('Overname')
        ->and($vacancy->fresh()->status)->toBe(VacancyStatus::Draft)
        ->and($vacancy->is_featured)->toBeFalse();
});

test('rendered public logout posts a valid csrf token redirects home and ends authentication', function () {
    $user = accountEmployer();

    $this->withMiddleware(ValidateCsrfToken::class)
        ->actingAs($user)
        ->get(route('account.index'))
        ->assertOk()
        ->assertSee('method="POST" action="'.route('logout').'"', false);

    $token = session()->token();
    $this->post(route('logout'), ['_token' => $token])
        ->assertRedirect(route('home'));

    $this->assertGuest();
    $this->get(route('home'))->assertOk()->assertSee('Inloggen');
    $this->get('/dashboard')->assertRedirect('/dashboard/login');
    $this->get('/dashboard/login')->assertOk();
});

test('an authenticated employer cannot access the Filament panel', function () {
    $this->actingAs(accountEmployer())
        ->get('/dashboard')
        ->assertForbidden();
});
