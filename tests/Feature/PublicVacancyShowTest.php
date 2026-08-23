<?php

use App\Enums\ApplicationMode;
use App\Enums\CategoryType;
use App\Enums\CompanyStatus;
use App\Enums\VacancySource;
use App\Enums\VacancyStatus;
use App\Models\Category;
use App\Models\Company;
use App\Models\Vacancy;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

beforeEach(function () {
    Carbon::setTestNow('2026-08-18 12:00:00');
});

afterEach(function () {
    Carbon::setTestNow();
});

function publicDetailVacancy(array $attributes = []): Vacancy
{
    return Vacancy::factory()->create([
        'company_id' => Company::factory()->state(['status' => CompanyStatus::Active]),
        'status' => VacancyStatus::Active,
        'source' => VacancySource::Manual,
        'is_filled' => false,
        'published_at' => now()->subDay(),
        'deadline_at' => now()->addWeek(),
        'expires_at' => now()->addMonth(),
        'description' => '<p>Een inhoudelijke vacaturebeschrijving.</p>',
        ...$attributes,
    ]);
}

test('a publicly visible vacancy resolves by its canonical slug and renders its content', function () {
    $vacancy = publicDetailVacancy([
        'title' => 'Senior accountmanager',
        'location' => 'Utrecht',
        'application_url' => 'https://example.test/solliciteren',
        'application_mode' => ApplicationMode::External,
    ]);
    $vacancy->company->update(['name' => 'Commercieel Collectief', 'tagline' => 'Groeien met commercieel talent']);

    $this->get(route('vacancies.show', $vacancy))
        ->assertOk()
        ->assertSee('Senior accountmanager')
        ->assertSee('Commercieel Collectief')
        ->assertSee('Utrecht')
        ->assertSee('Een inhoudelijke vacaturebeschrijving.')
        ->assertSee(route('bedrijven.show', $vacancy->company), false)
        ->assertSee('https://example.test/solliciteren', false)
        ->assertSee('Solliciteren vóór:');
});

test('non-public vacancies and unknown slugs return 404', function (array $attributes) {
    $vacancy = publicDetailVacancy($attributes);

    $this->get(route('vacancies.show', $vacancy))->assertNotFound();
})->with([
    'draft' => [['status' => VacancyStatus::Draft]],
    'filled' => [['is_filled' => true]],
    'expired' => [['expires_at' => Carbon::parse('2026-08-17 12:00:00')]],
    'deadline passed' => [['deadline_at' => Carbon::parse('2026-08-17 12:00:00')]],
    'future publication' => [['published_at' => now()->addSecond()]],
]);

test('an unknown vacancy slug returns 404', function () {
    $this->get('/vacatures/onbekende-vacature')->assertNotFound();
});

test('the detail renders structured taxonomy tags and media-backed company branding', function () {
    config(['filesystems.disks.vacancy-branding-test' => [
        'driver' => 'local',
        'root' => storage_path('framework/testing/disks/vacancy-branding-test'),
    ]]);
    Storage::fake('vacancy-branding-test');
    $vacancy = publicDetailVacancy(['title' => 'Marketing specialist']);
    $vacancy->company->addMedia(UploadedFile::fake()->image('logo.png'))->toMediaCollection('logo', 'vacancy-branding-test');

    $categories = collect([
        Category::factory()->create(['name' => 'Fulltime', 'type' => CategoryType::employment_type]),
        Category::factory()->create(['name' => 'Hybride', 'type' => CategoryType::workplace]),
        Category::factory()->create(['name' => 'IT', 'type' => CategoryType::sector]),
        Category::factory()->create(['name' => 'Marketing', 'type' => CategoryType::function_area]),
        Category::factory()->create(['name' => 'Medior', 'type' => CategoryType::experience]),
    ]);
    $vacancy->categories()->attach($categories->pluck('id'));
    $vacancy->syncTags(['AI', 'Content']);

    $this->get(route('vacancies.show', $vacancy))
        ->assertOk()
        ->assertSee('Dienstverband')
        ->assertSee('Fulltime')
        ->assertSee('Werklocatie')
        ->assertSee('Hybride')
        ->assertSee('Sector')
        ->assertSee('Functiegebied')
        ->assertSee('Ervaring')
        ->assertSee('AI')
        ->assertSee('Content')
        ->assertSee('Logo van '.$vacancy->company->name);
});

test('null optional fields do not render a fake deadline and email is a supported application destination', function () {
    $vacancy = publicDetailVacancy([
        'deadline_at' => null,
        'application_url' => null,
        'application_email' => 'solliciteren@example.test',
        'application_mode' => ApplicationMode::Email,
        'salary_min' => null,
        'salary_max' => null,
        'rate_min' => null,
        'rate_max' => null,
    ]);

    $this->get(route('vacancies.show', $vacancy))
        ->assertOk()
        ->assertDontSee('Solliciteren vóór:')
        ->assertSee('mailto:solliciteren@example.test', false);
});

test('related vacancies include only other publicly visible vacancies at the same company', function () {
    $vacancy = publicDetailVacancy();
    $visible = Vacancy::factory()->create([
        'company_id' => $vacancy->company_id,
        'title' => 'Andere open vacature',
        'status' => VacancyStatus::Active,
        'is_filled' => false,
        'published_at' => now(),
        'deadline_at' => now()->addWeek(),
        'expires_at' => now()->addMonth(),
    ]);
    Vacancy::factory()->create(['company_id' => $vacancy->company_id, 'title' => 'Verborgen gerelateerde vacature', 'status' => VacancyStatus::Draft]);

    $this->get(route('vacancies.show', $vacancy))
        ->assertOk()
        ->assertSee('Gerelateerde vacatures')
        ->assertSee($visible->title)
        ->assertDontSee('Verborgen gerelateerde vacature');
});
