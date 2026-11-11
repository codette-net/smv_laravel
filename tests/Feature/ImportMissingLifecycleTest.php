<?php

use App\Enums\ImportFormat;
use App\Enums\ImportStatus;
use App\Enums\ImportTransport;
use App\Enums\VacancySource;
use App\Enums\VacancyStatus;
use App\Imports\VacancyImportRunner;
use App\Models\ImportLog;
use App\Models\ImportMapping;
use App\Models\ImportMappingField;
use App\Models\ImportSource;
use App\Models\Vacancy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Event;

uses(RefreshDatabase::class);

afterEach(function (): void {
    foreach (glob(storage_path('framework/missing-lifecycle-*')) ?: [] as $path) {
        unlink($path);
    }
});

function missingLifecycleMapping(array $jobs, ?array $selectionRules = null): array
{
    $path = storage_path('framework/missing-lifecycle-'.uniqid().'.json');
    file_put_contents($path, json_encode(['jobs' => $jobs], JSON_THROW_ON_ERROR));
    $source = ImportSource::factory()->create([
        'transport' => ImportTransport::Upload,
        'format' => ImportFormat::Json,
        'record_path' => 'jobs',
        'selection_rules' => $selectionRules,
        'configuration' => ['sample_path' => $path, 'snapshot_reconciliation' => ['enabled' => true]],
    ]);
    $mapping = ImportMapping::factory()->create(['import_source_id' => $source->id]);
    foreach ([['source_reference', ['id']], ['vacancy.title', ['title']]] as $position => [$destination, $paths]) {
        ImportMappingField::factory()->create(['import_mapping_id' => $mapping->id, 'destination_key' => $destination, 'source_paths' => $paths, 'position' => $position]);
    }

    return [$mapping->fresh('fields'), $path];
}

function replaceMissingLifecycleFeed(string $path, array $jobs): void
{
    file_put_contents($path, json_encode(['jobs' => $jobs], JSON_THROW_ON_ERROR));
}

test('successful reruns report newly missing still missing and restored vacancies reversibly', function () {
    [$mapping, $path] = missingLifecycleMapping([
        ['id' => 'A', 'title' => 'Vacature A'],
        ['id' => 'B', 'title' => 'Vacature B'],
        ['id' => 'C', 'title' => 'Vacature C'],
    ]);
    $first = app(VacancyImportRunner::class)->run($mapping);

    expect($first->status)->toBe(ImportStatus::Completed)
        ->and($first->missing_rows)->toBe(0)
        ->and($first->restored_rows)->toBe(0)
        ->and(Vacancy::where('import_source_id', $mapping->import_source_id)->whereNull('missing_since')->count())->toBe(3)
        ->and(Vacancy::where('source_reference', 'B')->first()->last_seen_import_id)->toBe($first->id);

    replaceMissingLifecycleFeed($path, [
        ['id' => 'A', 'title' => 'Vacature A'],
        ['id' => 'C', 'title' => 'Vacature C'],
    ]);
    $second = app(VacancyImportRunner::class)->run($mapping);
    $missingSince = Vacancy::where('source_reference', 'B')->first()->missing_since;

    expect($second->missing_rows)->toBe(1)
        ->and($second->restored_rows)->toBe(0)
        ->and($missingSince)->not->toBeNull()
        ->and(Vacancy::where('source_reference', 'B')->exists())->toBeTrue()
        ->and(ImportLog::where('import_id', $second->id)->where('context->code', 'missing_from_source')->exists())->toBeTrue();

    $stillMissing = app(VacancyImportRunner::class)->run($mapping);
    expect($stillMissing->missing_rows)->toBe(0)
        ->and(Vacancy::where('source_reference', 'B')->first()->missing_since->equalTo($missingSince))->toBeTrue()
        ->and(ImportLog::where('import_id', $stillMissing->id)->where('context->code', 'still_missing_from_source')->exists())->toBeTrue();

    replaceMissingLifecycleFeed($path, [
        ['id' => 'A', 'title' => 'Vacature A'],
        ['id' => 'B', 'title' => 'Vacature B terug'],
        ['id' => 'C', 'title' => 'Vacature C'],
    ]);
    $restored = app(VacancyImportRunner::class)->run($mapping);
    $vacancyB = Vacancy::where('source_reference', 'B')->first();

    expect($restored->restored_rows)->toBe(1)
        ->and($restored->missing_rows)->toBe(0)
        ->and($vacancyB->missing_since)->toBeNull()
        ->and($vacancyB->last_seen_import_id)->toBe($restored->id)
        ->and(ImportLog::where('import_id', $restored->id)->where('context->code', 'restored_in_source')->exists())->toBeTrue()
        ->and(Vacancy::where('import_source_id', $mapping->import_source_id)->count())->toBe(3);
});

