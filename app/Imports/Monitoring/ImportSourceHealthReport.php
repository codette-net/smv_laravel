<?php

namespace App\Imports\Monitoring;

use App\Enums\ImportSourceHealth;
use App\Models\Import;
use Illuminate\Support\Carbon;

final readonly class ImportSourceHealthReport
{
    public function __construct(
        public ImportSourceHealth $health,
        public ?Import $lastAttempt,
        public ?Import $lastCompleted,
        public ?Import $lastSuccessful,
        public ?Import $lastVerified,
        public ?Carbon $overdueAt,
        public int $consecutiveFailures,
        public ?int $previousVerifiedTotal,
        public ?float $verifiedCountDropPercent,
    ) {}

    public function hasSubstantialCountDrop(): bool
    {
        return $this->verifiedCountDropPercent !== null && $this->verifiedCountDropPercent >= 50;
    }
}
