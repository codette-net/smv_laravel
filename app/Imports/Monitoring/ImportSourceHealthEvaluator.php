<?php

namespace App\Imports\Monitoring;

use App\Enums\ImportScheduleFrequency;
use App\Enums\ImportSourceHealth;
use App\Enums\ImportStatus;
use App\Models\Import;
use App\Models\ImportSource;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class ImportSourceHealthEvaluator
{
    public const REPEATED_FAILURE_THRESHOLD = 2;

    public const STALE_PROCESSING_MINUTES = 15;

    public const COUNT_DROP_MINIMUM_BASELINE = 10;

    public function for(ImportSource $source, ?Carbon $now = null): ImportSourceHealthReport
    {
        $now ??= now();
        /** @var Collection<int, Import> $runs */
        $runs = $source->relationLoaded('recentImports')
            ? $source->recentImports->sortByDesc('id')->values()
            : $source->imports()->latest('id')->limit(10)->get();
        $lastAttempt = $runs->first();
        $lastCompleted = $runs->first(fn (Import $run): bool => $run->finished_at !== null);
        $lastSuccessful = $runs->first(fn (Import $run): bool => $run->status === ImportStatus::Completed);
        $verified = $runs->filter(fn (Import $run): bool => $run->status === ImportStatus::Completed && $run->snapshotState() === 'verified_complete')->values();
        $lastVerified = $verified->first();
        $previousVerified = $verified->get(1);
        $consecutiveFailures = $runs->takeWhile(fn (Import $run): bool => in_array($run->status, [ImportStatus::Failed, ImportStatus::Incomplete], true))->count();
        $overdueAt = $this->overdueAt($source, $lastAttempt);
        $dropPercent = $this->verifiedCountDropPercent($lastVerified, $previousVerified);

        $health = match (true) {
            ! $source->is_active => ImportSourceHealth::Disabled,
            ! $source->isEligibleForAutomaticExecution() => ImportSourceHealth::ManualOnly,
            $lastAttempt?->status === ImportStatus::Processing && $lastAttempt->started_at?->gt($now->copy()->subMinutes(self::STALE_PROCESSING_MINUTES)) => ImportSourceHealth::Running,
            $lastAttempt?->status === ImportStatus::Processing => ImportSourceHealth::Incomplete,
            $consecutiveFailures >= self::REPEATED_FAILURE_THRESHOLD => ImportSourceHealth::RepeatedlyFailing,
            $lastAttempt?->status === ImportStatus::Incomplete => ImportSourceHealth::Incomplete,
            $lastAttempt?->status === ImportStatus::Skipped => ImportSourceHealth::Skipped,
            $overdueAt?->lt($now) => ImportSourceHealth::Overdue,
            $lastAttempt === null => ImportSourceHealth::NeverRun,
            $lastAttempt->status === ImportStatus::Failed => ImportSourceHealth::RecentFailure,
            default => ImportSourceHealth::Healthy,
        };

        return new ImportSourceHealthReport($health, $lastAttempt, $lastCompleted, $lastSuccessful, $lastVerified, $overdueAt, $consecutiveFailures, $previousVerified?->total_rows, $dropPercent);
    }

    private function overdueAt(ImportSource $source, ?Import $lastAttempt): ?Carbon
    {
        if (! $source->isEligibleForAutomaticExecution() || ! $source->schedule_frequency instanceof ImportScheduleFrequency) {
            return null;
        }

        $intervalMinutes = match ($source->schedule_frequency) {
            ImportScheduleFrequency::Hourly => 60,
            ImportScheduleFrequency::EverySixHours => 360,
            ImportScheduleFrequency::Daily => 1440,
        };
        $toleranceMinutes = max(15, (int) ceil($intervalMinutes * .25));
        $anchor = $lastAttempt?->started_at ?? $source->approved_at ?? $source->created_at;

        return $anchor?->copy()->addMinutes($intervalMinutes + $toleranceMinutes);
    }

    private function verifiedCountDropPercent(?Import $current, ?Import $previous): ?float
    {
        if ($current === null || $previous === null || $previous->total_rows < self::COUNT_DROP_MINIMUM_BASELINE || $current->total_rows >= $previous->total_rows) {
            return null;
        }

        return round((($previous->total_rows - $current->total_rows) / $previous->total_rows) * 100, 1);
    }
}