test('a failed source run never marks existing vacancies missing', function () {
    [$mapping] = missingLifecycleMapping([
        ['id' => 'A', 'title' => 'Vacature A'],
        ['id' => 'B', 'title' => 'Vacature B'],
    ]);
    $first = app(VacancyImportRunner::class)->run($mapping);
    $beforeSeen = Vacancy::where('source_reference', 'B')->first()->last_seen_import_id;
    $mapping->importSource->update(['configuration' => ['sample_path' => storage_path('framework/does-not-exist.json')]]);

    $failed = app(VacancyImportRunner::class)->run($mapping);

    expect($failed->status)->toBe(ImportStatus::Failed)
        ->and($failed->missing_rows)->toBe(0)
        ->and(Vacancy::where('source_reference', 'B')->first()->missing_since)->toBeNull()
        ->and(Vacancy::where('source_reference', 'B')->first()->last_seen_import_id)->toBe($beforeSeen)
        ->and($beforeSeen)->toBe($first->id);
});

test('missing detection remains provider scoped and ignores manual vacancies', function () {
    [$mapping, $path] = missingLifecycleMapping([['id' => 'A', 'title' => 'Vacature A']]);
    app(VacancyImportRunner::class)->run($mapping);
    $otherSource = ImportSource::factory()->create();
    $otherSourceVacancy = Vacancy::factory()->create([
        'company_id' => $otherSource->company_id,
        'import_source_id' => $otherSource->id,
        'source_reference' => 'other-B',
        'source' => VacancySource::Import,
    ]);
    $manual = Vacancy::factory()->create([
        'company_id' => $mapping->importSource->company_id,
        'import_source_id' => null,
        'source_reference' => null,
        'source' => VacancySource::Manual,
    ]);
    replaceMissingLifecycleFeed($path, [['id' => 'Z', 'title' => 'Nieuwe vacature']]);

    $run = app(VacancyImportRunner::class)->run($mapping);

    expect($run->missing_rows)->toBe(1)
        ->and(Vacancy::where('source_reference', 'A')->first()->missing_since)->not->toBeNull()
        ->and($otherSourceVacancy->fresh()->missing_since)->toBeNull()
        ->and($manual->fresh()->missing_since)->toBeNull();
});

test('selection rules define the selected universe for missing reporting', function () {
    $rules = [['path' => 'department', 'operator' => 'equals', 'value' => 'Sales']];
    [$mapping] = missingLifecycleMapping([
        ['id' => 'A', 'title' => 'Salesfunctie', 'department' => 'Sales'],
        ['id' => 'B', 'title' => 'Techniekfunctie', 'department' => 'Techniek'],
    ], $rules);
    $source = $mapping->importSource;
    $previouslySelected = Vacancy::factory()->create([
        'company_id' => $source->company_id,
        'import_source_id' => $source->id,
        'source_reference' => 'B',
        'source' => VacancySource::Import,
        'last_seen_at' => now()->subDay(),
    ]);

    $run = app(VacancyImportRunner::class)->run($mapping);

    expect($run->total_rows)->toBe(1)
        ->and($run->missing_rows)->toBe(1)
        ->and($previouslySelected->fresh()->missing_since)->not->toBeNull()
        ->and(Vacancy::where('source_reference', 'A')->exists())->toBeTrue();
});

test('identical feeds update seen metadata without false missing records', function () {
    [$mapping] = missingLifecycleMapping([
        ['id' => 'A', 'title' => 'Vacature A'],
        ['id' => 'B', 'title' => 'Vacature B'],
    ]);
    app(VacancyImportRunner::class)->run($mapping);
    $second = app(VacancyImportRunner::class)->run($mapping);

    expect($second->missing_rows)->toBe(0)
        ->and($second->restored_rows)->toBe(0)
        ->and(Vacancy::whereNotNull('missing_since')->count())->toBe(0)
        ->and(Vacancy::where('last_seen_import_id', $second->id)->count())->toBe(2)
        ->and(Vacancy::count())->toBe(2);
});

