<?php

use App\Enums\AdvertisingPackage;
use App\Enums\CompanyStatus;
use App\Enums\VacancySource;
use App\Enums\VacancyStatus;
use App\Models\Company;
use App\Models\User;
use App\Models\Vacancy;
use App\Models\VacancyDetailView;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

uses(RefreshDatabase::class);

beforeEach(function () {
    Carbon::setTestNow('2026-11-27 12:00:00 UTC');
});

afterEach(function () {
    Carbon::setTestNow();
});

function viewableVacancy(array $attributes = []): Vacancy
{
    return Vacancy::factory()->create([
        'company_id' => Company::factory()->state(['status' => CompanyStatus::Active]),
        'status' => VacancyStatus::Active,
        'is_filled' => false,
        'published_at' => now()->subDay(),
        'deadline_at' => now()->addWeek(),
        'expires_at' => now()->addMonth(),
        ...$attributes,
    ]);
}

test('each successful public detail GET records one raw view without visitor data', function () {
    $vacancy = viewableVacancy();

    $this->get(route('vacancies.show', $vacancy))->assertOk();
    $this->get(route('vacancies.show', $vacancy))->assertOk();

    expect($vacancy->detailViews()->count())->toBe(2)
        ->and(VacancyDetailView::query()->firstOrFail()->viewed_at->toDateTimeString())->toBe('2026-11-27 12:00:00')
        ->and(Schema::getColumnListing('vacancy_detail_views'))->toBe([
            'id',
            'vacancy_id',
            'viewed_at',
        ]);
});

test('HEAD unknown and non-public detail requests do not record views', function (array $attributes) {
    $vacancy = viewableVacancy($attributes);

    $this->head(route('vacancies.show', $vacancy));
    $this->get(route('vacancies.show', $vacancy));
    $this->get('/vacatures/bestaat-niet')->assertNotFound();

    expect(VacancyDetailView::query()->count())->toBe(0);
})->with([
    'draft' => [['status' => VacancyStatus::Draft]],
    'archived' => [['status' => VacancyStatus::Archived]],
    'filled' => [['is_filled' => true]],
    'expired' => [['expires_at' => Carbon::parse('2026-11-26 12:00:00', 'UTC')]],
    'source offlined' => [[
        'status' => VacancyStatus::Archived,
        'source' => VacancySource::Import,
        'source_offlined_at' => Carbon::parse('2026-11-26 12:00:00', 'UTC'),
    ]],
]);

test('a Vacancy for a non-public Company does not record a view', function () {
    $vacancy = viewableVacancy();
    $vacancy->company->update(['status' => CompanyStatus::Draft]);

    $this->get(route('vacancies.show', $vacancy))->assertNotFound();

    expect(VacancyDetailView::query()->count())->toBe(0);
});

test('the private employer preview does not record a public detail view', function () {
    $owner = User::factory()->create();
    $vacancy = viewableVacancy([
        'company_id' => Company::factory()->for($owner),
        'status' => VacancyStatus::Draft,
        'source' => VacancySource::Manual,
        'placement_package' => AdvertisingPackage::Standard,
    ]);

    $this->actingAs($owner)
        ->get(route('vacancy-placement.preview', $vacancy))
        ->assertOk();

    expect(VacancyDetailView::query()->count())->toBe(0);
});

test('analytics storage failure cannot take down a valid public detail response', function () {
    $vacancy = viewableVacancy();
    Schema::drop('vacancy_detail_views');
    Log::spy();

    $this->get(route('vacancies.show', $vacancy))->assertOk();

    Log::shouldHaveReceived('warning')->once()->withArgs(
        fn (string $message, array $context): bool => $message === 'Vacancy detail view recording failed.'
            && $context['vacancy_id'] === $vacancy->id
    );
});
