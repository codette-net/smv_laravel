<?php

namespace App\Actions\Placements;

use App\Enums\PlacementCreditEventType;
use App\Models\PlacementCredit;
use App\Models\PlacementCreditEvent;
use App\Models\User;
use App\Models\Vacancy;

class RecordPlacementCreditEvent
{
    /** @param array<string, mixed> $metadata */
    public function execute(
        PlacementCredit $credit,
        PlacementCreditEventType $type,
        string $deduplicationKey,
        ?Vacancy $vacancy = null,
        ?User $actor = null,
        array $metadata = [],
        mixed $occurredAt = null,
    ): PlacementCreditEvent {
        return PlacementCreditEvent::query()->firstOrCreate([
            'deduplication_key' => $deduplicationKey,
        ], [
            'placement_credit_id' => $credit->id,
            'vacancy_id' => $vacancy?->id,
            'actor_user_id' => $actor?->id,
            'event_type' => $type,
            'metadata' => $metadata,
            'occurred_at' => $occurredAt ?? now(),
        ]);
    }
}
