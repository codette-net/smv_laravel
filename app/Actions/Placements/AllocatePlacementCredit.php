<?php

namespace App\Actions\Placements;

use App\Enums\AdvertisingPackage;
use App\Enums\PlacementCreditEventType;
use App\Enums\VacancySource;
use App\Enums\VacancyStatus;
use App\Models\PlacementCredit;
use App\Models\User;
use App\Models\Vacancy;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class AllocatePlacementCredit
{
    public function __construct(
        private readonly RecordPlacementCreditEvent $recordEvent,
        private readonly ActivatePlacementCredit $activate,
    ) {}

    public function execute(User $actor, PlacementCredit $credit, Vacancy $vacancy): PlacementCredit
    {
        return $this->allocate($credit, $vacancy, $actor, allowPending: false, requireOrderLink: false);
    }

    public function allocateLinked(PlacementCredit $credit, Vacancy $vacancy): PlacementCredit
    {
        return $this->allocate($credit, $vacancy, null, allowPending: true, requireOrderLink: true);
    }

    private function allocate(
        PlacementCredit $credit,
        Vacancy $vacancy,
        ?User $actor,
        bool $allowPending,
        bool $requireOrderLink,
    ): PlacementCredit {
        $allocated = DB::transaction(function () use ($credit, $vacancy, $actor, $allowPending, $requireOrderLink): PlacementCredit {
            $lockedCredit = PlacementCredit::query()->lockForUpdate()->findOrFail($credit->id);
            $lockedVacancy = Vacancy::query()->lockForUpdate()->findOrFail($vacancy->id);

            if ($actor !== null && $lockedCredit->company()->value('user_id') !== $actor->id) {
                throw new InvalidArgumentException('U mag dit plaatsingsrecht niet gebruiken.');
            }

            if ($lockedCredit->company_id !== $lockedVacancy->company_id) {
                throw new InvalidArgumentException('Het plaatsingsrecht en de vacature moeten bij hetzelfde bedrijf horen.');
            }

            if ($lockedCredit->reversed_at !== null) {
                throw new InvalidArgumentException('Dit plaatsingsrecht is niet meer beschikbaar.');
            }

            if ($lockedCredit->vacancy_id !== null) {
                if ($lockedCredit->vacancy_id === $lockedVacancy->id) {
                    return $lockedCredit;
                }

                throw new InvalidArgumentException('Dit plaatsingsrecht is al gebruikt.');
            }

            $allowedStatuses = $allowPending
                ? [VacancyStatus::Draft, VacancyStatus::Pending, VacancyStatus::Active]
                : [VacancyStatus::Draft];
            $package = AdvertisingPackage::tryFrom($lockedCredit->product_code);

            if ($lockedVacancy->source !== VacancySource::Manual
                || $lockedVacancy->import_source_id !== null
                || $lockedVacancy->is_filled
                || ! in_array($lockedVacancy->status, $allowedStatuses, true)
                || $package === null
                || ($lockedVacancy->placement_package !== null && $lockedVacancy->placement_package !== $package)) {
                throw new InvalidArgumentException('Deze vacature kan dit plaatsingsrecht niet gebruiken.');
            }

            if ($requireOrderLink && $lockedCredit->orderItem()->value('vacancy_id') !== $lockedVacancy->id) {
                throw new InvalidArgumentException('Het plaatsingsrecht is niet voor deze betaalde vacature gekocht.');
            }

            if (PlacementCredit::query()->where('vacancy_id', $lockedVacancy->id)->exists()) {
                throw new InvalidArgumentException('Deze vacature heeft al een plaatsingsrecht.');
            }

            $lockedCredit->update([
                'vacancy_id' => $lockedVacancy->id,
                'allocated_at' => now(),
            ]);

            if ($lockedVacancy->placement_package === null) {
                $lockedVacancy->placement_package = $package;
                $lockedVacancy->saveQuietly();
            }

            $this->recordEvent->execute(
                $lockedCredit,
                PlacementCreditEventType::Allocated,
                "credit:{$lockedCredit->id}:allocated",
                $lockedVacancy,
                $actor,
            );

            return $lockedCredit->fresh();
        });

        if ($vacancy->fresh()->status === VacancyStatus::Active) {
            return $this->activate->execute($vacancy) ?? $allocated;
        }

        return $allocated;
    }
}
