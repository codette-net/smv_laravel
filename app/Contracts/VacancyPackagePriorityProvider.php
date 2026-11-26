<?php

namespace App\Contracts;

use App\Models\Vacancy;

interface VacancyPackagePriorityProvider
{
    /**
     * Return a database expression that resolves an authoritative entitled package score.
     *
     * The expression must be safe to embed in a Vacancy select and may use a correlated
     * subquery against the supplied Vacancy table.
     */
    public function queryExpression(string $vacancyTable): string;

    /** @return array<int, mixed> */
    public function queryBindings(): array;

    public function priorityFor(Vacancy $vacancy): int;
}
