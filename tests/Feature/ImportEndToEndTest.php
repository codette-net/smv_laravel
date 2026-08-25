<?php

use App\Enums\ApplicationMode;
use App\Enums\CategoryType;
use App\Enums\CompensationPeriod;
use App\Enums\ImportFormat;
use App\Enums\ImportTransport;
use App\Imports\Preview\ImportPreview;
use App\Imports\VacancyImportRunner;
use App\Models\Category;
use App\Models\ImportMapping;
use App\Models\ImportMappingField;
use App\Models\ImportSource;
use App\Models\Vacancy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

function endToEndMapping(ImportFormat $format, string $fixture, ?string $recordPath, array $fields, ?array $selectionRules = null): array
{
    $source = ImportSource::factory()->create([
        'transport' => ImportTransport::Upload,
        'format' => $format,
        'endpoint_url' => null,
        'record_path' => $recordPath,
        'selection_rules' => $selectionRules,
        'configuration' => ['sample_path' => base_path("tests/Fixtures/Imports/{$fixture}")],
    ]);
    $mapping = ImportMapping::factory()->create(['import_source_id' => $source->id]);

    foreach ($fields as $position => $field) {
        ImportMappingField::factory()->create([
            'import_mapping_id' => $mapping->id,
            'destination_key' => $field[0],
            'source_paths' => $field[1] ?? [],
            'operation' => $field[2] ?? 'direct',
            'configuration' => $field[3] ?? [],
            'position' => $position,
        ]);
    }

    return [$source->fresh('company'), $mapping->fresh('fields')];
}

function canonicalVacancy(Vacancy $vacancy): array
{
    return [
        'source_reference' => $vacancy->source_reference,
        'title' => $vacancy->title,
        'description' => $vacancy->description,
        'location' => $vacancy->location,
        'application_mode' => $vacancy->application_mode?->value,
        'application_url' => $vacancy->application_url,
        'salary_min' => $vacancy->salary_min,
        'salary_max' => $vacancy->salary_max,
        'salary_currency' => $vacancy->salary_currency,
        'salary_period' => $vacancy->salary_period?->value,
        'rate_min' => $vacancy->rate_min,
        'rate_max' => $vacancy->rate_max,
        'rate_currency' => $vacancy->rate_currency,
        'rate_period' => $vacancy->rate_period?->value,
    ];
}

function canonicalPreviewRecord($record): array
{
    $data = $record->outcome->data;

    return [
        'source_reference' => (string) $data->get('source_reference'),
        'title' => $data->get('vacancy.title'),
        'description' => $data->get('vacancy.description') ?? '',
        'location' => $data->get('vacancy.location'),
        'application_mode' => $data->get('vacancy.application_mode'),
        'application_url' => $data->get('vacancy.application_url'),
        'salary_min' => $data->get('vacancy.salary_min') === null ? null : (int) $data->get('vacancy.salary_min'),
        'salary_max' => $data->get('vacancy.salary_max') === null ? null : (int) $data->get('vacancy.salary_max'),
        'salary_currency' => $data->get('vacancy.salary_currency'),
        'salary_period' => $data->get('vacancy.salary_period'),
        'rate_min' => $data->get('vacancy.rate_min') === null ? null : (int) $data->get('vacancy.rate_min'),
        'rate_max' => $data->get('vacancy.rate_max') === null ? null : (int) $data->get('vacancy.rate_max'),
        'rate_currency' => $data->get('vacancy.rate_currency'),
        'rate_period' => $data->get('vacancy.rate_period'),
    ];
}

function assertPreviewExecutionParity(ImportSource $source, ImportMapping $mapping, int $expectedRecords): array
{
    $companyBefore = $source->company->only(['name', 'description', 'email', 'phone', 'logo']);
    $preview = app(ImportPreview::class)->build($source, $mapping, 25);
    $importable = collect($preview['records'])->filter(fn ($record) => $record->outcome->canImport())->values();
    $run = app(VacancyImportRunner::class)->run($mapping);

    expect($preview['counts']['previewed'])->toBe($expectedRecords)
        ->and($run->total_rows)->toBe($expectedRecords)
        ->and($run->imported_rows)->toBe($importable->count())
        ->and($run->failed_rows)->toBe(0);

    foreach ($importable as $record) {
        $vacancy = Vacancy::query()
            ->where('import_source_id', $source->id)
            ->where('source_reference', (string) $record->outcome->data->get('source_reference'))
            ->firstOrFail();
        $resolvedCategoryIds = collect($record->outcome->resolved)->pluck('category_id')->sort()->values()->all();

        expect(canonicalVacancy($vacancy))->toBe(canonicalPreviewRecord($record))
            ->and($vacancy->company_id)->toBe($source->company_id)
            ->and($vacancy->categories->pluck('id')->sort()->values()->all())->toBe($resolvedCategoryIds)
            ->and($vacancy->tags->pluck('name')->sort()->values()->all())->toBe(collect((array) $record->outcome->data->get('tags', []))->sort()->values()->all());
    }

    expect($source->company->fresh()->only(['name', 'description', 'email', 'phone', 'logo']))->toBe($companyBefore);

    $vacancyCount = Vacancy::where('import_source_id', $source->id)->count();
    $categoryAttachments = DB::table('categoryables')->where('categoryable_type', Vacancy::class)->whereIn('categoryable_id', Vacancy::where('import_source_id', $source->id)->pluck('id'))->count();
    $tagAttachments = DB::table('taggables')->where('taggable_type', Vacancy::class)->whereIn('taggable_id', Vacancy::where('import_source_id', $source->id)->pluck('id'))->count();
    $rerun = app(VacancyImportRunner::class)->run($mapping);

    expect($rerun->imported_rows)->toBe(0)
        ->and($rerun->updated_rows)->toBe($importable->count())
        ->and(Vacancy::where('import_source_id', $source->id)->count())->toBe($vacancyCount)
        ->and(DB::table('categoryables')->where('categoryable_type', Vacancy::class)->whereIn('categoryable_id', Vacancy::where('import_source_id', $source->id)->pluck('id'))->count())->toBe($categoryAttachments)
        ->and(DB::table('taggables')->where('taggable_type', Vacancy::class)->whereIn('taggable_id', Vacancy::where('import_source_id', $source->id)->pluck('id'))->count())->toBe($tagAttachments);

    return [$preview, $run, $rerun];
}

