<?php

use App\Enums\CompanyStatus;
use App\Enums\VacancySource;
use App\Enums\VacancyStatus;
use App\Models\Company;
use App\Models\Vacancy;
use App\Support\Vacancies\VacancyFilterOptions;
use App\Support\Vacancies\VacancySearch;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    Carbon::setTestNow('2026-08-17 12:00:00');
});

afterEach(function () {
    Carbon::setTestNow();
});

function lifecycleVacancy(array $attributes = []): Vacancy
{
    return Vacancy::factory()->create([
        'company_id' => Company::factory(),
        'status' => VacancyStatus::Active,
        'source' => VacancySource::Manual,
        'is_filled' => false,
        'published_at' => now(),
        'deadline_at' => now()->addWeek(),
        'expires_at' => now()->addMonth(),
        ...$attributes,
    ]);
}

test('the public vacancy scope applies the canonical lifecycle rule', function () {
    $visible = lifecycleVacancy(['title' => 'Zichtbare vacature']);
    $withoutDeadline = lifecycleVacancy(['title' => 'Vacature zonder deadline', 'deadline_at' => null]);
    $withoutExpiry = lifecycleVacancy(['title' => 'Vacature zonder afloop', 'expires_at' => null]);
    $draft = lifecycleVacancy(['title' => 'Concept vacature', 'status' => VacancyStatus::Draft]);
    $filled = lifecycleVacancy(['title' => 'Ingevulde vacature', 'is_filled' => true]);
    $pastDeadline = lifecycleVacancy(['title' => 'Deadline verstreken', 'deadline_at' => now()->subSecond()]);
    $expired = lifecycleVacancy(['title' => 'Verlopen vacature', 'expires_at' => now()->subSecond()]);
    $scheduled = lifecycleVacancy(['title' => 'Geplande vacature', 'published_at' => now()->addSecond()]);

    expect(Vacancy::publiclyVisible()->pluck('id')->all())
        ->toContain($visible->id, $withoutDeadline->id, $withoutExpiry->id)
        ->not->toContain($draft->id, $filled->id, $pastDeadline->id, $expired->id, $scheduled->id);
});

test('a null publication timestamp keeps existing published vacancies immediately public', function () {
    $vacancy = lifecycleVacancy([
        'title' => 'Bestaande gepubliceerde vacature',
    ]);
    Vacancy::query()->whereKey($vacancy)->update(['published_at' => null]);
    $vacancy->refresh();

    expect($vacancy->published_at)->toBeNull()
        ->and(Vacancy::publiclyVisible()->pluck('id')->all())->toContain($vacancy->id);
});

test('transitioning a vacancy to published without a timestamp publishes it now', function () {
    $vacancy = lifecycleVacancy([
        'status' => VacancyStatus::Pending,
        'published_at' => null,
    ]);

    $vacancy->update(['status' => VacancyStatus::Active]);

    expect($vacancy->fresh()->published_at?->equalTo(now()))->toBeTrue()
        ->and(Vacancy::publiclyVisible()->whereKey($vacancy)->exists())->toBeTrue();
});

test('explicit publication dates remain deterministic before and after their scheduled time', function () {
    $past = lifecycleVacancy(['published_at' => now()->subSecond()]);
    $current = lifecycleVacancy(['published_at' => now()]);
    $scheduled = lifecycleVacancy(['published_at' => now()->addHour()]);

    expect(Vacancy::publiclyVisible()->whereKey($past)->exists())->toBeTrue()
        ->and(Vacancy::publiclyVisible()->whereKey($current)->exists())->toBeTrue()
        ->and(Vacancy::publiclyVisible()->whereKey($scheduled)->exists())->toBeFalse();

    Carbon::setTestNow(now()->addHours(2));

    expect(Vacancy::publiclyVisible()->whereKey($scheduled)->exists())->toBeTrue();
});

test('publication normalization preserves future schedules and existing publication history', function () {
    $scheduledAt = now()->addDay();
    $scheduled = lifecycleVacancy([
        'status' => VacancyStatus::Pending,
        'published_at' => $scheduledAt,
    ]);
    $scheduled->update(['status' => VacancyStatus::Active]);

    $publishedAt = now()->subWeek();
    $published = lifecycleVacancy(['published_at' => $publishedAt]);
    $published->update(['title' => 'Alleen de titel gewijzigd']);

    expect($scheduled->fresh()->published_at?->equalTo($scheduledAt))->toBeTrue()
        ->and($published->fresh()->published_at?->equalTo($publishedAt))->toBeTrue();
});

test('draft pending expired and ineligible-company vacancies remain hidden', function () {
    $draft = lifecycleVacancy(['status' => VacancyStatus::Draft, 'published_at' => now()->subDay()]);
    $pending = lifecycleVacancy(['status' => VacancyStatus::Pending, 'published_at' => now()->subDay()]);
    $expired = lifecycleVacancy(['expires_at' => now()->subSecond()]);
    $pastDeadline = lifecycleVacancy(['deadline_at' => now()->subSecond()]);
    $ineligibleCompany = Company::factory()->create(['status' => CompanyStatus::Pending]);
    $companyVacancy = lifecycleVacancy(['company_id' => $ineligibleCompany->id]);

    expect(Vacancy::publiclyVisible()->pluck('id')->all())
        ->not->toContain($draft->id, $pending->id, $expired->id, $pastDeadline->id)
        ->toContain($companyVacancy->id);

    $search = app(VacancySearch::class);
    $options = app(VacancyFilterOptions::class);
    expect($search->query($options->emptyFilters(), 'nieuwste')->pluck('id')->all())
        ->not->toContain($companyVacancy->id);
});
