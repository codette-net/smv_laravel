<?php

use App\Enums\AdvertisingPackage;
use App\Enums\ApplicationMode;
use App\Enums\CompanyStatus;
use App\Enums\VacancySource;
use App\Enums\VacancyStatus;
use App\Models\Application;
use App\Models\Company;
use App\Models\User;
use App\Models\Vacancy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

test('generic registration offers both journeys without silently selecting employer', function () {
    $this->withSession(['vacancy_placement.package' => AdvertisingPackage::Standard->value])
        ->get(route('register'))
        ->assertOk()
        ->assertSee('Waarvoor wil je SMV gebruiken?')
        ->assertSee('Werkzoekende')
        ->assertSee('Werkgever')
        ->assertSee('href="'.route('register.job-seeker').'"', false)
        ->assertSee('href="'.route('register.employer').'"', false)
        ->assertDontSee('Bedrijfsnaam');

    $this->get(route('register', ['context' => 'administrator']))
        ->assertOk()
        ->assertSee('Waarvoor wil je SMV gebruiken?')
        ->assertDontSee('Bedrijfsnaam');

    $this->get(route('register', ['context' => 'employer']))
        ->assertOk()
        ->assertSee('Waarvoor wil je SMV gebruiken?')
        ->assertDontSee('Bedrijfsnaam');
});

test('registration forms use explicit validated context and shared login', function () {
    $this->get(route('register.job-seeker'))
        ->assertOk()
        ->assertSee('Account aanmaken als werkzoekende')
        ->assertSee('name="context" type="hidden" value="job_seeker"', false)
        ->assertDontSee('Bedrijfsnaam');

    $this->get(route('register.employer'))
        ->assertOk()
        ->assertSee('Account aanmaken als werkgever')
        ->assertSee('name="context" type="hidden" value="employer"', false)
        ->assertSee('Bedrijfsnaam');

    $this->get(route('login'))
        ->assertOk()
        ->assertSee('Inloggen')
        ->assertDontSee('Werkzoekende login')
        ->assertDontSee('Werkgever login');

    $this->post(route('register.store'), [
        'context' => 'administrator',
        'name' => 'Onveilige invoer',
        'company_name' => 'Niet opslaan BV',
        'email' => 'onveilig@example.com',
        'password' => 'Veilig123',
        'password_confirmation' => 'Veilig123',
    ])->assertRedirect(route('register'));

    expect(User::where('email', 'onveilig@example.com')->exists())->toBeFalse()
        ->and(Company::where('name', 'Niet opslaan BV')->exists())->toBeFalse();
});

test('direct job seeker registration creates one candidate identity without a company', function () {
    $this->post(route('register.store'), [
        'context' => 'job_seeker',
        'name' => 'Nieuwe Werkzoekende',
        'company_name' => 'Genegeerd Bedrijf',
        'email' => 'werkzoekende@example.com',
        'password' => 'Veilig123',
        'password_confirmation' => 'Veilig123',
    ])->assertRedirect(route('account.index'));

    $user = User::where('email', 'werkzoekende@example.com')->firstOrFail();

    expect($user->role)->toBe('candidate')
        ->and($user->hasRole('candidate'))->toBeTrue()
        ->and($user->companies()->count())->toBe(0)
        ->and(User::count())->toBe(1);
});

test('direct employer registration creates only an owned pending company and no staff access', function () {
    $existingCompany = Company::factory()->create(['status' => CompanyStatus::Active]);

    $this->post(route('register.store'), [
        'context' => 'employer',
        'name' => 'Nieuwe Werkgever',
        'company_name' => 'Eigen Bedrijf BV',
        'email' => 'werkgever@example.com',
        'password' => 'Veilig123',
        'password_confirmation' => 'Veilig123',
    ])->assertRedirect(route('account.index'));

    $user = User::where('email', 'werkgever@example.com')->firstOrFail();
    $ownedCompany = $user->companies()->sole();

    expect($user->role)->toBe('employer')
        ->and($user->hasRole('employer'))->toBeTrue()
        ->and($ownedCompany->name)->toBe('Eigen Bedrijf BV')
        ->and($ownedCompany->status)->toBe(CompanyStatus::Pending)
        ->and($user->can('update', $existingCompany))->toBeFalse()
        ->and($user->canAccessPanel(filament()->getPanel('dashboard')))->toBeFalse();
});

