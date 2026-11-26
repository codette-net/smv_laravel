<?php

namespace App\Actions\Payments;

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Models\Order;
use App\Models\Payment;
use App\Models\User;
use App\Payments\PaymentGatewayResolver;
use App\Payments\PaymentLineItem;
use App\Payments\PaymentRequest;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use InvalidArgumentException;

class StartPayment
{
    public function __construct(private readonly PaymentGatewayResolver $gateways) {}

    public function execute(User $purchaser, Order $order, ?string $idempotencyKey = null): Payment
    {
        $gateway = $this->gateways->resolve();
        $provider = $gateway->provider();

        [$payment, $shouldStart] = DB::transaction(function () use ($purchaser, $order, $idempotencyKey, $provider): array {
            $lockedOrder = Order::query()->lockForUpdate()->findOrFail($order->id);

            if ($lockedOrder->user_id !== $purchaser->id || $lockedOrder->company()->value('user_id') !== $purchaser->id) {
                throw new InvalidArgumentException('The purchaser does not own this order.');
            }

            if ($lockedOrder->status === OrderStatus::Paid) {
                throw new InvalidArgumentException('A paid order cannot start another payment.');
            }

            if (! in_array($lockedOrder->status, [OrderStatus::PendingPayment, OrderStatus::Pending], true)) {
                throw new InvalidArgumentException('The order is not ready for payment.');
            }

            if ($idempotencyKey !== null) {
                $existing = Payment::query()->where('idempotency_key', $idempotencyKey)->first();
                if ($existing !== null) {
                    if ($existing->order_id !== $lockedOrder->id) {
                        throw new InvalidArgumentException('The payment idempotency key is already in use.');
                    }

                    return [$existing, false];
                }
            }

            $pending = $lockedOrder->payments()
                ->where('provider', $provider)
                ->whereIn('status', [PaymentStatus::Pending->value, PaymentStatus::Open->value])
                ->latest('id')
                ->first();

            if ($pending !== null) {
                return [$pending, $pending->provider_reference === null];
            }

            $key = $idempotencyKey ?: 'order-'.$lockedOrder->id.'-'.Str::lower(Str::random(20));

            return [Payment::create([
                'order_id' => $lockedOrder->id,
                'idempotency_key' => $key,
                'provider' => $provider,
                'status' => PaymentStatus::Pending,
                'amount_cents' => $lockedOrder->total_cents,
                'currency' => $lockedOrder->currency,
            ]), true];
        });

        if (! $shouldStart) {
            return $payment->fresh();
        }

        $order = $order->fresh(['orderItems', 'user']);
        $lineItems = $order->orderItems->map(fn ($item): PaymentLineItem => new PaymentLineItem(
            name: $item->title_snapshot,
            quantity: $item->quantity,
            unitAmountCents: $item->price_cents,
            vatAmountCents: $item->vat_cents,
            currency: $item->currency,
            vatRateBasisPoints: $item->vat_rate_basis_points,
        ))->all();

        $checkoutTotal = collect($lineItems)->sum(
            fn (PaymentLineItem $item): int => ($item->unitAmountCents * $item->quantity) + $item->vatAmountCents,
        );
        if ($checkoutTotal !== $order->total_cents) {
            throw new InvalidArgumentException('The Order snapshot does not match its payment total.');
        }

        try {
            $result = $gateway->start(new PaymentRequest(
                idempotencyKey: $payment->idempotency_key,
                paymentId: $payment->id,
                amountCents: $order->total_cents,
                currency: $order->currency,
                orderReference: $order->order_number,
                customerEmail: $order->user->email,
                successUrl: route('account.orders.success', $order),
                cancelUrl: route('account.orders.cancelled', $order),
                lineItems: $lineItems,
            ));
        } catch (\Throwable $exception) {
            // The remote outcome may be indeterminate after a timeout. Leave this
            // attempt pending so a retry uses the same provider idempotency key.
            throw $exception;
        }

        $payment->update([
            'provider' => $result->provider,
            'provider_payment_id' => $result->provider === 'stripe' ? null : $result->providerReference,
            'provider_reference' => $result->providerReference,
            'status' => $result->status,
            'completed_at' => $result->status === PaymentStatus::Pending ? null : now(),
            'paid_at' => $result->status === PaymentStatus::Paid ? now() : null,
            'metadata' => [
                ...$result->metadata,
                'redirect_url' => $result->redirectUrl,
            ],
        ]);

        if ($result->status === PaymentStatus::Paid) {
            return app(ConfirmPayment::class)->execute($payment->fresh());
        }

        return $payment->fresh();
    }
}
