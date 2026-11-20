<?php

use App\Enums\VacancyStatus;
use App\Filament\Pages\OutboundApplyClickReporting;
use App\Models\Company;
use App\Models\ImportSource;
use App\Models\OutboundApplyClick;
use App\Models\User;
use App\Models\Vacancy;
use App\Support\Analytics\OutboundApplyClickCsv;
use App\Support\Analytics\OutboundApplyClickFilters;
use App\Support\Analytics\OutboundApplyClickReport;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function () {
    Carbon::setTestNow('2026-11-20 12:00:00');
});

afterEach(function () {
    Carbon::setTestNow();
});

function clickReportUser(string $role): User
{
    Role::firstOrCreate(['name' => $role, 'guard_name' => 'web']);
    $user = User::factory()->create();
    $user->assignRole($role);

    return $user;
}

function clickReportVacancy(Company $company, ?ImportSource $source, string $title): Vacancy
{
    return Vacancy::factory()->for($company)->create([
        'title' => $title,
        'status' => VacancyStatus::Active,
        'import_source_id' => $source,
        'source_reference' => 'ref-'.str($title)->slug(),
    ]);
}

function recordOutboundClick(Vacancy $vacancy, string $clickedAt): OutboundApplyClick
{
    $click = new OutboundApplyClick;
    $click->vacancy()->associate($vacancy);
    $click->clicked_at = Carbon::parse($clickedAt, 'UTC');
    $click->save();

    return $click;
}

test('only administrators can access outbound click reporting', function (string $role, bool $allowed) {
    $this->actingAs(clickReportUser($role));

    expect(OutboundApplyClickReporting::canAccess())->toBe($allowed);

    $response = $this->get(OutboundApplyClickReporting::getUrl());
    $allowed ? $response->assertOk() : $response->assertForbidden();
})->with([
    'super admin' => ['super-admin', true],
    'admin' => ['admin', true],
    'editor' => ['editor', false],
]);

test('public and ordinary authenticated users cannot access outbound click reporting', function () {
    $this->get(OutboundApplyClickReporting::getUrl())->assertRedirect();

    $this->actingAs(clickReportUser('candidate'))
        ->get(OutboundApplyClickReporting::getUrl())
        ->assertForbidden();
});

test('report aggregates totals by vacancy company and import source including manual vacancies', function () {
    $companyA = Company::factory()->create(['name' => 'Alpha BV']);
    $companyB = Company::factory()->create(['name' => 'Beta BV']);
    $joobs = ImportSource::factory()->create(['name' => 'Joobs']);
    $vnom = ImportSource::factory()->create(['name' => 'VNOM']);
    $joobsVacancy = clickReportVacancy($companyA, $joobs, 'Joobs accountmanager');
    $manualVacancy = clickReportVacancy($companyA, null, 'Handmatige marketeer');
    $vnomVacancy = clickReportVacancy($companyB, $vnom, 'VNOM salesmanager');

    recordOutboundClick($joobsVacancy, '2026-11-01 10:00:00');
    recordOutboundClick($joobsVacancy, '2026-11-10 10:00:00');
    recordOutboundClick($joobsVacancy, '2026-10-01 10:00:00');
    recordOutboundClick($manualVacancy, '2026-11-11 10:00:00');
    recordOutboundClick($vnomVacancy, '2026-11-12 10:00:00');
    recordOutboundClick($vnomVacancy, '2026-11-13 10:00:00');

    $filters = OutboundApplyClickFilters::fromArray([]);
    $report = app(OutboundApplyClickReport::class);
    $vacancies = $report->vacancies($filters)->get()->keyBy('id');

    expect($report->total($filters))->toBe(5)
        ->and($vacancies[$joobsVacancy->id]->outbound_apply_clicks_count)->toBe(2)
        ->and($vacancies[$manualVacancy->id]->outbound_apply_clicks_count)->toBe(1)
        ->and($vacancies[$vnomVacancy->id]->outbound_apply_clicks_count)->toBe(2)
        ->and($report->companies($filters)->pluck('click_count', 'name')->all())->toBe([
            'Alpha BV' => 3,
            'Beta BV' => 2,
        ])
        ->and($report->importSources($filters)->pluck('click_count', 'name')->all())->toBe([
            'Joobs' => 2,
            'VNOM' => 2,
            '' => 1,
        ]);
});

test('date vacancy company source and combined filters constrain the same raw events', function () {
    $companyA = Company::factory()->create();
    $companyB = Company::factory()->create();
    $joobs = ImportSource::factory()->create(['name' => 'Joobs']);
    $vnom = ImportSource::factory()->create(['name' => 'VNOM']);
    $joobsA = clickReportVacancy($companyA, $joobs, 'Joobs A');
    $joobsB = clickReportVacancy($companyB, $joobs, 'Joobs B');
    $vnomA = clickReportVacancy($companyA, $vnom, 'VNOM A');

    recordOutboundClick($joobsA, '2026-11-05 08:00:00');
    recordOutboundClick($joobsA, '2026-11-15 08:00:00');
    recordOutboundClick($joobsB, '2026-11-16 08:00:00');
    recordOutboundClick($vnomA, '2026-11-17 08:00:00');

    $report = app(OutboundApplyClickReport::class);

    expect($report->total(OutboundApplyClickFilters::fromArray(['date_from' => '2026-11-15', 'date_to' => '2026-11-16'])))->toBe(2)
        ->and($report->total(OutboundApplyClickFilters::fromArray(['vacancy_id' => $joobsA->id])))->toBe(2)
        ->and($report->total(OutboundApplyClickFilters::fromArray(['company_id' => $companyA->id])))->toBe(3)
        ->and($report->total(OutboundApplyClickFilters::fromArray(['import_source_id' => $joobs->id])))->toBe(3)
        ->and($report->total(OutboundApplyClickFilters::fromArray([
            'date_from' => '2026-11-15',
            'date_to' => '2026-11-20',
            'company_id' => $companyA->id,
            'import_source_id' => $joobs->id,
        ])))->toBe(1);
});

