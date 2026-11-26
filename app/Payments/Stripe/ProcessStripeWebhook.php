<?php

namespace App\Payments\Stripe;

use App\Actions\Payments\ConfirmPayment;
use App\Enums\PaymentStatus;
use App\Models\Payment;
use App\Models\PaymentWebhookEvent;
use App\Payments\PaymentGatewayException;
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
    ];

    public function __construct(private readonly ConfirmPayment $confirmPayment) {}

    public function handle(Event $event): void
    {
        if (! in_array($event->type, self::SUPPORTED_EVENTS, true)) {
            return;
        }

        $session = $event->data->object;
        if (! $session instanceof StripeObject || ! is_string($session->id ?? null)) {
            throw new PaymentGatewayException('Stripe webhook bevat geen geldige Checkout Session.');
        }

        DB::transaction(function () use ($event, $session): void {
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
                ->where('provider_reference', $session->id)
                ->lockForUpdate()
                ->first();

            if ($payment === null) {
                throw new PaymentGatewayException('Stripe Checkout Session is niet gekoppeld aan een betaling.');
            }

            $this->validateSession($session, $payment);
            $this->applyEvent($event->type, $session, $payment);

            $ledger->update([
                'payment_id' => $payment->id,
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