test('sources without verified snapshot reconciliation never mark omitted vacancies missing', function () {
    [$mapping, $path] = missingLifecycleMapping([
        ['id' => 'A', 'title' => 'Vacature A'],
        ['id' => 'B', 'title' => 'Vacature B'],
    ]);
    $mapping->importSource->update(['configuration' => ['sample_path' => $path]]);
    app(VacancyImportRunner::class)->run($mapping);
    replaceMissingLifecycleFeed($path, [['id' => 'A', 'title' => 'Vacature A']]);

    $run = app(VacancyImportRunner::class)->run($mapping);

    expect($run->status)->toBe(ImportStatus::Completed)
        ->and(data_get($run->mapping, 'snapshot.state'))->toBe('not_configured')
        ->and($run->missing_rows)->toBe(0)
        ->and(Vacancy::where('source_reference', 'B')->first()->missing_since)->toBeNull();
});

test('invalid, duplicate, partial and unconfirmed empty snapshots do not apply or reconcile', function (array $jobs, string $expectedState) {
    [$mapping, $path] = missingLifecycleMapping([
        ['id' => 'A', 'title' => 'Vacature A'],
        ['id' => 'B', 'title' => 'Vacature B'],
    ]);
    app(VacancyImportRunner::class)->run($mapping);
    $configuration = ['sample_path' => $path, 'snapshot_reconciliation' => ['enabled' => true]];
    if ($expectedState === 'snapshot_marked_partial') {
        $configuration['snapshot_reconciliation']['partial'] = true;
    }
    $mapping->importSource->update(['configuration' => $configuration]);
    replaceMissingLifecycleFeed($path, $jobs);

    $run = app(VacancyImportRunner::class)->run($mapping);

    expect($run->status)->toBe(ImportStatus::Incomplete)
        ->and(data_get($run->mapping, 'snapshot.state'))->toBe($expectedState)
        ->and(Vacancy::where('source_reference', 'B')->first()->missing_since)->toBeNull()
        ->and(Vacancy::where('source_reference', 'C')->exists())->toBeFalse();
})->with([
    'mapping failure' => [[['id' => 'A', 'title' => 'Vacature A'], ['id' => 'C', 'title' => '']], 'snapshot_mapping_invalid'],
    'duplicate reference' => [[['id' => 'A', 'title' => 'Vacature A'], ['id' => 'A', 'title' => 'Dubbel']], 'snapshot_duplicate_reference'],
    'empty without explicit capability' => [[], 'snapshot_empty_unconfirmed'],
    'explicit partial source' => [[['id' => 'A', 'title' => 'Vacature A']], 'snapshot_marked_partial'],
]);

test('a declared legitimate empty snapshot is the only empty feed that can reconcile', function () {
    [$mapping, $path] = missingLifecycleMapping([['id' => 'A', 'title' => 'Vacature A']]);
    app(VacancyImportRunner::class)->run($mapping);
    $mapping->importSource->update(['configuration' => ['sample_path' => $path, 'snapshot_reconciliation' => ['enabled' => true, 'allow_empty' => true]]]);
    replaceMissingLifecycleFeed($path, []);

    $run = app(VacancyImportRunner::class)->run($mapping);

    expect($run->status)->toBe(ImportStatus::Completed)
        ->and(data_get($run->mapping, 'snapshot.state'))->toBe('verified_complete')
        ->and($run->missing_rows)->toBe(1)
        ->and(Vacancy::sole()->missing_since)->not->toBeNull();
});

test('persistence failure never reconciles a partially applied snapshot', function () {
    [$mapping, $path] = missingLifecycleMapping([
        ['id' => 'A', 'title' => 'Vacature A'],
        ['id' => 'B', 'title' => 'Vacature B'],
    ]);
    app(VacancyImportRunner::class)->run($mapping);
    replaceMissingLifecycleFeed($path, [
        ['id' => 'A', 'title' => 'Vacature A'],
        ['id' => 'C', 'title' => 'Vacature C'],
    ]);
    $event = 'eloquent.creating: '.Vacancy::class;
    Event::listen($event, function (Vacancy $vacancy): void {
        if ($vacancy->source_reference === 'C') {
            throw new RuntimeException('Test persistence failure');
        }
    });
    try {
        $run = app(VacancyImportRunner::class)->run($mapping);
    } finally {
        Event::forget($event);
    }

    expect($run->status)->toBe(ImportStatus::Failed)
        ->and(data_get($run->mapping, 'snapshot.state'))->toBe('application_failed')
        ->and(Vacancy::where('source_reference', 'B')->first()->missing_since)->toBeNull();
});

