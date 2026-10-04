<?php

use App\Enums\AdvertisingPackage;
use App\Enums\ApplicationMode;
use App\Enums\CategoryType;
use App\Enums\CompanyStatus;
use App\Enums\VacancySource;
use App\Enums\VacancyStatus;
use App\Models\Category;
use App\Models\Company;
use App\Models\Order;
use App\Models\Payment;
use App\Models\User;
use App\Models\Vacancy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

function placementEmployer(array $attributes = []): User
{
    Role::firstOrCreate(['name' => 'employer', 'guard_name' => 'web']);

    $user = User::factory()->create($attributes);
    $user->assignRole('employer');

    return $user;
}

function placementCompany(User $user, array $attributes = []): Company
{
    return Company::factory()->for($user)->create([
        'status' => CompanyStatus::Pending,
        ...$attributes,
    ]);
}

function validPlacementData(Company $company, array $overrides = []): array
{
    return [
        'company_id' => $company->id,
        'title' => 'Senior accountmanager duurzame groei',
        'description' => 'In deze functie bouwt u langdurige klantrelaties op en werkt u samen met een ervaren commercieel team aan duurzame groei.',
        'location' => 'Utrecht',
        'application_mode' => ApplicationMode::Internal->value,
        'salary_min' => 4000,
        'salary_max' => 5200,
        'deadline_at' => now()->addMonth()->toDateString(),
        ...$overrides,
    ];
}

test('a guest sees the package step before authentication and maatwerk follows contact', function () {
    $this->get(route('vacancy-placement.index'))
        ->assertOk()
        ->assertSee('Standaard')
        ->assertSee('€ 189')
        ->assertSee('Superior')
        ->assertSee('€ 398')
        ->assertSee('Maatwerk')
        ->assertSee('noindex, nofollow');

    $this->post(route('vacancy-placement.package'), ['package' => AdvertisingPackage::Superior->value])
        ->assertRedirect(route('vacancy-placement.account'))
        ->assertSessionHas('vacancy_placement.package', AdvertisingPackage::Superior->value)
        ->assertSessionHas('url.intended', route('vacancy-placement.create'));

    expect(Vacancy::count())->toBe(0)
        ->and(Company::count())->toBe(0);

    $this->post(route('vacancy-placement.package'), ['package' => AdvertisingPackage::Custom->value])
        ->assertRedirect(route('contact', ['reason' => 'advertising']));
});

test('quick registration creates one employer and pending company then resumes the chosen package', function () {
    $this->post(route('vacancy-placement.package'), ['package' => AdvertisingPackage::Superior->value]);

    $this->post(route('register.store'), [
        'name' => 'Sanne Werkgever',
        'company_name' => 'Duurzame Sales BV',
        'email' => 'sanne@example.com',
        'password' => 'Sterk1234',
        'password_confirmation' => 'Sterk1234',
    ])->assertRedirect(route('vacancy-placement.create'));

    $user = User::where('email', 'sanne@example.com')->firstOrFail();
    $company = $user->companies()->sole();

    expect(auth()->id())->toBe($user->id)
        ->and($user->hasRole('employer'))->toBeTrue()
        ->and($user->role)->toBe('employer')
        ->and(Hash::check('Sterk1234', $user->password))->toBeTrue()
        ->and($company->status)->toBe(CompanyStatus::Pending);

    $this->get(route('vacancy-placement.create'))
        ->assertOk()
        ->assertSee('Superior')
        ->assertSee('Duurzame Sales BV');
});

test('normal account registration remains usable without placement state', function () {
    $this->post(route('register.store'), [
        'name' => 'Nieuwe Werkgever',
        'company_name' => 'Nieuw Bedrijf BV',
        'email' => 'nieuw@example.com',
        'password' => 'Veilig123',
        'password_confirmation' => 'Veilig123',
    ])->assertRedirect(route('account.index'));

    $this->assertAuthenticated();
    $this->assertDatabaseHas('companies', [
        'name' => 'Nieuw Bedrijf BV',
        'status' => CompanyStatus::Pending->value,
    ]);
});

