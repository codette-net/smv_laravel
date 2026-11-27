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
use App\Payments\RefundRequest;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use InvalidArgumentException;

class RequestFullRefund
{
    public function __construct(
        private readonly PaymentGatewayResolver $gateways,
        private readonly ReconcileRefund $reconcile,
    ) {}

    public function execute(User $operator, Order $order, string $reason): OrderOperation
    {
        if (! $operator->can('refund', $order)) {
            throw new AuthorizationException;
        }

        $reason = trim($reason);
        if (mb_strlen($reason) < 5 || mb_strlen($reason) > 2000) {
            throw new InvalidArgumentException('Een interne reden van 5 tot 2000 tekens is verplicht.');
        }

        [$operation, $payment, $shouldCallProvider] = DB::transaction(function () use ($operator, $order, $reason): array {
            $lockedOrder = Order::query()->lockForUpdate()->findOrFail($order->id);
            $existing = $lockedOrder->operations()->where('type', OrderOperationType::Refund->value)->lockForUpdate()->first();
            if ($existing?->providerSucceeded()) {
                return [$existing, $existing->payment, false];
            }

            if ($lockedOrder->status !== OrderStatus::Paid) {
                throw new InvalidArgumentException('Alleen een betaalde bestelling kan volledig worden terugbetaald.');
            }

            $paidPayments = $lockedOrder->payments()->where('status', PaymentStatus::Paid->value)->lockForUpdate()->get();
            if ($paidPayments->count() !== 1) {
                throw new InvalidArgumentException('De succesvolle betaling kan niet eenduidig worden vastgesteld.');
            }

            /** @var Payment $payment */
            $payment = $paidPayments->first();
            if (! is_string($payment->provider_payment_id) || $payment->provider_payment_id === '') {
                throw new InvalidArgumentException('De providerbetaling heeft geen bruikbare betalingsreferentie.');
            }

            $operation = $existing ?? $lockedOrder->operations()->create([
                'payment_id' => $payment->id,
                'operator_user_id' => $operator->id,
                'type' => OrderOperationType::Refund,
                'status' => OrderOperationStatus::Requested,
                'reason' => $reason,
                'amount_cents' => $payment->amount_cents,
                'currency' => $payment->currency,
                'provider' => $payment->provider,
                'idempotency_key' => 'refund-order-'.$lockedOrder->id.'-'.Str::lower(Str::random(20)),
                'requested_at' => now(),
                'metadata' => [
                    'previous_order_status' => $lockedOrder->status->value,
                    'previous_payment_status' => $payment->status->value,
                ],
            ]);

            return [$operation->fresh(), $payment, true];
        });

        if (! $shouldCallProvider) {
            return $operation;
        }

        try {
            $result = $this->gateways->resolveForProvider($payment->provider)->refund(new RefundRequest(
                idempotencyKey: $operation->idempotency_key,
                operationId: $operation->id,
                orderId: $order->id,
                paymentId: $payment->id,
                amountCents: $operation->amount_cents,
                currency: $operation->currency,
                providerPaymentId: $payment->provider_payment_id,
            ));
        } catch (PaymentGatewayException $exception) {
            $operation->update([
                'status' => OrderOperationStatus::RequiresReview,
                'failure_code' => 'provider_outcome_unknown',
                'failure_message' => $exception->getMessage(),
            ]);

            return $operation->fresh();
        }

        return $this->reconcile->execute($operation, $result);
    }
}
