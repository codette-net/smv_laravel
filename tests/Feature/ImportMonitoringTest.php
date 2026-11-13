<?php

use App\Enums\ApplicationMode;
use App\Enums\ImportExecutionOrigin;
use App\Enums\ImportFormat;
use App\Enums\ImportScheduleFrequency;
use App\Enums\ImportSourceHealth;
use App\Enums\ImportStatus;
use App\Enums\ImportTransport;
use App\Filament\Resources\ImportSources\ImportSourceResource;
use App\Filament\Resources\ImportSources\Pages\ViewImportSource;
use App\Imports\Monitoring\ImportSourceHealthEvaluator;
use App\Imports\VacancyImportRunner;
use App\Models\Import;
use App\Models\ImportMapping;
use App\Models\ImportMappingField;
use App\Models\ImportSource;
use App\Models\User;
use App\Models\Vacancy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

afterEach(fn () => Carbon::setTestNow());

function monitoredScheduledSource(array $attributes = []): ImportSource
{
    $source = ImportSource::factory()->create(array_merge([
        'automatic_import_enabled' => true,
        'schedule_frequency' => ImportScheduleFrequency::Hourly,
        'selection_rules' => null,
    ], $attributes));
    $source->approve(User::factory()->create());

    return $source->fresh();
}

function monitoredRun(ImportSource $source, ImportStatus $status, array $attributes = []): Import
{
    return Import::create(array_merge([
        'import_source_id' => $source->id,
        'status' => $status,
        'execution_origin' => ImportExecutionOrigin::Scheduled,
        'started_at' => now()->subMinutes(5),
        'finished_at' => $status === ImportStatus::Processing ? null : now(),
        'mapping' => ['snapshot' => ['state' => $status === ImportStatus::Completed ? 'verified_complete' : 'read_or_parse_failed']],
    ], $attributes));
}

function recoverableImportSource(): ImportSource
{
    $source = ImportSource::factory()->create([
        'transport' => ImportTransport::Upload,
        'format' => ImportFormat::Json,
        'endpoint_url' => null,
        'record_path' => 'jobs.*',
        'selection_rules' => null,
        'configuration' => ['sample_path' => base_path('tests/Fixtures/Imports/orange-career/provisional-example.json')],
    ]);
    $mapping = ImportMapping::factory()->create(['import_source_id' => $source->id, 'is_active' => true, 'is_default' => true]);
    foreach ([
        ['source_reference', ['title'], 'direct', []],
        ['vacancy.title', ['title'], 'direct', []],
        ['vacancy.application_mode', [], 'default', ['value' => ApplicationMode::Internal->value]],
    ] as $position => [$destination, $paths, $operation, $configuration]) {
        ImportMappingField::factory()->create([
            'import_mapping_id' => $mapping->id,
            'destination_key' => $destination,
            'source_paths' => $paths,
            'operation' => $operation,
            'configuration' => $configuration,
            'position' => $position,
        ]);
    }

    return $source;
}

test('health distinguishes healthy never run running and overdue sources from execution attempts', function () {
    Carbon::setTestNow('2026-11-12 12:00:00');
    $evaluator = app(ImportSourceHealthEvaluator::class);
    $healthy = monitoredScheduledSource();
    monitoredRun($healthy, ImportStatus::Completed);
    $neverRun = monitoredScheduledSource();
    $running = monitoredScheduledSource();
    monitoredRun($running, ImportStatus::Processing, ['started_at' => now()->subMinutes(2)]);
    $overdue = monitoredScheduledSource(['last_scheduled_at' => now()]);
    $overdue->forceFill(['approved_at' => now()->subHours(2)])->save();

    expect($evaluator->for($healthy)->health)->toBe(ImportSourceHealth::Healthy)
        ->and($evaluator->for($neverRun)->health)->toBe(ImportSourceHealth::NeverRun)
        ->and($evaluator->for($running)->health)->toBe(ImportSourceHealth::Running)
        ->and($evaluator->for($overdue)->health)->toBe(ImportSourceHealth::Overdue)
        ->and($evaluator->for($overdue)->lastAttempt)->toBeNull()
        ->and($overdue->last_scheduled_at)->not->toBeNull();
});

test('health distinguishes repeated failures incomplete and lock skips', function () {
    $evaluator = app(ImportSourceHealthEvaluator::class);
    $repeated = monitoredScheduledSource();
    monitoredRun($repeated, ImportStatus::Failed, ['started_at' => now()->subMinutes(20)]);
    monitoredRun($repeated, ImportStatus::Incomplete);
    $incomplete = monitoredScheduledSource();
    monitoredRun($incomplete, ImportStatus::Incomplete);
    $skipped = monitoredScheduledSource();
    monitoredRun($skipped, ImportStatus::Skipped, ['mapping' => ['snapshot' => ['state' => 'concurrency_rejected']]]);

    expect($evaluator->for($repeated)->health)->toBe(ImportSourceHealth::RepeatedlyFailing)
        ->and($evaluator->for($repeated)->consecutiveFailures)->toBe(2)
        ->and($evaluator->for($incomplete)->health)->toBe(ImportSourceHealth::Incomplete)
        ->and($evaluator->for($skipped)->health)->toBe(ImportSourceHealth::Skipped);
});

