<?php

use App\Enums\ApplicationMode;
use App\Enums\ImportExecutionOrigin;
use App\Enums\ImportFormat;
use App\Enums\ImportScheduleFrequency;
use App\Enums\ImportStatus;
use App\Enums\ImportTransport;
use App\Imports\Scheduling\DueImportDispatcher;
use App\Imports\VacancyImportRunner;
use App\Jobs\RunScheduledImport;
use App\Models\Company;
use App\Models\Import;
use App\Models\ImportMapping;
use App\Models\ImportMappingField;
use App\Models\ImportSource;
use App\Models\User;
use App\Models\Vacancy;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Factory as HttpFactory;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;

uses(RefreshDatabase::class);

afterEach(fn () => Carbon::setTestNow());

function scheduledImportSource(array $sourceAttributes = [], array $jobs = [['id' => 'AUTO-1', 'title' => 'Automatische vacature']]): array
{
    $source = ImportSource::factory()->create(array_merge([
        'company_id' => Company::factory(),
        'transport' => ImportTransport::Http,
        'format' => ImportFormat::Json,
        'endpoint_url' => 'https://93.184.216.34/feed-'.uniqid().'.json',
        'record_path' => 'jobs',
        'selection_rules' => null,
        'configuration' => null,
        'is_active' => true,
        'automatic_import_enabled' => true,
        'schedule_frequency' => ImportScheduleFrequency::Hourly,
    ], $sourceAttributes));
    $source->approve(User::factory()->create());
    $mapping = ImportMapping::factory()->create([
        'import_source_id' => $source->id,
        'is_active' => true,
        'is_default' => true,
    ]);
    foreach ([
        ['source_reference', ['id']],
        ['vacancy.title', ['title']],
        ['vacancy.application_mode', [], 'default', ['value' => ApplicationMode::Internal->value]],
    ] as $position => $field) {
        ImportMappingField::factory()->create([
            'import_mapping_id' => $mapping->id,
            'destination_key' => $field[0],
            'source_paths' => $field[1],
            'operation' => $field[2] ?? 'direct',
            'configuration' => $field[3] ?? [],
            'position' => $position,
        ]);
    }

    Http::fake([$source->endpoint_url => Http::response(['jobs' => $jobs])]);

    return [$source->fresh(), $mapping->fresh('fields')];
}

test('only due eligible remote sources with a default mapping are dispatched', function () {
    Queue::fake();
    Carbon::setTestNow('2026-11-12 12:00:00');
    [$eligible] = scheduledImportSource();
    [$notDue] = scheduledImportSource(['last_scheduled_at' => now()->subMinutes(30)]);
    [$disabled] = scheduledImportSource(['automatic_import_enabled' => false]);
    [$inactive] = scheduledImportSource(['is_active' => false]);
    [$unapproved] = scheduledImportSource();
    $unapproved->revokeApproval();
    [$upload] = scheduledImportSource([
        'transport' => ImportTransport::Upload,
        'endpoint_url' => null,
        'automatic_import_enabled' => true,
    ]);
    [$locked] = scheduledImportSource();
    $sourceLock = Cache::lock(VacancyImportRunner::sourceLockKey($locked->id), VacancyImportRunner::SOURCE_LOCK_SECONDS);
    expect($sourceLock->get())->toBeTrue();

    try {
        $count = app(DueImportDispatcher::class)->dispatch(now());
    } finally {
        $sourceLock->release();
    }

    expect($count)->toBe(1)
        ->and($eligible->fresh()->last_scheduled_at->equalTo(now()))->toBeTrue()
        ->and($notDue->fresh()->last_scheduled_at->equalTo(now()->subMinutes(30)))->toBeTrue()
        ->and($disabled->fresh()->last_scheduled_at)->toBeNull()
        ->and($inactive->fresh()->last_scheduled_at)->toBeNull()
        ->and($unapproved->fresh()->last_scheduled_at)->toBeNull()
        ->and($upload->fresh()->last_scheduled_at)->toBeNull()
        ->and($locked->fresh()->last_scheduled_at)->toBeNull();
    Queue::assertPushed(RunScheduledImport::class, 1);
    Queue::assertPushed(fn (RunScheduledImport $job): bool => $job->importSourceId === $eligible->id && $job->queue === 'imports');
});

test('all supported frequencies use their exact due boundary', function (ImportScheduleFrequency $frequency, string $lastScheduled, bool $due) {
    $now = Carbon::parse('2026-11-12 12:00:00');

    expect($frequency->isDue(Carbon::parse($lastScheduled), $now))->toBe($due);
})->with([
    'hourly due' => [ImportScheduleFrequency::Hourly, '2026-11-12 11:00:00', true],
    'hourly early' => [ImportScheduleFrequency::Hourly, '2026-11-12 11:00:01', false],
    'six hourly due' => [ImportScheduleFrequency::EverySixHours, '2026-11-12 06:00:00', true],
    'six hourly early' => [ImportScheduleFrequency::EverySixHours, '2026-11-12 06:00:01', false],
    'daily due' => [ImportScheduleFrequency::Daily, '2026-11-11 12:00:00', true],
    'daily early' => [ImportScheduleFrequency::Daily, '2026-11-11 12:00:01', false],
]);

test('the scheduler dispatches queue work without executing imports inline', function () {
    Queue::fake();
    [$source] = scheduledImportSource();

    Artisan::call('imports:dispatch-due');

    Queue::assertPushed(RunScheduledImport::class, fn (RunScheduledImport $job): bool => $job->importSourceId === $source->id);
    expect(Import::count())->toBe(0)
        ->and(Vacancy::count())->toBe(0)
        ->and(Artisan::call('schedule:list'))->toBe(0)
        ->and(Artisan::output())->toContain('imports:dispatch-due', 'queue:work');
});

