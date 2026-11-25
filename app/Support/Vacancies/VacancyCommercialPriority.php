<?php

namespace App\Support\Vacancies;

use App\Contracts\VacancyPackagePriorityProvider;
use App\Models\Vacancy;
use Illuminate\Database\Eloquent\Builder;

class VacancyCommercialPriority
{
    public function __construct(
        private readonly VacancyPackagePriorityProvider $packagePriority,
    ) {}

    /** @param Builder<Vacancy> $query */
    public function addSelects(Builder $query): Builder
    {
        $vacancyTable = $query->getModel()->getTable();
        $packageExpression = $this->packagePriority->queryExpression($vacancyTable);
        $packageBindings = $this->packagePriority->queryBindings();
        $companyExpression = "COALESCE((SELECT companies.commercial_priority FROM companies WHERE companies.id = {$vacancyTable}.company_id), 0)";

        if ($query->getQuery()->columns === null) {
            $query->select("{$vacancyTable}.*");
        }

        return $query
            ->selectRaw("({$packageExpression}) AS package_priority", $packageBindings)
            ->selectRaw("{$companyExpression} AS company_priority")
            ->selectRaw("{$vacancyTable}.commercial_priority_adjustment AS vacancy_adjustment")
            ->selectRaw(
                "(({$packageExpression}) + {$companyExpression} + {$vacancyTable}.commercial_priority_adjustment) AS effective_priority",
                $packageBindings,
            );
    }

    /** @return array{package: int, company: int, vacancy: int, effective: int} */
    public function components(Vacancy $vacancy): array
    {
        $package = array_key_exists('package_priority', $vacancy->getAttributes())
            ? (int) $vacancy->getAttribute('package_priority')
            : $this->packagePriority->priorityFor($vacancy);
        $company = array_key_exists('company_priority', $vacancy->getAttributes())
            ? (int) $vacancy->getAttribute('company_priority')
            : (int) ($vacancy->relationLoaded('company')
                ? $vacancy->company?->commercial_priority
                : $vacancy->company()->value('commercial_priority'));
        $adjustment = (int) $vacancy->commercial_priority_adjustment;

        return [
            'package' => $package,
            'company' => $company,
            'vacancy' => $adjustment,
            'effective' => $package + $company + $adjustment,
        ];
    }
}