test('manual-only and disabled sources are not marked overdue merely for not running', function () {
    Carbon::setTestNow('2026-11-12 12:00:00');
    $manual = ImportSource::factory()->create(['automatic_import_enabled' => false, 'created_at' => now()->subWeek()]);
    $disabled = monitoredScheduledSource(['is_active' => false, 'created_at' => now()->subWeek()]);
    $evaluator = app(ImportSourceHealthEvaluator::class);

    expect($evaluator->for($manual)->health)->toBe(ImportSourceHealth::ManualOnly)
        ->and($evaluator->for($manual)->overdueAt)->toBeNull()
        ->and($evaluator->for($disabled)->health)->toBe(ImportSourceHealth::Disabled)
        ->and($evaluator->for($disabled)->overdueAt)->toBeNull();
});

test('verified count information exposes substantial drops without changing the run outcome', function () {
    $source = monitoredScheduledSource();
    monitoredRun($source, ImportStatus::Completed, ['total_rows' => 100, 'imported_rows' => 90, 'skipped_rows' => 10, 'started_at' => now()->subHours(2)]);
    $latest = monitoredRun($source, ImportStatus::Completed, ['total_rows' => 40, 'updated_rows' => 38, 'failed_rows' => 2]);
    $report = app(ImportSourceHealthEvaluator::class)->for($source);

    expect($report->lastVerified->is($latest))->toBeTrue()
        ->and($report->previousVerifiedTotal)->toBe(100)
        ->and($report->verifiedCountDropPercent)->toBe(60.0)
        ->and($report->hasSubstantialCountDrop())->toBeTrue()
        ->and($latest->acceptedRows())->toBe(38)
        ->and($latest->rejectedRows())->toBe(2)
        ->and($latest->status)->toBe(ImportStatus::Completed);
});

test('administrator recovery uses the normal manual pipeline remains idempotent and respects locks', function () {
    Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
    Role::firstOrCreate(['name' => 'editor', 'guard_name' => 'web']);
    $admin = User::factory()->create();
    $admin->assignRole('admin');
    $editor = User::factory()->create();
    $editor->assignRole('editor');
    $source = recoverableImportSource();

    Livewire::actingAs($admin)->test(ViewImportSource::class, ['record' => $source->id])->assertActionVisible('runAgain');
    Livewire::actingAs($editor)->test(ViewImportSource::class, ['record' => $source->id])->assertActionHidden('runAgain');

    Livewire::actingAs($admin)->test(ViewImportSource::class, ['record' => $source->id])->callAction('runAgain');
    Livewire::actingAs($admin)->test(ViewImportSource::class, ['record' => $source->id])->callAction('runAgain');

    expect(Vacancy::where('import_source_id', $source->id)->count())->toBe(2)
        ->and(Import::where('import_source_id', $source->id)->count())->toBe(2)
        ->and(Import::where('import_source_id', $source->id)->get()->every(fn (Import $run) => $run->execution_origin === ImportExecutionOrigin::Manual))->toBeTrue();

    $lock = Cache::lock(VacancyImportRunner::sourceLockKey($source->id), VacancyImportRunner::SOURCE_LOCK_SECONDS);
    expect($lock->get())->toBeTrue();
    try {
        Livewire::actingAs($admin)->test(ViewImportSource::class, ['record' => $source->id])->callAction('runAgain');
    } finally {
        $lock->release();
    }

    expect(Import::latest('id')->first()->status)->toBe(ImportStatus::Skipped)
        ->and(Import::latest('id')->first()->snapshotState())->toBe('concurrency_rejected')
        ->and(Vacancy::where('import_source_id', $source->id)->count())->toBe(2);
});

test('monitoring pages redact endpoint query secrets and show safe run context', function () {
    Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
    $admin = User::factory()->create();
    $admin->assignRole('admin');
    $source = monitoredScheduledSource(['endpoint_url' => 'https://example.test/feed.xml?token=monitor-secret']);
    monitoredRun($source, ImportStatus::Failed);

    $this->actingAs($admin)->get(ImportSourceResource::getUrl('view', ['record' => $source]))
        ->assertSuccessful()
        ->assertSee('https://example.test/feed.xml')
        ->assertSee('read_or_parse_failed')
        ->assertDontSee('monitor-secret');
});
