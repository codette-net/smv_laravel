<?php

use App\Enums\ImportFormat;
use App\Enums\ImportStatus;
use App\Enums\ImportTransport;
use App\Enums\VacancySource;
use App\Imports\VacancyImportRunner;
use App\Models\ImportLog;
use App\Models\ImportMapping;
use App\Models\ImportMappingField;
use App\Models\ImportSource;
use App\Models\Vacancy;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

afterEach(function (): void {
    foreach (glob(storage_path('framework/missing-lifecycle-*.json')) ?: [] as $path) {
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
        'record_path' => 'jobs.*',
        'selection_rules' => $selectionRules,
        'configuration' => ['sample_path' => $path],
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