test('Orange Career JSON has preview execution parity selection idempotency and no Company overwrite', function () {
    $function = Category::factory()->create(['name' => 'Accountmanager', 'type' => CategoryType::function_area]);
    [$source, $mapping] = endToEndMapping(ImportFormat::Json, 'orange-career/final_sanitized_jobs_reordered.json', '*', [
        ['source_reference', ['id']],
        ['vacancy.title', ['title']],
        ['vacancy.description', ['description']],
        ['vacancy.location', ['detailed_location.city']],
        ['vacancy.application_mode', [], 'default', ['value' => ApplicationMode::External->value]],
        ['vacancy.application_url', ['apply_url']],
        ['vacancy.salary_min', ['salary_low'], 'transform', ['transform' => 'annual_salary_to_monthly']],
        ['vacancy.salary_max', ['salary_high'], 'transform', ['transform' => 'annual_salary_to_monthly']],
        ['vacancy.salary_currency', ['salary_currency']],
        ['vacancy.salary_period', [], 'default', ['value' => CompensationPeriod::Month->value]],
        ['taxonomy.function_area', ['function_name']],
        ['tags', ['function_name']],
    ], ['logic' => 'and', 'rules' => [['field' => 'id', 'operator' => 'equals', 'value' => 1820725136]]]);

    [, $run] = assertPreviewExecutionParity($source, $mapping, 1);

    expect($run->imported_rows)->toBe(1)
        ->and(Vacancy::sole()->categories()->whereKey($function)->exists())->toBeTrue()
        ->and(Vacancy::sole()->tags->pluck('name')->all())->toBe(['Accountmanager'])
        ->and(Vacancy::sole()->salary_min)->toBe(3750)
        ->and(Vacancy::sole()->salary_max)->toBe(5417);
});

test('VNOM XML flat mappings have preview execution parity and idempotency', function () {
    Category::factory()->create(['name' => 'Sales', 'type' => CategoryType::function_area]);
    [$source, $mapping] = endToEndMapping(ImportFormat::Xml, 'vnom/jobs_for_test.xml', 'job', [
        ['source_reference', ['identifier']], ['vacancy.title', ['title']], ['vacancy.description', ['description']], ['vacancy.location', ['city']],
        ['vacancy.application_mode', [], 'default', ['value' => ApplicationMode::External->value]], ['vacancy.application_url', ['applyUrl']], ['taxonomy.function_area', ['function']],
    ], ['logic' => 'and', 'rules' => [['field' => 'identifier', 'operator' => 'equals', 'value' => 'a1WWy000001RVvBMAW']]]);

    assertPreviewExecutionParity($source, $mapping, 1);
});

test('Michael Page XML nested and combined mappings have preview execution parity', function () {
    Category::factory()->create(['name' => 'Sales', 'type' => CategoryType::function_area]);
    Category::factory()->create(['name' => 'Retail', 'type' => CategoryType::sector]);
    [$source, $mapping] = endToEndMapping(ImportFormat::Xml, 'michael-page/jobs_for_test.xml', 'job', [
        ['source_reference', ['uniqueJobID']], ['vacancy.title', ['title']], ['vacancy.description', ['description.role', 'description.candidate'], 'combine', ['separator' => "\n\n"]], ['vacancy.location', ['location.text']],
        ['vacancy.application_mode', [], 'default', ['value' => ApplicationMode::External->value]], ['vacancy.application_url', ['Job_Detail_URL']],
        ['vacancy.salary_min', ['salary.min'], 'transform', ['transform' => 'annual_salary_to_monthly']], ['vacancy.salary_max', ['salary.max'], 'transform', ['transform' => 'annual_salary_to_monthly']],
        ['vacancy.salary_currency', ['salary.currency']], ['vacancy.salary_period', [], 'default', ['value' => CompensationPeriod::Month->value]],
        ['taxonomy.function_area', ['sector.term']], ['taxonomy.sector', ['industry.term']],
    ], ['logic' => 'and', 'rules' => [['field' => 'uniqueJobID', 'operator' => 'equals', 'value' => 'JN-082026-7084517_MP_NL']]]);

    [$preview] = assertPreviewExecutionParity($source, $mapping, 1);
    expect($preview['records'][0]->outcome->data->get('vacancy.description'))->toContain('eerste aanspreekpunt', 'beschikbaar voor 32 tot 40 uur');
});

