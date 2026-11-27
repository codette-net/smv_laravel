<?php

use App\Models\Company;
use App\Models\OutboundApplyClick;
use App\Models\Vacancy;
use App\Models\VacancyDetailView;
use App\Support\Analytics\VacancyDetailViewPruner;
use Carbon\Carbon;
use Carbon\CarbonImmutable;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;

uses(RefreshDatabase::class);

beforeEach(function () {
    Carbon::setTestNow('2026-11-27 12:00:00 UTC');
});

afterEach(function () {
    Carbon::setTestNow();
});

function retentionDetailView(Vacancy $vacancy, string $viewedAt): VacancyDetailView
{
    $view = new VacancyDetailView;
    $view->vacancy()->associate($vacancy);
    $view->viewed_at = CarbonImmutable::parse($viewedAt, 'UTC');
    $view->save();

    return $view;
}

test('detail views use a strict 24 month UTC cutoff and bounded deletion batches', function () {
    $vacancy = Vacancy::factory()->for(Company::factory())->create();
    $expired = retentionDetailView($vacancy, '2024-11-27 11:59:59');
    $boundary = retentionDetailView($vacancy, '2024-11-27 12:00:00');
    $inside = retentionDetailView($vacancy, '2024-11-27 12:00:01');

    $pruner = app(VacancyDetailViewPruner::class);

    expect($pruner->prune($pruner->cutoff(), batchSize: 1))->toBe(1)
        ->and(VacancyDetailView::query()->whereKey($expired)->exists())->toBeFalse()
        ->and(VacancyDetailView::query()->pluck('id')->all())->toBe([$boundary->id, $inside->id]);
});

test('detail view dry run preserves events and outbound clicks', function () {
    $vacancy = Vacancy::factory()->for(Company::factory())->create();
    retentionDetailView($vacancy, '2024-11-27 11:59:59');
    retentionDetailView($vacancy, '2024-11-27 12:00:00');
    $click = new OutboundApplyClick;
    $click->vacancy()->associate($vacancy);
    $click->clicked_at = CarbonImmutable::parse('2023-01-01 00:00:00', 'UTC');
    $click->save();

    expect(Artisan::call('analytics:prune-vacancy-detail-views', ['--dry-run' => true]))->toBe(0)
        ->and(Artisan::output())->toContain('Dry run: 1 Vacancy detail view event(s)', 'nothing deleted')
        ->and(VacancyDetailView::query()->count())->toBe(2)
        ->and(OutboundApplyClick::query()->count())->toBe(1);
});

test('detail view pruning is registered daily in UTC without replacing click retention', function () {
    expect(Artisan::call('schedule:list'))->toBe(0)
        ->and(Artisan::output())->toContain(
            'analytics:prune-outbound-clicks',
            'analytics:prune-vacancy-detail-views',
            '35 2 * * *',
        );

    $event = collect(app(Schedule::class)->events())
        ->first(fn ($event): bool => str_contains($event->command, 'analytics:prune-vacancy-detail-views'));

    expect($event)->not->toBeNull()
        ->and($event->expression)->toBe('35 2 * * *')
        ->and($event->timezone)->toBe('UTC');
});
