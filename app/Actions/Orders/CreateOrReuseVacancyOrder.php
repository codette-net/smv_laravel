<?php

namespace App\Actions\Orders;

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\User;
use App\Models\Vacancy;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class CreateOrReuseVacancyOrder
{
    public function __construct(private readonly CreateOrder $createOrder) {}

    public function execute(User $purchaser, Vacancy $vacancy): Order
    {
        return DB::transaction(function () use ($purchaser, $vacancy): Order {
            $vacancy = Vacancy::query()->lockForUpdate()->findOrFail($vacancy->id);
            $company = $vacancy->company()->firstOrFail();

            if ($company->user_id !== $purchaser->id) {
                throw new InvalidArgumentException('The purchaser does not own this vacancy.');
            }

            if ($vacancy->placement_package === null || ! $vacancy->placement_package->entersPlacementFlow()) {
                throw new InvalidArgumentException('The vacancy has no purchasable package intent.');
            }

            $existing = Order::query()
                ->where('user_id', $purchaser->id)
                ->where('company_id', $company->id)
                ->whereIn('status', [
                    OrderStatus::Pending->value,
                    OrderStatus::PendingPayment->value,
                    OrderStatus::Paid->value,
                ])
                ->whereHas('orderItems', fn ($query) => $query
                    ->where('vacancy_id', $vacancy->id)
                    ->where('product_code', $vacancy->placement_package->value))
                ->latest('id')
                ->first();

            if ($existing !== null) {
                return $existing->load(['orderItems.vacancy', 'payments']);
            }

            return $this->createOrder->execute(
                $purchaser,
                $company,
                [['package' => $vacancy->placement_package, 'vacancy' => $vacancy]],
                OrderStatus::PendingPayment,
            );
        });
    }
}