test('an existing user logs in and returns to the selected package flow', function () {
    $user = placementEmployer(['email' => 'bestaand@example.com', 'password' => 'Wachtwoord123']);
    placementCompany($user);

    $this->post(route('vacancy-placement.package'), ['package' => AdvertisingPackage::Standard->value]);
    $this->post(route('login.store'), [
        'email' => 'bestaand@example.com',
        'password' => 'Wachtwoord123',
    ])->assertRedirect(route('vacancy-placement.create'));

    $this->assertAuthenticatedAs($user);
    $this->get(route('vacancy-placement.create'))
        ->assertOk()
        ->assertSee('Standaard');
});

test('an employer creates only a private protected draft for an owned company', function () {
    $user = placementEmployer();
    $company = placementCompany($user);
    $functionArea = Category::factory()->create(['type' => CategoryType::function_area, 'name' => 'Sales']);

    $response = $this->actingAs($user)
        ->withSession(['vacancy_placement.package' => AdvertisingPackage::Superior->value])
        ->post(route('vacancy-placement.store'), validPlacementData($company, [
            'function_area_category_id' => $functionArea->id,
            'status' => VacancyStatus::Active->value,
            'is_featured' => true,
            'published_at' => now()->toDateTimeString(),
            'placement_package' => AdvertisingPackage::Standard->value,
            'description' => '<script>alert("x")</script> Dit is een veilige vacaturebeschrijving met voldoende inhoud voor de validatieregel.',
        ]));

    $vacancy = Vacancy::sole();
    $response->assertRedirect(route('vacancy-placement.preview', $vacancy));

    expect($vacancy->status)->toBe(VacancyStatus::Draft)
        ->and($vacancy->source)->toBe(VacancySource::Manual)
        ->and($vacancy->placement_package)->toBe(AdvertisingPackage::Superior)
        ->and($vacancy->is_featured)->toBeFalse()
        ->and($vacancy->is_filled)->toBeFalse()
        ->and($vacancy->published_at)->toBeNull()
        ->and($vacancy->expires_at)->toBeNull()
        ->and($vacancy->description)->toContain('<p>Dit is een veilige vacaturebeschrijving')
        ->not->toContain('<script>', 'alert')
        ->and($vacancy->categories()->pluck('categories.id')->all())->toBe([$functionArea->id])
        ->and(Vacancy::publiclyVisible()->count())->toBe(0);

    $this->get(route('vacancies.show', $vacancy))->assertNotFound();
});

test('vacancy placement validates ownership enums destinations and taxonomy types', function () {
    $user = placementEmployer();
    $owned = placementCompany($user);
    $foreign = placementCompany(User::factory()->create());
    $wrongCategory = Category::factory()->create(['type' => CategoryType::sector]);

    $this->actingAs($user)
        ->withSession(['vacancy_placement.package' => AdvertisingPackage::Standard->value])
        ->post(route('vacancy-placement.store'), validPlacementData($foreign))
        ->assertSessionHasErrors('company_id');

    $this->actingAs($user)
        ->withSession(['vacancy_placement.package' => AdvertisingPackage::Standard->value])
        ->post(route('vacancy-placement.store'), validPlacementData($owned, [
            'application_mode' => ApplicationMode::Email->value,
            'application_email' => null,
            'function_area_category_id' => $wrongCategory->id,
        ]))
        ->assertSessionHasErrors(['application_email', 'function_area_category_id'])
        ->assertSessionHasInput('title', 'Senior accountmanager duurzame groei')
        ->assertSessionHasInput('location', 'Utrecht');

    $this->actingAs($user)
        ->withSession(['vacancy_placement.package' => AdvertisingPackage::Standard->value])
        ->post(route('vacancy-placement.store'), validPlacementData($owned, [
            'application_mode' => 'unsupported',
        ]))
        ->assertSessionHasErrors('application_mode');

    expect(Vacancy::count())->toBe(0);
});

