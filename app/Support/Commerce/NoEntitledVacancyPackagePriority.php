<?php

namespace App\Support\Commerce;

use App\Contracts\VacancyPackagePriorityProvider;
use App\Models\Vacancy;

class NoEntitledVacancyPackagePriority implements VacancyPackagePriorityProvider
{
    public function queryExpression(string $vacancyTable): string
    {
        return '0';
    }

    public function queryBindings(): array
    {
        return [];
    }

    public function priorityFor(Vacancy $vacancy): int
    {
        // placement_package and paid Orders are deliberately not entitlement evidence.
        return 0;
    }
}
