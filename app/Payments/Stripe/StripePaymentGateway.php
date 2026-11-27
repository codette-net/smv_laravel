<?php

namespace App\Payments\Stripe;

use App\Enums\OrderOperationStatus;
use App\Enums\PaymentStatus;
use App\Models\Payment;
use App\Payments\PaymentGateway;
use App\Payments\PaymentGatewayException;
use App\Payments\PaymentGatewayResult;
use App\Payments\PaymentLineItem;
use App\Payments\PaymentOperationResult;
use App\Payments\PaymentRequest;
use App\Payments\RefundRequest;

final class StripePaymentGateway implements PaymentGateway
{
    public function __construct(
        private readonly StripeCheckoutClient $client,
        private readonly StripeOrderOperationsClient $operations,
    ) {}

    public function provider(): string
    {
        return 'stripe';
    }

    public function start(PaymentRequest $request): PaymentGatewayResult
    {
        $session = $this->client->createSession([
            'mode' => 'payment',
            'success_url' => $request->successUrl,
            'cancel_url' => $request->cancelUrl,
            'customer_email' => $request->customerEmail,
            'client_reference_id' => (string) $request->paymentId,
            'locale' => 'nl',
            'line_items' => $this->checkoutLineItems($request->lineItems),
            'metadata' => [
                'smv_payment_id' => (string) $request->paymentId,
                'smv_order_reference' => $request->orderReference,
            ],
            'payment_intent_data' => [
                'metadata' => [
                    'smv_payment_id' => (string) $request->paymentId,
                    'smv_order_reference' => $request->orderReference,
                ],
            ],
        ], $request->idempotencyKey);

        if ($session->livemode !== config('payments.stripe.livemode')) {
            throw new PaymentGatewayException('Stripe Checkout gebruikte een onverwachte omgeving.');
        }

        return new PaymentGatewayResult(
            status: PaymentStatus::Pending,
            provider: $this->provider(),
            providerReference: $session->id,
            metadata: [
                'checkout_url' => $session->url,
                'livemode' => $session->livemode,
            ],
            redirectUrl: $session->url,
        );
    }

    public function expire(Payment $payment, string $idempotencyKey): PaymentOperationResult
    {
        if (! is_string($payment->provider_reference) || $payment->provider_reference === '') {
            return new PaymentOperationResult(
                OrderOperationStatus::RequiresReview,
                $this->provider(),
                null,
                'unknown',
                'checkout_reference_missing',
                'De Checkout-referentie ontbreekt.',
            );
        }

        $state = $this->operations->retrieveCheckoutSession($payment->provider_reference);
        if ($state->livemode !== config('payments.stripe.livemode')) {
            throw new PaymentGatewayException('De Stripe Checkout-sessie hoort bij een onverwachte omgeving.');
        }

        if ($state->paymentStatus === 'paid' || $state->paymentIntent !== null) {
            return new PaymentOperationResult(
                OrderOperationStatus::RequiresReview,
                $this->provider(),
                $state->id,
                $state->paymentStatus,
                'payment_may_have_succeeded',
                'Stripe meldt een betaling of PaymentIntent; de bestelling is niet geannuleerd.',
            );
        }

        if ($state->status === 'expired' && $state->paymentStatus === 'unpaid') {
            return new PaymentOperationResult(OrderOperationStatus::Succeeded, $this->provider(), $state->id, 'expired');
        }

        if ($state->status !== 'open' || $state->paymentStatus !== 'unpaid') {
            return new PaymentOperationResult(
                OrderOperationStatus::RequiresReview,
                $this->provider(),
                $state->id,
                $state->status,
                'checkout_not_expirable',
                'De Checkout-sessie is niet aantoonbaar open en onbetaald.',
            );
        }

        $expired = $this->operations->expireCheckoutSession($state->id);

        return $expired->status === 'expired' && $expired->paymentStatus === 'unpaid'
            ? new PaymentOperationResult(OrderOperationStatus::Succeeded, $this->provider(), $expired->id, 'expired')
            : new PaymentOperationResult(
                OrderOperationStatus::RequiresReview,
                $this->provider(),
                $expired->id,
                $expired->status,
                'checkout_expiry_unconfirmed',
                'Stripe heeft het beëindigen van de Checkout-sessie niet eenduidig bevestigd.',
            );
    }

    public function refund(RefundRequest $request): PaymentOperationResult
    {
        $refund = $this->operations->createRefund([
            'payment_intent' => $request->providerPaymentId,
            'amount' => $request->amountCents,
            'metadata' => [
                'smv_order_operation_id' => (string) $request->operationId,
                'smv_order_id' => (string) $request->orderId,
                'smv_payment_id' => (string) $request->paymentId,
            ],
        ], $request->idempotencyKey);

        if ($refund->amountCents !== $request->amountCents
            || strtoupper($refund->currency) !== strtoupper($request->currency)
            || $refund->paymentIntent !== $request->providerPaymentId) {
            throw new PaymentGatewayException('De Stripe-terugbetaling komt niet overeen met de SMV-bestelling.');
        }

        return new PaymentOperationResult(
            status: match ($refund->status) {
                'succeeded' => OrderOperationStatus::Succeeded,
                'pending' => OrderOperationStatus::Pending,
                'failed' => OrderOperationStatus::Failed,
                default => OrderOperationStatus::RequiresReview,
            },
            provider: $this->provider(),
            providerReference: $refund->id,
            providerStatus: $refund->status,
            failureCode: $refund->failureReason ?? $refund->pendingReason,
            failureMessage: $refund->failureReason !== null ? 'Stripe meldde dat de terugbetaling is mislukt.' : null,
        );
    }

    /**
     * The Order snapshot remains the tax source of truth. Net product and VAT
     * are separate Checkout rows so their integer sum always equals the Order.
     *
     * @param  array<int, PaymentLineItem>  $items
     * @return array<int, array<string, mixed>>
     */
    private function checkoutLineItems(array $items): array
    {
        return collect($items)->flatMap(function (PaymentLineItem $item): array {
            $currency = strtolower($item->currency);
            $rows = [[
                'price_data' => [
                    'currency' => $currency,
                    'unit_amount' => $item->unitAmountCents,
                    'product_data' => ['name' => $item->name],
                ],
                'quantity' => $item->quantity,
            ]];

            if ($item->vatAmountCents > 0) {
                $rate = $item->vatRateBasisPoints === null
                    ? 'Btw'
                    : number_format($item->vatRateBasisPoints / 100, 2, ',', '.').'% btw';
                $rows[] = [
                    'price_data' => [
                        'currency' => $currency,
                        'unit_amount' => $item->vatAmountCents,
                        'product_data' => ['name' => $rate.' — '.$item->name],
                    ],
                    'quantity' => 1,
                ];
            }

            return $rows;
        })->values()->all();
    }
}
