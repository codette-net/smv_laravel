<?php

namespace App\Payments\Stripe;

use App\Actions\Orders\ReconcileRefund;
use App\Actions\Payments\ConfirmPayment;
use App\Enums\OrderOperationStatus;
use App\Enums\OrderOperationType;
use App\Enums\PaymentStatus;
use App\Models\OrderOperation;
use App\Models\Payment;
use App\Models\PaymentWebhookEvent;
use App\Payments\PaymentGatewayException;
use App\Payments\PaymentOperationResult;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Stripe\Event;
use Stripe\StripeObject;

final class ProcessStripeWebhook
{
    private const SUPPORTED_EVENTS = [
        'checkout.session.completed',
        'checkout.session.async_payment_succeeded',
        'checkout.session.async_payment_failed',
        'checkout.session.expired',
        'refund.created',
        'refund.updated',
        'refund.failed',
    ];

    public function __construct(
        private readonly ConfirmPayment $confirmPayment,
        private readonly ReconcileRefund $reconcileRefund,
    ) {}

    public function handle(Event $event): void
    {
        if (! in_array($event->type, self::SUPPORTED_EVENTS, true)) {
            return;
        }

        $object = $event->data->object;
        if (! $object instanceof StripeObject || ! is_string($object->id ?? null)) {
            throw new PaymentGatewayException('Stripe webhook bevat geen geldig object.');
        }

        if (str_starts_with($event->type, 'refund.')) {
            $this->handleRefund($event, $object);

            return;
        }

        DB::transaction(function () use ($event, $object): void {
            $ledger = PaymentWebhookEvent::query()->firstOrCreate([
                'provider' => 'stripe',
                'event_id' => $event->id,
            ], [
                'event_type' => $event->type,
            ]);
            $ledger = PaymentWebhookEvent::query()->lockForUpdate()->findOrFail($ledger->id);

            if ($ledger->processed_at !== null) {
                return;
            }

            $payment = Payment::query()
                ->where('provider', 'stripe')
                ->where('provider_reference', $object->id)
                ->lockForUpdate()
                ->first();

            if ($payment === null) {
                throw new PaymentGatewayException('Stripe Checkout Session is niet gekoppeld aan een betaling.');
            }

            $this->validateSession($object, $payment);
            $this->applyEvent($event->type, $object, $payment);

            $ledger->update([
                'payment_id' => $payment->id,
                'processed_at' => now(),
            ]);
        });
    }

    private function handleRefund(Event $event, StripeObject $refund): void
    {
        DB::transaction(function () use ($event, $refund): void {
            $ledger = PaymentWebhookEvent::query()->firstOrCreate([
                'provider' => 'stripe',
                'event_id' => $event->id,
            ], [
                'event_type' => $event->type,
            ]);
            $ledger = PaymentWebhookEvent::query()->lockForUpdate()->findOrFail($ledger->id);
            if ($ledger->processed_at !== null) {
                return;
            }

            $metadata = $refund->metadata ?? null;
            $operationId = $metadata instanceof StripeObject
                ? (int) ($metadata->smv_order_operation_id ?? 0)
                : 0;
            $operation = OrderOperation::query()
                ->where('type', OrderOperationType::Refund->value)
                ->where(function ($query) use ($refund, $operationId): void {
                    $query->where('provider_reference', $refund->id);
                    if ($operationId > 0) {
                        $query->orWhere('id', $operationId);
                    }
                })
                ->lockForUpdate()
                ->first();

            if ($operation === null || $operation->payment === null) {
                throw new PaymentGatewayException('Stripe-terugbetaling is niet gekoppeld aan een SMV-handeling.');
            }

            $paymentIntent = is_string($refund->payment_intent ?? null)
                ? $refund->payment_intent
                : ($refund->payment_intent->id ?? null);
            if ((int) ($refund->amount ?? -1) !== $operation->amount_cents
                || strtoupper((string) ($refund->currency ?? '')) !== strtoupper($operation->currency)
                || $paymentIntent !== $operation->payment->provider_payment_id) {
                Log::warning('Stripe refund reconciliation rejected.', [
                    'order_operation_id' => $operation->id,
                    'refund_id' => $refund->id,
                ]);

                throw new PaymentGatewayException('Stripe-terugbetaling komt niet overeen met de SMV-bestelling.');
            }

            $providerStatus = (string) ($refund->status ?? 'unknown');
            $result = new PaymentOperationResult(
                status: match ($providerStatus) {
                    'succeeded' => OrderOperationStatus::Succeeded,
                    'pending' => OrderOperationStatus::Pending,
                    'failed' => OrderOperationStatus::Failed,
                    default => OrderOperationStatus::RequiresReview,
                },
                provider: 'stripe',
                providerReference: $refund->id,
                providerStatus: $providerStatus,
                failureCode: is_string($refund->failure_reason ?? null)
                    ? $refund->failure_reason
                    : (is_string($refund->pending_reason ?? null) ? $refund->pending_reason : null),
                failureMessage: $providerStatus === 'failed' ? 'Stripe meldde dat de terugbetaling is mislukt.' : null,
            );
            $this->reconcileRefund->execute($operation, $result);

            $ledger->update([
                'payment_id' => $operation->payment_id,
                'order_operation_id' => $operation->id,
                'processed_at' => now(),
            ]);
        });
    }

    private function validateSession(StripeObject $session, Payment $payment): void
    {
        $order = $payment->order;
        $metadata = $session->metadata;
        $currency = strtolower((string) ($session->currency ?? ''));
        $paymentIntent = $session->payment_intent ?? null;

        if (($session->mode ?? null) !== 'payment'
            || (bool) ($session->livemode ?? false) !== config('payments.stripe.livemode')
            || (int) ($session->amount_total ?? -1) !== $payment->amount_cents
            || $currency !== strtolower($payment->currency)
            || (string) ($session->client_reference_id ?? '') !== (string) $payment->id
            || (string) ($metadata->smv_payment_id ?? '') !== (string) $payment->id
            || (string) ($metadata->smv_order_reference ?? '') !== $order->order_number) {
            Log::warning('Stripe Checkout reconciliation rejected.', [
                'payment_id' => $payment->id,
                'checkout_session_id' => $session->id,
            ]);

            throw new PaymentGatewayException('Stripe Checkout Session komt niet overeen met de SMV-bestelling.');
        }

        if (is_string($paymentIntent) && $paymentIntent !== '') {
            $payment->update(['provider_payment_id' => $paymentIntent]);
        }
    }

    private function applyEvent(string $eventType, StripeObject $session, Payment $payment): void
    {
        $isPaid = ($session->payment_status ?? null) === 'paid';

        if ($eventType === 'checkout.session.completed') {
            if ($isPaid) {
                $this->confirmPayment->execute($payment, providerAuthoritative: true);
            }

            return;
        }

        if ($eventType === 'checkout.session.async_payment_succeeded') {
            if (! $isPaid) {
                throw new PaymentGatewayException('Stripe meldde succes zonder betaalde Checkout Session.');
            }

            $this->confirmPayment->execute($payment, providerAuthoritative: true);

            return;
        }

        if ($payment->status === PaymentStatus::Paid) {
            return;
        }

        if (! in_array($payment->status, [PaymentStatus::Pending, PaymentStatus::Open], true)) {
            return;
        }

        $payment->update([
            'status' => $eventType === 'checkout.session.expired'
                ? PaymentStatus::Expired
                : PaymentStatus::Failed,
            'completed_at' => $payment->completed_at ?? now(),
        ]);
    }
}