test('the Filament report supports filtering sorting zero results and raw repeat semantics', function () {
    $admin = clickReportUser('admin');
    $companyA = Company::factory()->create(['name' => 'A-bedrijf']);
    $companyB = Company::factory()->create(['name' => 'B-bedrijf']);
    $sourceA = ImportSource::factory()->create(['name' => 'A-bron']);
    $sourceZ = ImportSource::factory()->create(['name' => 'Z-bron']);
    $low = clickReportVacancy($companyA, $sourceZ, 'Weinig clicks');
    $high = clickReportVacancy($companyB, $sourceA, 'Veel clicks');

    recordOutboundClick($low, '2026-11-18 10:00:00');
    recordOutboundClick($high, '2026-11-18 10:00:00');
    recordOutboundClick($high, '2026-11-18 10:00:00');

    Livewire::actingAs($admin)
        ->test(OutboundApplyClickReporting::class)
        ->assertSee('Raw outbound apply clicks')
        ->assertSee('geen unieke kandidaten', false)
        ->assertSee('maximaal 24 maanden')
        ->sortTable('outbound_apply_clicks_count', 'asc')
        ->assertCanSeeTableRecords([$low, $high], inOrder: true)
        ->sortTable('title', 'asc')
        ->assertCanSeeTableRecords([$high, $low], inOrder: true)
        ->sortTable('company.name', 'asc')
        ->assertCanSeeTableRecords([$low, $high], inOrder: true)
        ->sortTable('importSource.name', 'asc')
        ->assertCanSeeTableRecords([$high, $low], inOrder: true)
        ->filterTable('company', $companyA->id)
        ->assertCanSeeTableRecords([$low])
        ->assertCanNotSeeTableRecords([$high])
        ->filterTable('period', ['from' => '2026-10-01', 'to' => '2026-10-02'])
        ->assertCountTableRecords(0);
});

test('CSV exports deterministic aggregate rows for the selected period without personal data', function () {
    $company = Company::factory()->create(['name' => 'Acme Sales', 'email' => 'private-company@example.test']);
    $joobs = ImportSource::factory()->create(['name' => 'Joobs']);
    $joobsVacancy = clickReportVacancy($company, $joobs, 'Accountmanager Antwerpen');
    $manualVacancy = clickReportVacancy($company, null, 'Marketingmanager Utrecht');
    recordOutboundClick($joobsVacancy, '2026-11-10 09:00:00');
    recordOutboundClick($joobsVacancy, '2026-11-10 09:00:01');
    recordOutboundClick($manualVacancy, '2026-11-11 09:00:00');

    $filters = OutboundApplyClickFilters::fromArray([
        'date_from' => '2026-11-01',
        'date_to' => '2026-11-20',
    ]);
    $csv = app(OutboundApplyClickCsv::class)->generate($filters);
    $lines = preg_split('/\r\n|\r|\n/', trim($csv));

    expect(str_starts_with($csv, "\xEF\xBB\xBF"))->toBeTrue()
        ->and(str_getcsv(ltrim($lines[0], "\xEF\xBB\xBF")))->toBe([
            'vacancy_id',
            'source_reference',
            'vacancy_title',
            'company',
            'import_source',
            'vacancy_status',
            'raw_outbound_apply_clicks',
            'period_from_utc',
            'period_to_utc',
        ])
        ->and($lines[1])->toContain('Accountmanager Antwerpen', 'Joobs', ',2,2026-11-01,2026-11-20')
        ->and($lines[2])->toContain('Marketingmanager Utrecht', 'Geen importbron', ',1,2026-11-01,2026-11-20')
        ->and($csv)->not->toContain('private-company@example.test', 'ip_address', 'user_agent', 'candidate');
});

test('the admin CSV download uses the current table filters', function () {
    $admin = clickReportUser('admin');
    $includedCompany = Company::factory()->create();
    $excludedCompany = Company::factory()->create();
    $included = clickReportVacancy($includedCompany, null, 'In CSV');
    $excluded = clickReportVacancy($excludedCompany, null, 'Niet in CSV');
    recordOutboundClick($included, '2026-11-18 10:00:00');
    recordOutboundClick($excluded, '2026-11-18 10:00:00');

    $expected = app(OutboundApplyClickCsv::class)->generate(OutboundApplyClickFilters::fromArray([
        'date_from' => '2026-10-22',
        'date_to' => '2026-11-20',
        'company_id' => $includedCompany->id,
    ]));

    Livewire::actingAs($admin)
        ->test(OutboundApplyClickReporting::class)
        ->filterTable('company', $includedCompany->id)
        ->call('downloadCsv')
        ->assertFileDownloaded('outbound-apply-clicks-2026-10-22-2026-11-20.csv', $expected);
});

test('reporting indexes support date-first and per-vacancy date-range queries', function () {
    $indexColumns = collect(Schema::getIndexes('outbound_apply_clicks'))->pluck('columns');

    expect($indexColumns)->toContain(['clicked_at'])
        ->and($indexColumns)->toContain(['vacancy_id', 'clicked_at']);
});
