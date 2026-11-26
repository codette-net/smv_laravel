<?php

namespace App\Payments\Stripe;

use App\Enums\PaymentStatus;
use App\Payments\PaymentGateway;
use App\Payments\PaymentGatewayException;
use App\Payments\PaymentGatewayResult;
use App\Payments\PaymentLineItem;
use App\Payments\PaymentRequest;

final class StripePaymentGateway implements PaymentGateway
{
    public function __construct(private readonly StripeCheckoutClient $client) {}

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
