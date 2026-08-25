<?php

use App\Enums\VacancySource;
use App\Enums\VacancyStatus;
use App\Models\Company;
use App\Models\Vacancy;
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
        'published_at' => null,
    ]);

    expect(Vacancy::publiclyVisible()->pluck('id')->all())->toContain($vacancy->id);
});
