<?php

use App\Enums\ApplicationMode;
use App\Enums\VacancyStatus;
use App\Filament\Resources\Vacancies\Pages\CreateVacancy;
use App\Filament\Resources\Vacancies\Pages\EditVacancy;
use App\Models\Company;
use App\Models\User;
use App\Models\Vacancy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
});

function vacancyApplicationAdmin(): User
{
    $user = User::factory()->create();
    $user->assignRole('admin');

    return $user;
}

function vacancyApplicationFormData(Company $company, ApplicationMode $mode = ApplicationMode::Internal): array
{
    return [
        'company_id' => $company->id,
        'title' => 'Accountmanager duurzame groei',
        'description' => 'In deze functie bouw je langdurige klantrelaties op en werk je samen met een ervaren commercieel team aan duurzame groei.',
        'location' => 'Utrecht',
        'status' => VacancyStatus::Draft->value,
        'application_mode' => $mode->value,
    ];
}

test('create form reveals only the destination for the selected application mode', function (): void {
    $this->actingAs(vacancyApplicationAdmin());

    Livewire::test(CreateVacancy::class)
        ->assertFormFieldHidden('application_email')
        ->assertFormFieldHidden('application_url')
        ->fillForm(['application_mode' => ApplicationMode::External->value])
        ->assertFormFieldVisible('application_url')
        ->assertFormFieldHidden('application_email')
        ->fillForm(['application_mode' => ApplicationMode::Email->value])
        ->assertFormFieldVisible('application_email')
        ->assertFormFieldHidden('application_url')
        ->fillForm(['application_mode' => ApplicationMode::Internal->value])
        ->assertFormFieldHidden('application_email')
        ->assertFormFieldHidden('application_url');
});

test('create form validates only the active destination and persists each mode', function (): void {
    $this->actingAs(vacancyApplicationAdmin());
    $company = Company::factory()->create();

    Livewire::test(CreateVacancy::class)
        ->fillForm([
            ...vacancyApplicationFormData($company, ApplicationMode::External),
            'application_url' => 'https://jobs.example.test/apply',
            'application_email' => 'not-an-email',
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $external = Vacancy::query()->sole();
    expect($external->application_mode)->toBe(ApplicationMode::External)
        ->and($external->application_url)->toBe('https://jobs.example.test/apply');

    Livewire::test(EditVacancy::class, ['record' => $external->getRouteKey()])
        ->fillForm([
            'application_mode' => ApplicationMode::Email->value,
            'application_email' => 'apply@example.test',
            'application_url' => 'javascript:alert(1)',
        ])
        ->call('save')
        ->assertHasNoFormErrors();

    $email = $external->fresh();
    expect($email->application_mode)->toBe(ApplicationMode::Email)
        ->and($email->application_email)->toBe('apply@example.test');

    Livewire::test(EditVacancy::class, ['record' => $email->getRouteKey()])
        ->fillForm([
            'application_mode' => ApplicationMode::Internal->value,
            'application_email' => 'also-not-an-email',
            'application_url' => 'javascript:alert(1)',
        ])
        ->call('save')
        ->assertHasNoFormErrors();

    expect($email->fresh()->application_mode)->toBe(ApplicationMode::Internal);
});

test('edit form hydrates enum-backed modes and reacts through transitions', function (): void {
    $this->actingAs(vacancyApplicationAdmin());
    $vacancy = Vacancy::factory()->create([
        'company_id' => Company::factory(),
        'application_mode' => ApplicationMode::External,
        'application_url' => 'https://jobs.example.test/apply',
        'application_email' => 'stale@example.test',
    ]);

    Livewire::test(EditVacancy::class, ['record' => $vacancy->getRouteKey()])
        ->assertFormFieldVisible('application_url')
        ->assertFormFieldHidden('application_email')
        ->fillForm(['application_mode' => ApplicationMode::Internal->value])
        ->assertFormFieldHidden('application_email')
        ->assertFormFieldHidden('application_url')
        ->call('save')
        ->assertHasNoFormErrors();

    expect($vacancy->fresh()->application_mode)->toBe(ApplicationMode::Internal);
});

test('active destination values are required and constrained to their intended format', function (): void {
    $this->actingAs(vacancyApplicationAdmin());
    $company = Company::factory()->create();

    Livewire::test(CreateVacancy::class)
        ->fillForm(vacancyApplicationFormData($company, ApplicationMode::External))
        ->call('create')
        ->assertHasFormErrors(['application_url']);

    Livewire::test(CreateVacancy::class)
        ->fillForm([
            ...vacancyApplicationFormData($company, ApplicationMode::External),
            'application_url' => 'javascript:alert(1)',
        ])
        ->call('create')
        ->assertHasFormErrors(['application_url']);

    Livewire::test(CreateVacancy::class)
        ->fillForm(vacancyApplicationFormData($company, ApplicationMode::Email))
        ->call('create')
        ->assertHasFormErrors(['application_email']);

    Livewire::test(CreateVacancy::class)
        ->fillForm([
            ...vacancyApplicationFormData($company, ApplicationMode::Email),
            'application_email' => 'not-an-email',
        ])
        ->call('create')
        ->assertHasFormErrors(['application_email']);
});
