<?php

use App\Models\Company;
use App\Models\OutboundApplyClick;
use App\Models\Vacancy;
use App\Support\Analytics\OutboundApplyClickFilters;
use App\Support\Analytics\OutboundApplyClickPruner;
use App\Support\Analytics\OutboundApplyClickReport;
use Carbon\Carbon;
use Carbon\CarbonImmutable;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;

uses(RefreshDatabase::class);

beforeEach(function () {
    Carbon::setTestNow('2026-11-20 12:00:00 UTC');
});

afterEach(function () {
    Carbon::setTestNow();
});

function retentionVacancy(): Vacancy
{
    return Vacancy::factory()->for(Company::factory())->create();
}

function retentionClick(Vacancy $vacancy, string $clickedAt): OutboundApplyClick
{
    $click = new OutboundApplyClick;
    $click->vacancy()->associate($vacancy);
    $click->clicked_at = CarbonImmutable::parse($clickedAt, 'UTC');
    $click->save();

    return $click;
}

test('the command deletes only events strictly older than the 24 month UTC cutoff in bounded batches', function () {
    $firstVacancy = retentionVacancy();
    $secondVacancy = retentionVacancy();
    $expiredFirst = retentionClick($firstVacancy, '2024-11-20 11:59:59');
    $boundary = retentionClick($firstVacancy, '2024-11-20 12:00:00');
    $inside = retentionClick($firstVacancy, '2024-11-20 12:00:01');
    $expiredSecond = retentionClick($secondVacancy, '2023-06-01 00:00:00');

    $pruner = app(OutboundApplyClickPruner::class);
    expect($pruner->prune($pruner->cutoff(), batchSize: 1))->toBe(2)
        ->and(OutboundApplyClick::query()->pluck('id')->all())->toBe([$boundary->id, $inside->id])
        ->and(OutboundApplyClick::query()->whereKey([$expiredFirst->id, $expiredSecond->id])->count())->toBe(0);
});

test('the default command reports the actual deleted count', function () {
    $vacancy = retentionVacancy();
    retentionClick($vacancy, '2024-11-20 11:59:59');
    retentionClick($vacancy, '2024-11-20 11:00:00');
    retentionClick($vacancy, '2024-11-20 12:00:00');
    Log::spy();

    expect(Artisan::call('analytics:prune-outbound-clicks'))->toBe(0)
        ->and(Artisan::output())->toContain('Deleted 2 outbound apply click event(s)')
        ->and(OutboundApplyClick::query()->count())->toBe(1);

    Log::shouldHaveReceived('info')->once()->withArgs(
        fn (string $message, array $context): bool => $message === 'Outbound apply click retention completed.'
            && $context['cutoff_utc'] === '2024-11-20T12:00:00+00:00'
            && $context['eligible_count'] === 2
            && $context['deleted_count'] === 2
            && $context['dry_run'] === false
    );
});

test('dry run reports eligible events without changing data', function () {
    $vacancy = retentionVacancy();
    retentionClick($vacancy, '2024-11-20 11:59:59');
    retentionClick($vacancy, '2023-01-01 00:00:00');
    retentionClick($vacancy, '2024-11-20 12:00:00');

    expect(Artisan::call('analytics:prune-outbound-clicks', ['--dry-run' => true]))->toBe(0)
        ->and(Artisan::output())->toContain('Dry run: 2 outbound apply click event(s)', 'nothing deleted')
        ->and(OutboundApplyClick::query()->count())->toBe(3);
});

test('the cutoff converts the reference instant to UTC before subtracting 24 calendar months', function () {
    $reference = CarbonImmutable::parse('2026-11-20 01:30:00', 'Europe/Paris');

    expect(app(OutboundApplyClickPruner::class)->cutoff($reference)->format('Y-m-d H:i:s P'))
        ->toBe('2024-11-20 00:30:00 +00:00');
});

test('reports return retained values and zero for pruned historical periods', function () {
    $vacancy = retentionVacancy();
    retentionClick($vacancy, '2024-11-19 12:00:00');
    retentionClick($vacancy, '2026-11-19 12:00:00');

    Artisan::call('analytics:prune-outbound-clicks');

    $report = app(OutboundApplyClickReport::class);
    $expiredPeriod = OutboundApplyClickFilters::fromArray([
        'date_from' => '2024-11-19',
        'date_to' => '2024-11-19',
    ]);
    $retainedPeriod = OutboundApplyClickFilters::fromArray([
        'date_from' => '2026-11-19',
        'date_to' => '2026-11-19',
    ]);

    expect($report->total($expiredPeriod))->toBe(0)
        ->and($report->vacancies($expiredPeriod)->count())->toBe(0)
        ->and($report->total($retainedPeriod))->toBe(1)
        ->and($report->vacancies($retainedPeriod)->sole()->is($vacancy))->toBeTrue();
});

test('the command exits nonzero and logs an operational failure', function () {
    $pruner = Mockery::mock(OutboundApplyClickPruner::class);
    $pruner->shouldReceive('cutoff')->once()->andThrow(new RuntimeException('database unavailable'));
    app()->instance(OutboundApplyClickPruner::class, $pruner);
    Log::spy();

    expect(Artisan::call('analytics:prune-outbound-clicks'))->toBe(1)
        ->and(Artisan::output())->toContain('retention failed');

    Log::shouldHaveReceived('error')->once()->withArgs(
        fn (string $message, array $context): bool => $message === 'Outbound apply click retention failed.'
            && $context['exception'] === RuntimeException::class
    );
});

test('the outbound click prune command is registered as a daily UTC schedule', function () {
    expect(Artisan::call('schedule:list'))->toBe(0)
        ->and(Artisan::output())->toContain(
            'analytics:prune-outbound-clicks',
            '30 2 * * *',
        );

    $event = collect(app(Schedule::class)->events())
        ->first(fn ($event): bool => str_contains($event->command, 'analytics:prune-outbound-clicks'));

    expect($event)->not->toBeNull()
        ->and($event->expression)->toBe('30 2 * * *')
        ->and($event->timezone)->toBe('UTC');
});
