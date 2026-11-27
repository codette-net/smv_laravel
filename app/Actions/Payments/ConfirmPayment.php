<?php

namespace App\Actions\Payments;

use App\Actions\Placements\GrantPaidOrderPlacementCredits;
use App\Enums\OrderOperationStatus;
use App\Enums\OrderOperationType;
use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Models\Payment;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class ConfirmPayment
{
    public function __construct(private readonly GrantPaidOrderPlacementCredits $grantCredits) {}

    public function execute(Payment $payment, bool $providerAuthoritative = false): Payment
    {
        return DB::transaction(function () use ($payment, $providerAuthoritative): Payment {
            $lockedPayment = Payment::query()->lockForUpdate()->findOrFail($payment->id);
            $order = $lockedPayment->order()->lockForUpdate()->firstOrFail();

            if ($providerAuthoritative
                && $lockedPayment->status === PaymentStatus::Refunded
                && $order->status === OrderStatus::Refunded) {
                return $lockedPayment;
            }

            if ($providerAuthoritative && $order->status === OrderStatus::Cancelled) {
                $cancellation = $order->operations()
                    ->where('type', OrderOperationType::Cancellation->value)
                    ->lockForUpdate()
                    ->first();
                $cancellation?->update([
                    'status' => OrderOperationStatus::RequiresReview,
                    'failure_code' => 'late_payment_after_cancellation',
                    'failure_message' => 'Een geldige betaling is na de annulering bevestigd.',
                    'completed_at' => now(),
                    'metadata' => [
                        ...($cancellation->metadata ?? []),
                        'resulting_order_status' => OrderStatus::Paid->value,
                        'resulting_payment_status' => PaymentStatus::Paid->value,
                    ],
                ]);
            }

            if ($lockedPayment->status === PaymentStatus::Paid) {
                if ($order->status !== OrderStatus::Paid) {
                    $order->update(['status' => OrderStatus::Paid]);
                }

                $this->grantCredits->execute($order);

                return $lockedPayment->fresh();
            }

            if ($order->status === OrderStatus::Paid) {
                if ($providerAuthoritative) {
                    $lockedPayment->update([
                        'status' => PaymentStatus::Paid,
                        'paid_at' => $lockedPayment->paid_at ?? now(),
                        'completed_at' => $lockedPayment->completed_at ?? now(),
                    ]);

                    $this->grantCredits->execute($order);

                    return $lockedPayment->fresh();
                }

                throw new InvalidArgumentException('A different payment already paid this order.');
            }

            $confirmable = [PaymentStatus::Pending, PaymentStatus::Open];
            if ($providerAuthoritative) {
                $confirmable = [
                    ...$confirmable,
                    PaymentStatus::Failed,
                    PaymentStatus::Cancelled,
                    PaymentStatus::Expired,
                ];
            }

            if (! in_array($lockedPayment->status, $confirmable, true)) {
                throw new InvalidArgumentException('Only a pending payment can be confirmed.');
            }

            $lockedPayment->update([
                'status' => PaymentStatus::Paid,
                'paid_at' => $lockedPayment->paid_at ?? now(),
                'completed_at' => $lockedPayment->completed_at ?? now(),
            ]);
            $order->update(['status' => OrderStatus::Paid]);
            $this->grantCredits->execute($order);

            return $lockedPayment->fresh();
        });
    }
}