test('placement registration stays explicit and resumes the selected employer journey', function () {
    $this->post(route('vacancy-placement.package'), ['package' => AdvertisingPackage::Superior->value]);

    $this->get(route('vacancy-placement.account'))
        ->assertOk()
        ->assertSee('href="'.route('register.employer').'"', false)
        ->assertSee('Werkgeversaccount aanmaken');

    $this->get(route('login'))
        ->assertOk()
        ->assertSee('href="'.route('register.employer').'"', false);

    $this->post(route('register.store'), [
        'context' => 'employer',
        'name' => 'Plaatsende Werkgever',
        'company_name' => 'Plaatsend Bedrijf BV',
        'email' => 'plaatsen@example.com',
        'password' => 'Veilig123',
        'password_confirmation' => 'Veilig123',
    ])->assertRedirect(route('vacancy-placement.create'));

    expect(User::where('email', 'plaatsen@example.com')->firstOrFail()->companies()->count())->toBe(1);
});

test('an existing job seeker completes employer onboarding with the same identity', function () {
    Role::firstOrCreate(['name' => 'candidate', 'guard_name' => 'web']);
    $user = User::factory()->create(['role' => 'candidate']);
    $user->assignRole('candidate');

    $this->actingAs($user)
        ->post(route('vacancy-placement.package'), ['package' => AdvertisingPackage::Standard->value])
        ->assertRedirect(route('vacancy-placement.create'));

    $this->get(route('vacancy-placement.create'))
        ->assertRedirect(route('vacancy-placement.company.create'));

    $this->post(route('vacancy-placement.company.store'), [
        'name' => 'Later Werkgever BV',
        'email' => 'bedrijf@example.com',
    ])->assertRedirect(route('vacancy-placement.create'));

    $this->get(route('vacancy-placement.create'))
        ->assertOk()
        ->assertSee('Later Werkgever BV');

    expect(User::count())->toBe(1)
        ->and(auth()->id())->toBe($user->id)
        ->and($user->refresh()->hasRole('employer'))->toBeTrue()
        ->and($user->companies()->sole()->name)->toBe('Later Werkgever BV');
});

test('an employer can use saved content and own internal application features', function () {
    Role::firstOrCreate(['name' => 'employer', 'guard_name' => 'web']);
    $employer = User::factory()->create(['role' => 'employer']);
    $employer->assignRole('employer');
    $publicCompany = Company::factory()->create(['status' => CompanyStatus::Active]);
    $vacancy = Vacancy::factory()->for($publicCompany)->create([
        'status' => VacancyStatus::Active,
        'source' => VacancySource::Manual,
        'application_mode' => ApplicationMode::Internal,
        'is_filled' => false,
        'published_at' => now()->subDay(),
        'deadline_at' => now()->addWeek(),
        'expires_at' => now()->addMonth(),
    ]);

    $this->actingAs($employer)->post(route('vacancies.save', $vacancy))->assertRedirect();
    $this->post(route('companies.save', $publicCompany))->assertRedirect();
    $this->post(route('applications.store', $vacancy), [
        'candidate_name' => 'Werkgever als sollicitant',
        'candidate_email' => $employer->email,
        'motivation' => 'Ik reageer persoonlijk op deze interessante vacature met een passende motivatie.',
    ])->assertRedirect(route('applications.success', $vacancy));

    $application = Application::sole();

    expect($employer->savedVacancies()->whereKey($vacancy)->exists())->toBeTrue()
        ->and($employer->savedCompanies()->whereKey($publicCompany)->exists())->toBeTrue()
        ->and($application->candidate_id)->toBe($employer->id);

    $this->get(route('account.applications'))
        ->assertOk()
        ->assertSee($vacancy->title);
});
