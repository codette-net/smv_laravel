<?php

use App\Enums\CategoryType;
use App\Enums\CompensationPeriod;
use App\Enums\SalaryBasis;
use App\Enums\VacancySource;
use App\Enums\VacancyStatus;
use App\Filament\Resources\Vacancies\Pages\CreateVacancy;
use App\Filament\Resources\Vacancies\Pages\EditVacancy;
use App\Filament\Resources\Vacancies\VacancyResource;
use App\Models\Category;
use App\Models\Company;
use App\Models\User;
use App\Models\Vacancy;
use Carbon\Carbon;
use Filament\Panel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function () {
    collect(['super-admin', 'admin', 'editor', 'employer', 'candidate'])
        ->each(fn (string $role) => Role::firstOrCreate(['name' => $role, 'guard_name' => 'web']));

    Carbon::setTestNow('2026-08-17 12:00:00');
});

afterEach(function () {
    Carbon::setTestNow();
});

function vacancyAdminUser(string $role): User
{
    $user = User::factory()->create();
    $user->assignRole($role);

    return $user;
}

test('an administrator can create a manual vacancy with lifecycle fields and categories', function () {
    $administrator = vacancyAdminUser('admin');
    $company = Company::factory()->create();
    $category = Category::create([
        'name' => 'Sales',
        'slug' => 'sales',
        'type' => CategoryType::function_area,
    ]);
    $qualification = Category::create([
        'name' => 'HBO',
        'slug' => 'hbo',
        'type' => CategoryType::qualification,
    ]);

    $this->actingAs($administrator);

    Livewire::test(CreateVacancy::class)
        ->fillForm([
            'company_id' => $company->id,
            'title' => 'Accountmanager',
            'description' => '<h2>De functie</h2><p onclick="bad()">Een commerciële functie met voldoende inhoud voor een duidelijke vacaturetekst.</p><script>secret()</script>',
            'status' => VacancyStatus::Active->value,
            'published_at' => now()->toDateTimeString(),
            'expires_at' => now()->addMonths(3)->toDateTimeString(),
            'application_email' => 'solliciteren@example.test',
            'application_url' => 'https://example.test/solliciteren',
            'is_featured' => true,
            'is_filled' => false,
            'function_area_categories' => [$category->id],
            'qualification_categories' => [$qualification->id],
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $vacancy = Vacancy::query()->sole();

    expect($vacancy->company->is($company))->toBeTrue()
        ->and($vacancy->status)->toBe(VacancyStatus::Active)
        ->and($vacancy->source)->toBe(VacancySource::Manual)
        ->and($vacancy->description)->toContain('<h2>De functie</h2>')
        ->and($vacancy->description)->not->toContain('onclick', '<script', 'secret()')
        ->and($vacancy->deadline_at?->equalTo(now()->addMonths(2)))->toBeTrue()
        ->and($vacancy->categories->contains($category))->toBeTrue()
        ->and($vacancy->categories->contains($qualification))->toBeTrue();
});

test('editing a vacancy does not overwrite its existing deadline', function () {
    $administrator = vacancyAdminUser('admin');
    $deadline = now()->addDays(10);
    $vacancy = Vacancy::factory()->create([
        'company_id' => Company::factory(),
        'deadline_at' => $deadline,
    ]);

    $this->actingAs($administrator);

    Livewire::test(EditVacancy::class, ['record' => $vacancy->getRouteKey()])
        ->fillForm(['title' => 'Aangepaste functietitel'])
        ->call('save')
        ->assertHasNoFormErrors();

    expect($vacancy->fresh()->deadline_at?->equalTo($deadline))->toBeTrue();
});

test('Filament persists explicit compensation metadata and rejects zero amounts', function () {
    $administrator = vacancyAdminUser('admin');
    $vacancy = Vacancy::factory()->create([
        'company_id' => Company::factory(),
        'salary_min' => null,
        'salary_max' => null,
        'salary_currency' => null,
        'salary_period' => null,
        'salary_basis' => null,
        'rate_min' => null,
        'rate_max' => null,
        'rate_currency' => null,
        'rate_period' => null,
    ]);

    $this->actingAs($administrator);

    Livewire::test(EditVacancy::class, ['record' => $vacancy->getRouteKey()])
        ->fillForm([
            'salary_min' => 3500,
            'salary_max' => 4500,
            'salary_currency' => 'eur',
            'salary_period' => CompensationPeriod::Month->value,
            'salary_basis' => SalaryBasis::GrossFullTimeEquivalent->value,
            'rate_min' => 95,
            'rate_max' => 125,
            'rate_currency' => 'eur',
            'rate_period' => CompensationPeriod::Hour->value,
        ])
        ->call('save')
        ->assertHasNoFormErrors();

    $vacancy->refresh();
    expect($vacancy->salary_currency)->toBe('EUR')
        ->and($vacancy->salary_period)->toBe(CompensationPeriod::Month)
        ->and($vacancy->salary_basis)->toBe(SalaryBasis::GrossFullTimeEquivalent)
        ->and($vacancy->rate_currency)->toBe('EUR')
        ->and($vacancy->rate_period)->toBe(CompensationPeriod::Hour);

    Livewire::test(EditVacancy::class, ['record' => $vacancy->getRouteKey()])
        ->fillForm(['salary_min' => 0])
        ->call('save')
        ->assertHasFormErrors(['salary_min']);
});

test('admin publication without a date publishes now while later edits preserve the date', function () {
    $administrator = vacancyAdminUser('admin');
    $vacancy = Vacancy::factory()->create([
        'company_id' => Company::factory(),
        'status' => VacancyStatus::Pending,
        'published_at' => null,
        'is_filled' => false,
        'deadline_at' => now()->addMonth(),
        'expires_at' => now()->addMonths(2),
    ]);

    $this->actingAs($administrator);

    Livewire::test(EditVacancy::class, ['record' => $vacancy->getRouteKey()])
        ->fillForm([
            'status' => VacancyStatus::Active->value,
            'published_at' => null,
        ])
        ->call('save')
        ->assertHasNoFormErrors();

    $publishedAt = $vacancy->fresh()->published_at;
    expect($publishedAt?->equalTo(now()))->toBeTrue();

    Livewire::test(EditVacancy::class, ['record' => $vacancy->getRouteKey()])
        ->fillForm(['title' => 'Titel na publicatie'])
        ->call('save')
        ->assertHasNoFormErrors();

    expect($vacancy->fresh()->published_at?->equalTo($publishedAt))->toBeTrue();
});

test('admin publication preserves an explicitly selected future schedule', function () {
    $administrator = vacancyAdminUser('admin');
    $future = now()->addDay();
    $vacancy = Vacancy::factory()->create([
        'company_id' => Company::factory(),
        'status' => VacancyStatus::Pending,
        'published_at' => null,
    ]);

    $this->actingAs($administrator);

    Livewire::test(EditVacancy::class, ['record' => $vacancy->getRouteKey()])
        ->fillForm([
            'status' => VacancyStatus::Active->value,
            'published_at' => $future->toDateTimeString(),
        ])
        ->call('save')
        ->assertHasNoFormErrors();

    expect($vacancy->fresh()->published_at?->equalTo($future))->toBeTrue()
        ->and(Vacancy::publiclyVisible()->whereKey($vacancy)->exists())->toBeFalse();
});

test('the vacancy resource respects the established administrative policy', function () {
    $vacancy = Vacancy::factory()->create(['company_id' => Company::factory()]);
    $editor = vacancyAdminUser('editor');
    $employer = vacancyAdminUser('employer');
    $candidate = vacancyAdminUser('candidate');
    $administrator = vacancyAdminUser('admin');

    $this->actingAs($editor);
    expect(VacancyResource::canViewAny())->toBeTrue()
        ->and(VacancyResource::canCreate())->toBeTrue()
        ->and(VacancyResource::canDelete($vacancy))->toBeFalse()
        ->and(VacancyResource::canRestore($vacancy))->toBeFalse();

    $this->actingAs($employer);
    expect(VacancyResource::canViewAny())->toBeFalse();

    $this->actingAs($candidate);
    expect(VacancyResource::canViewAny())->toBeFalse();

    $this->actingAs($administrator);
    expect(VacancyResource::canViewAny())->toBeTrue()
        ->and(VacancyResource::canDelete($vacancy))->toBeTrue()
        ->and(VacancyResource::canRestore($vacancy))->toBeTrue();

    $this->get(VacancyResource::getUrl())
        ->assertSuccessful();
});

test('editor panel access remains available while employer and candidate access is denied', function (string $role, bool $allowed) {
    $user = vacancyAdminUser($role);

    expect($user->canAccessPanel(Panel::make()))->toBe($allowed);
})->with([
    'editor' => ['editor', true],
    'employer' => ['employer', false],
    'candidate' => ['candidate', false],
]);
