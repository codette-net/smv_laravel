<?php

namespace App\Actions\Orders;

use App\Enums\OrderOperationStatus;
use App\Enums\OrderOperationType;
use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Models\Order;
use App\Models\OrderOperation;
use App\Models\Payment;
use App\Models\User;
use App\Payments\PaymentGatewayException;
use App\Payments\PaymentGatewayResolver;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use InvalidArgumentException;

class CancelUnpaidOrder
{
    public function __construct(private readonly PaymentGatewayResolver $gateways) {}

    public function execute(User $operator, Order $order, string $reason): OrderOperation
    {
        if (! $operator->can('cancel', $order)) {
            throw new AuthorizationException;
        }

        $reason = trim($reason);
        if (mb_strlen($reason) < 5 || mb_strlen($reason) > 2000) {
            throw new InvalidArgumentException('Een interne reden van 5 tot 2000 tekens is verplicht.');
        }

        [$operation, $payment] = DB::transaction(function () use ($operator, $order, $reason): array {
            $lockedOrder = Order::query()->lockForUpdate()->findOrFail($order->id);
            $existing = $lockedOrder->operations()->where('type', OrderOperationType::Cancellation->value)->lockForUpdate()->first();
            if ($existing?->status === OrderOperationStatus::Succeeded || $lockedOrder->status === OrderStatus::Cancelled) {
                return [$existing ?? $this->createOperation($lockedOrder, $operator, $reason), null];
            }

            if (in_array($lockedOrder->status, [OrderStatus::Paid, OrderStatus::Refunded], true)
                || $lockedOrder->payments()->where('status', PaymentStatus::Paid->value)->exists()) {
                throw new InvalidArgumentException('Een betaalde bestelling kan niet als onbetaald worden geannuleerd.');
            }

            if ($lockedOrder->placementCredits()->exists()) {
                throw new InvalidArgumentException('Een bestelling met plaatsingsrechten kan niet als onbetaald worden geannuleerd.');
            }

            if (! in_array($lockedOrder->status, [OrderStatus::Draft, OrderStatus::Pending, OrderStatus::PendingPayment], true)) {
                throw new InvalidArgumentException('Deze bestelling kan niet worden geannuleerd.');
            }

            $operation = $existing ?? $this->createOperation($lockedOrder, $operator, $reason);
            $pendingPayments = $lockedOrder->payments()
                ->whereIn('status', [PaymentStatus::Open->value, PaymentStatus::Pending->value])
                ->lockForUpdate()
                ->get();

            if ($pendingPayments->count() > 1) {
                $operation->update([
                    'status' => OrderOperationStatus::RequiresReview,
                    'failure_code' => 'multiple_pending_payments',
                    'failure_message' => 'Meerdere open betalingspogingen vereisen handmatige controle.',
                ]);

                return [$operation->fresh(), null];
            }

            $payment = $pendingPayments->first();
            $operation->update([
                'payment_id' => $payment?->id,
                'provider' => $payment?->provider,
                'status' => $payment === null ? OrderOperationStatus::Succeeded : OrderOperationStatus::Pending,
                'completed_at' => $payment === null ? now() : null,
                'metadata' => [
                    ...($operation->metadata ?? []),
                    'previous_payment_status' => $payment?->status->value,
                    ...($payment === null ? [
                        'resulting_order_status' => OrderStatus::Cancelled->value,
                        'resulting_payment_status' => null,
                    ] : []),
                ],
            ]);

            if ($payment === null) {
                $lockedOrder->update(['status' => OrderStatus::Cancelled]);
            }

            return [$operation->fresh(), $payment];
        });

        if (! $payment instanceof Payment) {
            return $operation;
        }

        try {
            $result = $this->gateways->resolveForProvider($payment->provider)
                ->expire($payment, $operation->idempotency_key);
        } catch (PaymentGatewayException $exception) {
            $operation->update([
                'status' => OrderOperationStatus::RequiresReview,
                'failure_code' => 'provider_outcome_unknown',
                'failure_message' => $exception->getMessage(),
            ]);

            return $operation->fresh();
        }

        return DB::transaction(function () use ($operation, $payment, $result): OrderOperation {
            $locked = OrderOperation::query()->lockForUpdate()->findOrFail($operation->id);
            $lockedOrder = $locked->order()->lockForUpdate()->firstOrFail();
            $lockedPayment = Payment::query()->lockForUpdate()->findOrFail($payment->id);

            if ($lockedOrder->status === OrderStatus::Paid || $lockedPayment->status === PaymentStatus::Paid) {
                $locked->update([
                    'status' => OrderOperationStatus::RequiresReview,
                    'provider_reference' => $result->providerReference,
                    'provider_status' => $result->providerStatus,
                    'failure_code' => 'payment_confirmed_during_cancellation',
                    'failure_message' => 'De betaling is tijdens de annulering bevestigd.',
                    'metadata' => [
                        ...($locked->metadata ?? []),
                        'resulting_order_status' => $lockedOrder->status->value,
                        'resulting_payment_status' => $lockedPayment->status->value,
                    ],
                ]);

                return $locked->fresh();
            }

            $locked->update([
                'status' => $result->status,
                'provider_reference' => $result->providerReference,
                'provider_status' => $result->providerStatus,
                'failure_code' => $result->failureCode,
                'failure_message' => $result->failureMessage,
                'completed_at' => $result->status === OrderOperationStatus::Succeeded ? now() : null,
            ]);

            if ($result->status === OrderOperationStatus::Succeeded) {
                if (in_array($lockedPayment->status, [PaymentStatus::Open, PaymentStatus::Pending], true)) {
                    $lockedPayment->update(['status' => PaymentStatus::Expired, 'completed_at' => now()]);
                }
                $lockedOrder->update(['status' => OrderStatus::Cancelled]);
            }

            $locked->update(['metadata' => [
                ...($locked->metadata ?? []),
                'resulting_order_status' => $lockedOrder->fresh()->status->value,
                'resulting_payment_status' => $lockedPayment->fresh()->status->value,
            ]]);

            return $locked->fresh();
        });
    }

    private function createOperation(Order $order, User $operator, string $reason): OrderOperation
    {
        return $order->operations()->create([
            'operator_user_id' => $operator->id,
            'type' => OrderOperationType::Cancellation,
            'status' => OrderOperationStatus::Requested,
            'reason' => $reason,
            'amount_cents' => 0,
            'currency' => $order->currency,
            'idempotency_key' => 'cancel-order-'.$order->id.'-'.Str::lower(Str::random(20)),
            'requested_at' => now(),
            'metadata' => ['previous_order_status' => $order->status->value],
        ]);
    }
}