test('only the owner may preview and edit a draft and editing returns to preview', function () {
    $owner = placementEmployer();
    $other = placementEmployer();
    $vacancy = Vacancy::factory()->create([
        'company_id' => placementCompany($owner)->id,
        'status' => VacancyStatus::Draft,
        'source' => VacancySource::Manual,
        'placement_package' => AdvertisingPackage::Standard,
        'application_mode' => ApplicationMode::Internal,
        'description' => 'Eerste regel &amp; veilig.<br />'.PHP_EOL.'Tweede regel met voldoende inhoud voor de publieke vacaturetekst.',
    ]);

    $this->actingAs($owner)
        ->get(route('vacancy-placement.preview', $vacancy))
        ->assertOk()
        ->assertSee('Privévoorbeeld')
        ->assertSee('noindex, nofollow');

    $this->actingAs($other)->get(route('vacancy-placement.preview', $vacancy))->assertForbidden();
    $this->actingAs($other)->get(route('vacancy-placement.edit', $vacancy))->assertForbidden();

    $this->actingAs($owner)
        ->get(route('vacancy-placement.edit', $vacancy))
        ->assertOk()
        ->assertSee('Eerste regel &amp;amp; veilig.', false)
        ->assertSee('Tweede regel met voldoende inhoud');

    $this->actingAs($owner)
        ->patch(route('vacancy-placement.update', $vacancy), validPlacementData($vacancy->company, [
            'title' => 'Bijgewerkte accountmanager vacature',
            'description' => "Eerste regel & veilig.\nTweede regel met voldoende inhoud voor de publieke vacaturetekst.",
        ]))
        ->assertRedirect(route('vacancy-placement.preview', $vacancy));

    expect($vacancy->refresh()->title)->toBe('Bijgewerkte accountmanager vacature')
        ->and($vacancy->description)->toContain('Eerste regel &amp; veilig.')
        ->not->toContain('&amp;amp;');
});

test('submission creates a pending moderation handoff without payment publication or featured entitlement', function () {
    $user = placementEmployer();
    $vacancy = Vacancy::factory()->create([
        'company_id' => placementCompany($user)->id,
        'status' => VacancyStatus::Draft,
        'source' => VacancySource::Manual,
        'placement_package' => AdvertisingPackage::Superior,
        'application_mode' => ApplicationMode::Internal,
        'is_featured' => false,
        'published_at' => null,
    ]);

    $this->actingAs($user)
        ->withSession(['vacancy_placement.package' => AdvertisingPackage::Superior->value])
        ->post(route('vacancy-placement.submit', $vacancy), [
            'status' => VacancyStatus::Active->value,
            'is_featured' => true,
        ])
        ->assertRedirect(route('vacancy-placement.success', $vacancy))
        ->assertSessionMissing('vacancy_placement.package');

    $vacancy->refresh();
    expect($vacancy->status)->toBe(VacancyStatus::Pending)
        ->and($vacancy->published_at)->toBeNull()
        ->and($vacancy->is_featured)->toBeFalse()
        ->and(Vacancy::publiclyVisible()->count())->toBe(0)
        ->and(Order::count())->toBe(0)
        ->and(Payment::count())->toBe(0);

    $this->get(route('vacancy-placement.success', $vacancy))
        ->assertOk()
        ->assertSee('Er is nog niets betaald')
        ->assertSee('niet gepubliceerd of uitgelicht');

    $this->get(route('vacancy-placement.edit', $vacancy))->assertForbidden();
});

test('an authenticated user without a company can create only an owned pending company', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->withSession(['vacancy_placement.package' => AdvertisingPackage::Standard->value])
        ->get(route('vacancy-placement.create'))
        ->assertRedirect(route('vacancy-placement.company.create'));

    $this->actingAs($user)
        ->post(route('vacancy-placement.company.store'), [
            'name' => 'Eigen Werkgever BV',
            'email' => 'werkgever@example.com',
            'location' => 'Rotterdam',
        ])->assertRedirect(route('vacancy-placement.create'));

    $company = Company::where('name', 'Eigen Werkgever BV')->firstOrFail();
    expect($company->user_id)->toBe($user->id)
        ->and($company->status)->toBe(CompanyStatus::Pending)
        ->and($user->refresh()->hasRole('employer'))->toBeTrue();
});

test('public auth and placement pages are noindex and excluded from the sitemap', function () {
    config(['app.env' => 'production']);

    foreach (['login', 'register', 'vacancy-placement.index'] as $routeName) {
        $this->get(route($routeName))
            ->assertOk()
            ->assertSee('<meta name="robots" content="noindex, nofollow">', false);
    }

    $sitemap = $this->get(route('sitemap'))->assertOk();
    $sitemap->assertDontSee('<loc>'.route('vacancy-placement.index').'</loc>', false)
        ->assertDontSee('<loc>'.route('login').'</loc>', false)
        ->assertDontSee('<loc>'.route('register').'</loc>', false);
});
