<?php

namespace App\Actions\Placements;

use App\Enums\AdvertisingPackage;
use App\Enums\OrderStatus;
use App\Enums\PlacementCreditEventType;
use App\Enums\VacancySource;
use App\Enums\VacancyStatus;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\PlacementCredit;
use App\Models\Vacancy;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class GrantPaidOrderPlacementCredits
{
    public function __construct(
        private readonly RecordPlacementCreditEvent $recordEvent,
        private readonly AllocatePlacementCredit $allocate,
    ) {}

    /** @return Collection<int, PlacementCredit> */
    public function execute(Order $order): Collection
    {
        return DB::transaction(function () use ($order): Collection {
            $lockedOrder = Order::query()->lockForUpdate()->findOrFail($order->id);
            if ($lockedOrder->status !== OrderStatus::Paid) {
                return new Collection;
            }

            $credits = new Collection;
            $items = $lockedOrder->orderItems()->lockForUpdate()->get();

            foreach ($items as $item) {
                if (! $item->isPlacementProduct()) {
                    continue;
                }

                if (! $item->hasCompletePlacementEntitlementSnapshot()) {
                    throw new RuntimeException("OrderItem {$item->id} lacks an immutable placement entitlement snapshot.");
                }

                $grantCount = $item->quantity * $item->placement_quantity_snapshot;
                for ($sequence = 1; $sequence <= $grantCount; $sequence++) {
                    $credit = PlacementCredit::query()->firstOrCreate([
                        'order_item_id' => $item->id,
                        'grant_sequence' => $sequence,
                    ], [
                        'company_id' => $lockedOrder->company_id,
                        'order_id' => $lockedOrder->id,
                        'package_id' => $item->package_id,
                        'product_code' => $item->product_code,
                        'package_name_snapshot' => $item->title_snapshot,
                        'package_type_snapshot' => $item->package_type_snapshot,
                        'duration_days_snapshot' => $item->vacancy_duration_days_snapshot,
                        'commercial_priority_snapshot' => $item->commercial_priority_snapshot,
                        'benefits_snapshot' => $item->entitlement_snapshot,
                        'granted_at' => now(),
                    ]);

                    $this->recordEvent->execute(
                        $credit,
                        PlacementCreditEventType::Granted,
                        "credit:{$credit->id}:granted",
                        metadata: [
                            'order_id' => $lockedOrder->id,
                            'order_item_id' => $item->id,
                            'grant_sequence' => $sequence,
                        ],
                        occurredAt: $credit->granted_at,
                    );
                    $credits->push($credit);
                }

                $this->autoAllocateLinkedVacancy($item, $credits);
            }

            return $credits;
        });
    }

    /** @param Collection<int, PlacementCredit> $credits */
    private function autoAllocateLinkedVacancy(OrderItem $item, Collection $credits): void
    {
        if ($item->vacancy_id === null || PlacementCredit::query()->where('vacancy_id', $item->vacancy_id)->exists()) {
            return;
        }

        $vacancy = Vacancy::query()->find($item->vacancy_id);
        $credit = $credits->first(fn (PlacementCredit $credit): bool => $credit->order_item_id === $item->id && $credit->vacancy_id === null);
        $package = AdvertisingPackage::tryFrom((string) $item->product_code);

        if ($vacancy === null
            || $credit === null
            || $package === null
            || $vacancy->source !== VacancySource::Manual
            || $vacancy->import_source_id !== null
            || $vacancy->is_filled
            || ! in_array($vacancy->status, [VacancyStatus::Draft, VacancyStatus::Pending, VacancyStatus::Active], true)
            || ($vacancy->placement_package !== null && $vacancy->placement_package !== $package)) {
            return;
        }

        $this->allocate->allocateLinked($credit, $vacancy);
    }
}
