<?php

namespace App\Imports\Mapping;

final readonly class NormalizedVacancyData
{
    /** @param array<string, mixed> $values */
    public function __construct(public array $values) {}

    public function get(string $key, mixed $default = null): mixed
    {
        return data_get($this->values, $key, $default);
    }

    public function toArray(): array
    {
        return $this->values;
    }
}
