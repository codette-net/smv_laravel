<?php

namespace App\Support\Commerce;

use App\Contracts\VacancyPackagePriorityProvider;
use App\Enums\CompanyStatus;
use App\Enums\VacancyStatus;
use App\Models\PlacementCredit;
use App\Models\Vacancy;

class EntitledVacancyPackagePriority implements VacancyPackagePriorityProvider
{
    public function queryExpression(string $vacancyTable): string
    {
        return "COALESCE((SELECT placement_credits.commercial_priority_snapshot
            FROM placement_credits
            WHERE placement_credits.vacancy_id = {$vacancyTable}.id
              AND placement_credits.reversed_at IS NULL
              AND placement_credits.review_required_at IS NULL
              AND placement_credits.activated_at IS NOT NULL
              AND placement_credits.activated_at <= ?
              AND placement_credits.expires_at >= ?
              AND {$vacancyTable}.status = ?
              AND {$vacancyTable}.is_filled = 0
              AND ({$vacancyTable}.published_at IS NULL OR {$vacancyTable}.published_at <= ?)
              AND ({$vacancyTable}.deadline_at IS NULL OR {$vacancyTable}.deadline_at >= ?)
              AND ({$vacancyTable}.expires_at IS NULL OR {$vacancyTable}.expires_at >= ?)
              AND EXISTS (SELECT 1 FROM companies WHERE companies.id = {$vacancyTable}.company_id AND companies.status = ?)
            ORDER BY placement_credits.id DESC
            LIMIT 1), 0)";
    }

    public function queryBindings(): array
    {
        $now = now();

        return [
            $now,
            $now,
            VacancyStatus::Active->value,
            $now,
            $now,
            $now,
            CompanyStatus::Active->value,
        ];
    }

    public function priorityFor(Vacancy $vacancy): int
    {
        $now = now();
        $publiclyEligible = $vacancy->status === VacancyStatus::Active
            && ! $vacancy->is_filled
            && ($vacancy->published_at === null || $vacancy->published_at->lte($now))
            && ($vacancy->deadline_at === null || $vacancy->deadline_at->gte($now))
            && ($vacancy->expires_at === null || $vacancy->expires_at->gte($now))
            && $vacancy->company()->where('status', CompanyStatus::Active->value)->exists();

        if (! $publiclyEligible) {
            return 0;
        }

        return (int) PlacementCredit::query()
            ->where('vacancy_id', $vacancy->id)
            ->active()
            ->value('commercial_priority_snapshot');
    }
}