test('CSV and XLSX produce equivalent canonical preview and persisted vacancies', function () {
    foreach ([['Sales', CategoryType::function_area], ['Marketing', CategoryType::function_area], ['SaaS', CategoryType::sector], ['E-commerce', CategoryType::sector]] as [$name, $type]) {
        Category::factory()->create(['name' => $name, 'type' => $type]);
    }
    $fields = [
        ['source_reference', ['source_reference']], ['vacancy.title', ['title']], ['vacancy.location', ['location']],
        ['vacancy.application_mode', [], 'default', ['value' => ApplicationMode::Internal->value]],
        ['vacancy.salary_min', ['salary_min']], ['vacancy.salary_max', ['salary_max']], ['vacancy.salary_period', ['salary_period']],
        ['taxonomy.function_area', ['function_area']], ['taxonomy.sector', ['sector']], ['tags', ['sector']],
    ];
    [$csvSource, $csvMapping] = endToEndMapping(ImportFormat::Csv, 'csv/jobs.csv', null, $fields);
    [$xlsxSource, $xlsxMapping] = endToEndMapping(ImportFormat::Xlsx, 'xlsx/jobs.xlsx', null, $fields);
    [$csvPreview] = assertPreviewExecutionParity($csvSource, $csvMapping, 2);
    [$xlsxPreview] = assertPreviewExecutionParity($xlsxSource, $xlsxMapping, 2);

    $canonical = fn (array $preview): array => collect($preview['records'])->map(fn ($record) => canonicalPreviewRecord($record))->all();
    expect($canonical($csvPreview))->toBe($canonical($xlsxPreview));
});

test('unresolved taxonomy is classified consistently and skipped without blocking valid CSV records', function () {
    Category::factory()->create(['name' => 'Sales', 'type' => CategoryType::function_area]);
    Category::factory()->create(['name' => 'SaaS', 'type' => CategoryType::sector]);
    [$source, $mapping] = endToEndMapping(ImportFormat::Csv, 'csv/jobs.csv', null, [
        ['source_reference', ['source_reference']], ['vacancy.title', ['title']], ['vacancy.location', ['location']],
        ['vacancy.application_mode', [], 'default', ['value' => ApplicationMode::Internal->value]], ['taxonomy.function_area', ['function_area']], ['taxonomy.sector', ['sector']],
    ]);

    $preview = app(ImportPreview::class)->build($source, $mapping, 25);
    $run = app(VacancyImportRunner::class)->run($mapping);

    expect($preview['counts']['ready'])->toBe(1)
        ->and($preview['counts']['needs_resolution'])->toBe(1)
        ->and($preview['records'][1]->outcome->status())->toBe('needs_resolution')
        ->and($run->imported_rows)->toBe(1)
        ->and($run->skipped_rows)->toBe(1)
        ->and($run->failed_rows)->toBe(0)
        ->and(Vacancy::where('source_reference', 'TEST-001')->exists())->toBeTrue()
        ->and(Vacancy::where('source_reference', 'TEST-002')->exists())->toBeFalse()
        ->and($run->importLogs->contains(fn ($log): bool => data_get($log->context, 'source_reference') === 'TEST-002' && data_get($log->context, 'code') === 'validation_or_resolution'))->toBeTrue();
});

test('preview validation errors are skipped consistently during execution', function () {
    [$source, $mapping] = endToEndMapping(ImportFormat::Csv, 'csv/jobs.csv', null, [
        ['source_reference', ['source_reference']],
        ['vacancy.title', ['missing_title']],
    ], ['logic' => 'and', 'rules' => [['field' => 'source_reference', 'operator' => 'equals', 'value' => 'TEST-001']]]);

    $preview = app(ImportPreview::class)->build($source, $mapping, 25);
    $run = app(VacancyImportRunner::class)->run($mapping);

    expect($preview['counts']['errors'])->toBe(1)
        ->and($preview['records'][0]->outcome->status())->toBe('error')
        ->and($run->total_rows)->toBe(1)
        ->and($run->imported_rows)->toBe(0)
        ->and($run->skipped_rows)->toBe(1)
        ->and($run->failed_rows)->toBe(0)
        ->and(Vacancy::where('import_source_id', $source->id)->exists())->toBeFalse()
        ->and($run->importLogs->contains(fn ($log): bool => data_get($log->context, 'source_reference') === 'TEST-001' && data_get($log->context, 'code') === 'validation_or_resolution'))->toBeTrue();
});
