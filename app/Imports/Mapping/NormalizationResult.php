<?php

namespace App\Imports\Mapping;

final readonly class NormalizationResult
{
    /** @param list<string> $warnings */
    /** @param list<string> $errors */
    public function __construct(public NormalizedVacancyData $data, public array $warnings = [], public array $errors = []) {}

    public function canContinue(): bool
    {
        return $this->errors === [];
    }
}
