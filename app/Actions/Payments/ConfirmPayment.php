<?php

namespace App\Actions\Payments;

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Models\Payment;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class ConfirmPayment
{
    public function execute(Payment $payment): Payment
    {
        return DB::transaction(function () use ($payment): Payment {
            $lockedPayment = Payment::query()->lockForUpdate()->findOrFail($payment->id);
            $order = $lockedPayment->order()->lockForUpdate()->firstOrFail();

            if ($lockedPayment->status === PaymentStatus::Paid) {
                if ($order->status !== OrderStatus::Paid) {
                    $order->update(['status' => OrderStatus::Paid]);
                }

                return $lockedPayment->fresh();
            }

            if ($order->status === OrderStatus::Paid) {
                throw new InvalidArgumentException('A different payment already paid this order.');
            }

            if (! in_array($lockedPayment->status, [PaymentStatus::Pending, PaymentStatus::Open], true)) {
                throw new InvalidArgumentException('Only a pending payment can be confirmed.');
            }

            $lockedPayment->update([
                'status' => PaymentStatus::Paid,
                'paid_at' => $lockedPayment->paid_at ?? now(),
                'completed_at' => $lockedPayment->completed_at ?? now(),
            ]);
            $order->update(['status' => OrderStatus::Paid]);

            return $lockedPayment->fresh();
        });
    }
}