test('a scheduled import job executes the generic runner successfully', function () {
    [$source] = scheduledImportSource();

    app()->call([new RunScheduledImport($source->id), 'handle']);

    $run = Import::sole();
    expect($run->status)->toBe(ImportStatus::Completed)
        ->and($run->execution_origin)->toBe(ImportExecutionOrigin::Scheduled)
        ->and($run->imported_rows)->toBe(1)
        ->and(Vacancy::sole()->source_reference)->toBe('AUTO-1')
        ->and($source->fresh()->last_imported_at)->not->toBeNull();
});

test('failed reads are recorded safely and make the queued attempt retryable', function () {
    [$source] = scheduledImportSource();
    Http::swap(new HttpFactory);
    Http::fake([$source->endpoint_url => Http::response('unavailable', 503)]);

    expect(fn () => app()->call([new RunScheduledImport($source->id), 'handle']))
        ->toThrow(RuntimeException::class, 'failed safely');

    expect(Import::sole()->status)->toBe(ImportStatus::Failed)
        ->and(data_get(Import::sole()->mapping, 'snapshot.state'))->toBe('read_or_parse_failed')
        ->and(Vacancy::count())->toBe(0);
});

test('persistence failures are recorded before the job requests a retry', function () {
    [$source] = scheduledImportSource([
        'configuration' => ['snapshot_reconciliation' => ['enabled' => true]],
    ]);
    Event::listen('eloquent.creating: '.Vacancy::class, fn () => throw new RuntimeException('Test persistence failure'));

    try {
        expect(fn () => app()->call([new RunScheduledImport($source->id), 'handle']))
            ->toThrow(RuntimeException::class, 'failed safely');
    } finally {
        Event::forget('eloquent.creating: '.Vacancy::class);
    }

    expect(Import::sole()->status)->toBe(ImportStatus::Failed)
        ->and(Import::sole()->failed_rows)->toBe(1)
        ->and(Vacancy::count())->toBe(0);
});

test('same source contention is skipped while different sources execute independently', function () {
    [$first] = scheduledImportSource([], [['id' => 'SHARED', 'title' => 'Eerste bron']]);
    [$second] = scheduledImportSource([], [['id' => 'SHARED', 'title' => 'Tweede bron']]);
    $lock = Cache::lock('imports:source:'.$first->id, VacancyImportRunner::SOURCE_LOCK_SECONDS);
    expect($lock->get())->toBeTrue();

    try {
        app()->call([new RunScheduledImport($first->id), 'handle']);
        app()->call([new RunScheduledImport($second->id), 'handle']);
    } finally {
        $lock->release();
    }

    expect(Import::where('import_source_id', $first->id)->sole()->status)->toBe(ImportStatus::Skipped)
        ->and(Import::where('import_source_id', $second->id)->sole()->status)->toBe(ImportStatus::Completed)
        ->and(Vacancy::where('import_source_id', $first->id)->count())->toBe(0)
        ->and(Vacancy::where('import_source_id', $second->id)->count())->toBe(1);
});

test('bounded jobs are unique per source and preserve the timeout lock retry invariant', function () {
    $job = new RunScheduledImport(42);

    expect($job)->toBeInstanceOf(ShouldBeUnique::class)
        ->and($job->uniqueId())->toBe('42')
        ->and($job->tries)->toBe(2)
        ->and($job->timeout)->toBe(600)
        ->and($job->backoff())->toBe([60])
        ->and($job->failOnTimeout)->toBeTrue()
        ->and(RunScheduledImport::TIMEOUT_SECONDS)->toBeLessThan(VacancyImportRunner::SOURCE_LOCK_SECONDS)
        ->and(VacancyImportRunner::SOURCE_LOCK_SECONDS)->toBeLessThan(config('queue.connections.database.retry_after'));
});

test('a retry is idempotent and updates instead of duplicating source vacancies', function () {
    [$source] = scheduledImportSource();
    $job = new RunScheduledImport($source->id);

    app()->call([$job, 'handle']);
    app()->call([$job, 'handle']);

    expect(Vacancy::where('import_source_id', $source->id)->count())->toBe(1)
        ->and(Import::where('import_source_id', $source->id)->count())->toBe(2)
        ->and(Import::where('import_source_id', $source->id)->latest('id')->first()->updated_rows)->toBe(1);
});

test('failed scheduled snapshots never reconcile existing vacancies', function () {
    [$source] = scheduledImportSource([
        'configuration' => ['snapshot_reconciliation' => ['enabled' => true]],
    ], [
        ['id' => 'A', 'title' => 'Vacature A'],
        ['id' => 'B', 'title' => 'Vacature B'],
    ]);
    $job = new RunScheduledImport($source->id);
    app()->call([$job, 'handle']);
    Http::swap(new HttpFactory);
    Http::fake([$source->endpoint_url => Http::response('broken', 500)]);

    expect(fn () => app()->call([$job, 'handle']))->toThrow(RuntimeException::class);

    expect(Vacancy::where('import_source_id', $source->id)->whereNotNull('missing_since')->count())->toBe(0)
        ->and(Vacancy::where('import_source_id', $source->id)->count())->toBe(2)
        ->and(Import::latest('id')->first()->status)->toBe(ImportStatus::Failed);
});

test('sources disabled after dispatch are ignored safely by the job', function () {
    [$source] = scheduledImportSource();
    $source->update(['automatic_import_enabled' => false]);

    app()->call([new RunScheduledImport($source->id), 'handle']);

    expect(Import::count())->toBe(0)->and(Vacancy::count())->toBe(0);
});