test('source locks reject an overlapping run and release for a later run', function () {
    [$mapping] = missingLifecycleMapping([['id' => 'A', 'title' => 'Vacature A']]);
    [$otherMapping] = missingLifecycleMapping([['id' => 'B', 'title' => 'Andere bron']]);
    $lock = Cache::lock('imports:source:'.$mapping->import_source_id, 60);
    expect($lock->get())->toBeTrue();
    try {
        $skipped = app(VacancyImportRunner::class)->run($mapping);
        $other = app(VacancyImportRunner::class)->run($otherMapping);
    } finally {
        $lock->release();
    }

    $later = app(VacancyImportRunner::class)->run($mapping);

    expect($skipped->status)->toBe(ImportStatus::Skipped)
        ->and(data_get($skipped->mapping, 'snapshot.state'))->toBe('concurrency_rejected')
        ->and($skipped->importLogs->pluck('context')->pluck('code'))->toContain('source_locked')
        ->and($other->status)->toBe(ImportStatus::Completed)
        ->and($later->status)->toBe(ImportStatus::Completed);
});

test('malformed XML and unresolved source-scoped employers never reconcile existing vacancies', function () {
    $xmlPath = storage_path('framework/missing-lifecycle-malformed-'.uniqid().'.xml');
    file_put_contents($xmlPath, '<source><job>');
    $xmlSource = ImportSource::factory()->create([
        'transport' => ImportTransport::Upload,
        'format' => ImportFormat::Xml,
        'record_path' => 'job',
        'configuration' => ['sample_path' => $xmlPath, 'snapshot_reconciliation' => ['enabled' => true]],
    ]);
    $xmlMapping = ImportMapping::factory()->create(['import_source_id' => $xmlSource->id]);
    ImportMappingField::factory()->create(['import_mapping_id' => $xmlMapping->id, 'destination_key' => 'source_reference', 'source_paths' => ['id']]);
    ImportMappingField::factory()->create(['import_mapping_id' => $xmlMapping->id, 'destination_key' => 'vacancy.title', 'source_paths' => ['title'], 'position' => 1]);
    $xmlVacancy = Vacancy::factory()->create(['company_id' => $xmlSource->company_id, 'import_source_id' => $xmlSource->id, 'source_reference' => 'old-xml', 'source' => VacancySource::Import]);

    [$mapping] = missingLifecycleMapping([['id' => 'unresolved', 'title' => 'Onbekend bedrijf']]);
    $mapping->importSource->update(['configuration' => ['sample_path' => $mapping->importSource->configuration['sample_path'], 'employer_resolution' => 'source_scoped', 'snapshot_reconciliation' => ['enabled' => true]]]);
    $employerVacancy = Vacancy::factory()->create(['company_id' => $mapping->importSource->company_id, 'import_source_id' => $mapping->import_source_id, 'source_reference' => 'old-employer', 'source' => VacancySource::Import]);

    $malformed = app(VacancyImportRunner::class)->run($xmlMapping->fresh('fields'));
    $unresolved = app(VacancyImportRunner::class)->run($mapping);

    expect($malformed->status)->toBe(ImportStatus::Failed)
        ->and(data_get($malformed->mapping, 'snapshot.state'))->toBe('read_or_parse_failed')
        ->and($xmlVacancy->fresh()->missing_since)->toBeNull()
        ->and($unresolved->status)->toBe(ImportStatus::Incomplete)
        ->and(data_get($unresolved->mapping, 'snapshot.state'))->toBe('snapshot_record_unresolved')
        ->and($employerVacancy->fresh()->missing_since)->toBeNull();

});

test('reappearing archived and expired imported vacancies clear only missing state', function () {
    [$mapping, $path] = missingLifecycleMapping([['id' => 'A', 'title' => 'Vacature A']]);
    app(VacancyImportRunner::class)->run($mapping);
    $mapping->importSource->update(['configuration' => ['sample_path' => $path, 'snapshot_reconciliation' => ['enabled' => true, 'allow_empty' => true]]]);
    replaceMissingLifecycleFeed($path, []);
    app(VacancyImportRunner::class)->run($mapping);
    $vacancy = Vacancy::sole();
    $vacancy->update(['status' => VacancyStatus::Archived, 'expires_at' => now()->subDay()]);
    replaceMissingLifecycleFeed($path, [['id' => 'A', 'title' => 'Vacature A terug']]);

    app(VacancyImportRunner::class)->run($mapping);

    expect($vacancy->fresh()->missing_since)->toBeNull()
        ->and($vacancy->fresh()->status)->toBe(VacancyStatus::Archived)
        ->and($vacancy->fresh()->expires_at->isPast())->toBeTrue();
});
