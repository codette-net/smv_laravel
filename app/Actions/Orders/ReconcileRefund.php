<?php

namespace App\Actions\Orders;

use App\Enums\OrderOperationStatus;
use App\Enums\OrderOperationType;
use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Models\OrderOperation;
use App\Payments\PaymentOperationResult;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class ReconcileRefund
{
    public function __construct(private readonly ApplyRefundToPlacementCredits $applyCredits) {}

    public function execute(OrderOperation $operation, PaymentOperationResult $result): OrderOperation
    {
        return DB::transaction(function () use ($operation, $result): OrderOperation {
            $locked = OrderOperation::query()->lockForUpdate()->findOrFail($operation->id);
            if ($locked->type !== OrderOperationType::Refund) {
                throw new InvalidArgumentException('Only refund operations can be reconciled here.');
            }

            if ($locked->providerSucceeded() && $result->providerStatus !== 'succeeded') {
                return $locked;
            }

            $payment = $locked->payment()->lockForUpdate()->firstOrFail();
            $order = $locked->order()->lockForUpdate()->firstOrFail();
            if ($locked->amount_cents !== $payment->amount_cents
                || $locked->amount_cents !== $order->total_cents
                || strtoupper($locked->currency) !== strtoupper($payment->currency)) {
                throw new InvalidArgumentException('The refund operation no longer matches its immutable payment snapshot.');
            }

            $locked->update([
                'status' => $result->status,
                'provider' => $result->provider,
                'provider_reference' => $locked->provider_reference ?? $result->providerReference,
                'provider_status' => $result->providerStatus,
                'failure_code' => $result->failureCode,
                'failure_message' => $result->failureMessage,
                'metadata' => [
                    ...($locked->metadata ?? []),
                    ...$result->metadata,
                    'resulting_order_status' => $order->status->value,
                    'resulting_payment_status' => $payment->status->value,
                ],
                'completed_at' => in_array($result->status, [OrderOperationStatus::Succeeded, OrderOperationStatus::Failed], true) ? now() : null,
            ]);

            if ($result->providerStatus !== 'succeeded') {
                return $locked->fresh();
            }

            $payment->update([
                'status' => PaymentStatus::Refunded,
                'completed_at' => $payment->completed_at ?? now(),
            ]);
            $order->update(['status' => OrderStatus::Refunded]);

            $requiresReview = $this->applyCredits->execute($locked->fresh(['operator']));
            $locked->update([
                'status' => $requiresReview ? OrderOperationStatus::RequiresReview : OrderOperationStatus::Succeeded,
                'failure_code' => $requiresReview ? 'placement_review_required' : null,
                'failure_message' => $requiresReview ? 'Een toegewezen of actieve plaatsing vereist handmatige controle.' : null,
                'completed_at' => now(),
                'metadata' => [
                    ...($locked->metadata ?? []),
                    'resulting_order_status' => OrderStatus::Refunded->value,
                    'resulting_payment_status' => PaymentStatus::Refunded->value,
                    'placement_review_required' => $requiresReview,
                ],
            ]);

            return $locked->fresh();
        });
    }
}
