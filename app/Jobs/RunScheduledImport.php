<?php

namespace App\Jobs;

use App\Enums\ImportStatus;
use App\Imports\VacancyImportRunner;
use App\Models\ImportSource;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use RuntimeException;

class RunScheduledImport implements ShouldBeUnique, ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public const TIMEOUT_SECONDS = 600;

    public const UNIQUE_SECONDS = 960;

    public int $tries = 2;

    public int $timeout = self::TIMEOUT_SECONDS;

    public int $uniqueFor = self::UNIQUE_SECONDS;

    public bool $failOnTimeout = true;

    public function __construct(public readonly int $importSourceId)
    {
        $this->onQueue('imports');
    }

    /** @return list<int> */
    public function backoff(): array
    {
        return [60];
    }

    public function uniqueId(): string
    {
        return (string) $this->importSourceId;
    }

    public function handle(VacancyImportRunner $runner): void
    {
        $source = ImportSource::find($this->importSourceId);

        if (! $source?->isEligibleForAutomaticExecution()) {
            return;
        }

        $mapping = $source->mappings()
            ->where('is_active', true)
            ->where('is_default', true)
            ->orderBy('id')
            ->first();

        if ($mapping === null) {
            throw new RuntimeException('The import source has no active default mapping.');
        }

        $run = $runner->run($mapping);

        if ($run->status === ImportStatus::Failed) {
            throw new RuntimeException('The scheduled import failed safely.');
        }
    }
}
