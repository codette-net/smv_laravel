<?php

use App\Enums\ApplicationMode;
use App\Enums\CompanyStatus;
use App\Enums\VacancySource;
use App\Enums\VacancyStatus;
use App\Models\Application;
use App\Models\Company;
use App\Models\ImportSource;
use App\Models\OutboundApplyClick;
use App\Models\Vacancy;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;

uses(RefreshDatabase::class);

beforeEach(function () {
    Carbon::setTestNow('2026-11-13 12:00:00');
});

afterEach(function () {
    Carbon::setTestNow();
});

function externallyApplicableVacancy(array $attributes = []): Vacancy
{
    return Vacancy::factory()->create([
        'company_id' => Company::factory()->state(['status' => CompanyStatus::Active]),
        'status' => VacancyStatus::Active,
        'source' => VacancySource::Import,
        'is_filled' => false,
        'published_at' => now()->subDay(),
        'deadline_at' => now()->addWeek(),
        'expires_at' => now()->addMonth(),
        'application_mode' => ApplicationMode::External,
        'application_url' => 'https://joobs.be/nl/jobs/k7Qd2XvP?utm_source=salesenmarketingvacatures&utm_medium=jobboard&utm_campaign=SM260142&utm_content=antwerpen',
        ...$attributes,
    ]);
}

test('an eligible external apply request records the vacancy attribution and preserves the stored URL exactly', function () {
    $source = ImportSource::factory()->create();
    $vacancy = externallyApplicableVacancy(['import_source_id' => $source]);
    $destination = $vacancy->application_url;

    $this->get(route('vacancies.apply.external', $vacancy))
        ->assertRedirect($destination);

    $click = OutboundApplyClick::query()->sole();

    expect($click->vacancy->is($vacancy))->toBeTrue()
        ->and($click->vacancy->importSource->is($source))->toBeTrue()
        ->and($click->vacancy->company->is($vacancy->company))->toBeTrue()
        ->and($click->clicked_at->toDateTimeString())->toBe('2026-11-13 12:00:00')
        ->and(Application::query()->count())->toBe(0);
});

test('each successful repeated request is recorded as a separate raw outbound click', function () {
    $vacancy = externallyApplicableVacancy();

    $this->get(route('vacancies.apply.external', $vacancy))->assertRedirect($vacancy->application_url);
    $this->get(route('vacancies.apply.external', $vacancy))->assertRedirect($vacancy->application_url);

    expect($vacancy->outboundApplyClicks()->count())->toBe(2);
});

test('the endpoint never accepts a request supplied redirect target', function () {
    $vacancy = externallyApplicableVacancy();

    $this->get(route('vacancies.apply.external', $vacancy).'?url=https://attacker.example/phishing')
        ->assertRedirect($vacancy->application_url);

    expect(OutboundApplyClick::query()->count())->toBe(1);
});

test('non external application modes cannot create outbound click events', function (ApplicationMode $mode) {
    $vacancy = externallyApplicableVacancy(['application_mode' => $mode]);

    $this->get(route('vacancies.apply.external', $vacancy))->assertNotFound();

    expect(OutboundApplyClick::query()->count())->toBe(0);
})->with([
    'internal' => ApplicationMode::Internal,
    'email' => ApplicationMode::Email,
]);

test('missing or unsafe stored application URLs are rejected without recording', function (?string $destination) {
    $vacancy = externallyApplicableVacancy(['application_url' => $destination]);

    $this->get(route('vacancies.apply.external', $vacancy))->assertNotFound();

    expect(OutboundApplyClick::query()->count())->toBe(0);
})->with([
    'missing' => null,
    'malformed' => 'not-a-url',
    'non-http scheme' => 'ftp://partner.example/job/42',
    'credentials in URL' => 'https://user:secret@partner.example/job/42',
]);

test('vacancies outside the public lifecycle cannot create outbound click events', function (array $attributes) {
    $vacancy = externallyApplicableVacancy($attributes);

    $this->get(route('vacancies.apply.external', $vacancy))->assertNotFound();

    expect(OutboundApplyClick::query()->count())->toBe(0);
})->with([
    'draft' => [['status' => VacancyStatus::Draft]],
    'filled' => [['is_filled' => true]],
    'not published yet' => [['published_at' => Carbon::parse('2026-11-14 12:00:00')]],
    'deadline passed' => [['deadline_at' => Carbon::parse('2026-11-12 12:00:00')]],
    'expired' => [['expires_at' => Carbon::parse('2026-11-12 12:00:00')]],
]);

test('a vacancy for a non-public company cannot create an outbound click event', function () {
    $vacancy = externallyApplicableVacancy();
    $vacancy->company->update(['status' => CompanyStatus::Draft]);

    $this->get(route('vacancies.apply.external', $vacancy))->assertNotFound();

    expect(OutboundApplyClick::query()->count())->toBe(0);
});

test('outbound click storage contains only the vacancy key and event timestamp', function () {
    expect(Schema::getColumnListing('outbound_apply_clicks'))->toBe([
        'id',
        'vacancy_id',
        'clicked_at',
    ]);
});
