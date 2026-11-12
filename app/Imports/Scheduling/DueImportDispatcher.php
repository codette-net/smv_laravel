<?php

namespace App\Imports\Scheduling;

use App\Imports\VacancyImportRunner;
use App\Jobs\RunScheduledImport;
use App\Models\ImportSource;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;

class DueImportDispatcher
{
    public function dispatch(?Carbon $now = null): int
    {
        $now ??= now();
        $dispatched = 0;

        ImportSource::query()
            ->eligibleForAutomaticExecution()
            ->whereHas('mappings', fn ($query) => $query->where('is_active', true)->where('is_default', true))
            ->orderBy('id')
            ->lazyById()
            ->each(function (ImportSource $source) use ($now, &$dispatched): void {
                if (! $source->isAutomaticImportDue($now) || $this->sourceIsLocked($source->id)) {
                    return;
                }

                RunScheduledImport::dispatch($source->id);
                $source->forceFill(['last_scheduled_at' => $now])->save();
                $dispatched++;
            });

        return $dispatched;
    }

    private function sourceIsLocked(int $sourceId): bool
    {
        $probe = Cache::lock(VacancyImportRunner::sourceLockKey($sourceId), 1);

        if (! $probe->get()) {
            return true;
        }

        $probe->release();

        return false;
    }
}
