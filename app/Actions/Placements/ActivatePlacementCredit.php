<?php

namespace App\Actions\Placements;

use App\Enums\CompanyStatus;
use App\Enums\PlacementCreditEventType;
use App\Enums\VacancyStatus;
use App\Models\PlacementCredit;
use App\Models\Vacancy;
use Illuminate\Support\Facades\DB;

class ActivatePlacementCredit
{
    public function __construct(private readonly RecordPlacementCreditEvent $recordEvent) {}

    public function execute(Vacancy $vacancy): ?PlacementCredit
    {
        return DB::transaction(function () use ($vacancy): ?PlacementCredit {
            $lockedVacancy = Vacancy::query()->lockForUpdate()->find($vacancy->id);
            if ($lockedVacancy === null
                || $lockedVacancy->status !== VacancyStatus::Active
                || $lockedVacancy->is_filled
                || $lockedVacancy->company()->first()?->status !== CompanyStatus::Active) {
                return null;
            }

            $credit = PlacementCredit::query()
                ->where('vacancy_id', $lockedVacancy->id)
                ->whereNull('reversed_at')
                ->whereNull('review_required_at')
                ->lockForUpdate()
                ->first();

            if ($credit === null) {
                return $credit;
            }

            if ($credit->activated_at !== null) {
                if ($credit->expires_at !== null
                    && ($lockedVacancy->expires_at === null || $lockedVacancy->expires_at->gt($credit->expires_at))) {
                    $lockedVacancy->expires_at = $credit->expires_at;
                    $lockedVacancy->saveQuietly();
                }

                return $credit;
            }

            $activation = $lockedVacancy->published_at ?? now();
            $expiry = $activation->copy()->addDays($credit->duration_days_snapshot);

            $credit->update([
                'activated_at' => $activation,
                'expires_at' => $expiry,
            ]);

            if ($lockedVacancy->expires_at === null || $lockedVacancy->expires_at->gt($expiry)) {
                $lockedVacancy->expires_at = $expiry;
                $lockedVacancy->saveQuietly();
            }

            $this->recordEvent->execute(
                $credit,
                PlacementCreditEventType::Activated,
                "credit:{$credit->id}:activated",
                $lockedVacancy,
                metadata: ['duration_days' => $credit->duration_days_snapshot, 'expires_at' => $expiry->toISOString()],
                occurredAt: $activation,
            );

            return $credit->fresh();
        });
    }
}
