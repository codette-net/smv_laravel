<?php

namespace App\Imports\Validation;

use App\Imports\Mapping\NormalizedVacancyData;

final readonly class ImportRecordOutcome
{
    public function __construct(public NormalizedVacancyData $data, public array $warnings = [], public array $errors = [], public array $unresolved = [], public array $resolved = []) {}

    public function status(): string
    {
        return $this->errors !== [] ? 'error' : ($this->unresolved !== [] ? 'needs_resolution' : ($this->warnings !== [] ? 'warning' : 'ready'));
    }

    public function canImport(): bool
    {
        return ! in_array($this->status(), ['error', 'needs_resolution'], true);
    }
}
