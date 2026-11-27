<?php

namespace App\Actions\Orders;

use App\Actions\Placements\RecordPlacementCreditEvent;
use App\Enums\PlacementCreditEventType;
use App\Models\OrderOperation;
use App\Models\PlacementCredit;

class ApplyRefundToPlacementCredits
{
    public function __construct(private readonly RecordPlacementCreditEvent $recordEvent) {}

    public function execute(OrderOperation $operation): bool
    {
        $requiresReview = false;
        $credits = PlacementCredit::query()
            ->where('order_id', $operation->order_id)
            ->lockForUpdate()
            ->get();

        foreach ($credits as $credit) {
            if ($credit->reversed_at !== null || $credit->review_required_at !== null) {
                $requiresReview = $requiresReview || $credit->review_required_at !== null;

                continue;
            }

            if ($credit->vacancy_id === null) {
                $credit->update([
                    'reversed_at' => now(),
                    'reversal_reason' => 'confirmed_full_refund',
                ]);
                $this->recordEvent->execute(
                    $credit,
                    PlacementCreditEventType::Reversed,
                    "credit:{$credit->id}:refund:{$operation->id}:reversed",
                    actor: $operation->operator,
                    metadata: ['order_operation_id' => $operation->id],
                );

                continue;
            }

            $requiresReview = true;
            $credit->update([
                'review_required_at' => now(),
                'review_reason' => 'confirmed_refund_with_allocated_placement',
            ]);
            $this->recordEvent->execute(
                $credit,
                PlacementCreditEventType::ReviewRequired,
                "credit:{$credit->id}:refund:{$operation->id}:review",
                $credit->vacancy,
                $operation->operator,
                ['order_operation_id' => $operation->id, 'was_active' => $credit->activated_at !== null],
            );
        }

        return $requiresReview;
    }
}
