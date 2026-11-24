<?php

namespace App\Actions\Payments;

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Models\Order;
use App\Models\Payment;
use App\Models\User;
use App\Payments\PaymentGatewayResolver;
use App\Payments\PaymentRequest;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use InvalidArgumentException;

class StartPayment
{
    public function __construct(private readonly PaymentGatewayResolver $gateways) {}

    public function execute(User $purchaser, Order $order, ?string $idempotencyKey = null): Payment
    {
        if ($order->user_id !== $purchaser->id) {
            throw new InvalidArgumentException('The purchaser does not own this order.');
        }

        if ($order->status === OrderStatus::Paid) {
            throw new InvalidArgumentException('A paid order cannot start another payment.');
        }

        if (! in_array($order->status, [OrderStatus::PendingPayment, OrderStatus::Pending], true)) {
            throw new InvalidArgumentException('The order is not ready for payment.');
        }

        $key = $idempotencyKey ?: 'order-'.$order->id.'-'.Str::lower(Str::random(20));

        $gateway = $this->gateways->resolve();
        $provider = (string) (config('payments.provider') ?: 'fake');

        $existing = Payment::query()->where('idempotency_key', $key)->first();
        if ($existing) {
            if ($existing->order_id !== $order->id) {
                throw new InvalidArgumentException('The payment idempotency key is already in use.');
            }

            return $existing;
        }

        $payment = DB::transaction(fn (): Payment => Payment::create([
            'order_id' => $order->id,
            'idempotency_key' => $key,
            'provider' => $provider,
            'status' => PaymentStatus::Pending,
            'amount_cents' => $order->total_cents,
            'currency' => $order->currency,
        ]));

        try {
            $result = $gateway->start(new PaymentRequest(
                idempotencyKey: $key,
                amountCents: $order->total_cents,
                currency: $order->currency,
                orderReference: $order->order_number,
            ));
        } catch (\Throwable $exception) {
            $payment->update(['status' => PaymentStatus::Failed, 'completed_at' => now()]);
            throw $exception;
        }

        $payment->update([
            'provider' => $result->provider,
            'provider_payment_id' => $result->providerReference,
            'provider_reference' => $result->providerReference,
            'status' => $result->status,
            'completed_at' => $result->status === PaymentStatus::Pending ? null : now(),
            'paid_at' => $result->status === PaymentStatus::Paid ? now() : null,
            'metadata' => $result->metadata,
        ]);

        if ($result->status === PaymentStatus::Paid) {
            return app(ConfirmPayment::class)->execute($payment->fresh());
        }

        return $payment->fresh();
    }
}
