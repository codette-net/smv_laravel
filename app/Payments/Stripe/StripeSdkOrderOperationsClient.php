<?php

namespace App\Payments\Stripe;

use App\Payments\PaymentGatewayException;
use Stripe\ApiRequestor;
use Stripe\Exception\ApiErrorException;
use Stripe\HttpClient\CurlClient;
use Stripe\StripeClient;

final class StripeSdkOrderOperationsClient implements StripeOrderOperationsClient
{
    private StripeClient $client;

    public function __construct()
    {
        $secret = trim((string) config('payments.stripe.secret_key'));
        $livemode = config('payments.stripe.livemode');

        if ($secret === '' || ! is_bool($livemode)) {
            throw new PaymentGatewayException('Stripe is niet volledig geconfigureerd.');
        }

        $expectedPrefix = $livemode ? 'sk_live_' : 'sk_test_';
        if (! str_starts_with($secret, $expectedPrefix)) {
            throw new PaymentGatewayException('De Stripe-sleutel hoort niet bij de geconfigureerde omgeving.');
        }

        $httpClient = new CurlClient;
        $httpClient->setTimeout(max(1, (int) config('payments.stripe.timeout_seconds', 20)));
        $httpClient->setConnectTimeout(max(1, (int) config('payments.stripe.connect_timeout_seconds', 10)));
        ApiRequestor::setHttpClient($httpClient);

        $this->client = new StripeClient([
            'api_key' => $secret,
            'max_network_retries' => max(0, (int) config('payments.stripe.max_network_retries', 2)),
        ]);
    }

    public function retrieveCheckoutSession(string $sessionId): StripeCheckoutState
    {
        try {
            return $this->checkoutState($this->client->checkout->sessions->retrieve($sessionId));
        } catch (ApiErrorException $exception) {
            report($exception);

            throw new PaymentGatewayException('De Stripe Checkout-status kon niet veilig worden vastgesteld.', previous: $exception);
        }
    }

    public function expireCheckoutSession(string $sessionId): StripeCheckoutState
    {
        try {
            return $this->checkoutState($this->client->checkout->sessions->expire($sessionId));
        } catch (ApiErrorException $exception) {
            report($exception);

            throw new PaymentGatewayException('De Stripe Checkout-sessie kon niet veilig worden beëindigd.', previous: $exception);
        }
    }

    public function createRefund(array $parameters, string $idempotencyKey): StripeRefundState
    {
        try {
            $refund = $this->client->refunds->create($parameters, ['idempotency_key' => $idempotencyKey]);
        } catch (ApiErrorException $exception) {
            report($exception);

            throw new PaymentGatewayException('De Stripe-terugbetaling heeft een onzekere uitkomst en moet worden gecontroleerd.', previous: $exception);
        }

        $paymentIntent = is_string($refund->payment_intent) ? $refund->payment_intent : $refund->payment_intent?->id;
        if (! is_string($paymentIntent) || $paymentIntent === '') {
            throw new PaymentGatewayException('Stripe gaf geen geldige PaymentIntent voor de terugbetaling terug.');
        }

        return new StripeRefundState(
            id: $refund->id,
            status: (string) $refund->status,
            amountCents: (int) $refund->amount,
            currency: strtoupper((string) $refund->currency),
            paymentIntent: $paymentIntent,
            failureReason: is_string($refund->failure_reason) ? $refund->failure_reason : null,
            pendingReason: is_string($refund->pending_reason) ? $refund->pending_reason : null,
        );
    }

    private function checkoutState(object $session): StripeCheckoutState
    {
        $paymentIntent = is_string($session->payment_intent) ? $session->payment_intent : $session->payment_intent?->id;

        return new StripeCheckoutState(
            id: (string) $session->id,
            status: (string) $session->status,
            paymentStatus: (string) $session->payment_status,
            paymentIntent: is_string($paymentIntent) && $paymentIntent !== '' ? $paymentIntent : null,
            livemode: (bool) $session->livemode,
        );
    }
}
