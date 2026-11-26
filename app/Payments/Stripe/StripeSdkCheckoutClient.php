<?php

namespace App\Payments\Stripe;

use App\Payments\PaymentGatewayException;
use Stripe\ApiRequestor;
use Stripe\Exception\ApiErrorException;
use Stripe\HttpClient\CurlClient;
use Stripe\StripeClient;

final class StripeSdkCheckoutClient implements StripeCheckoutClient
{
    private StripeClient $client;

    public function __construct()
    {
        $secret = trim((string) config('payments.stripe.secret_key'));
        $livemode = config('payments.stripe.livemode');

        if ($secret === '') {
            throw new PaymentGatewayException('Stripe is niet geconfigureerd. Neem contact op met SMV.');
        }

        if (! is_bool($livemode)) {
            throw new PaymentGatewayException('De Stripe-omgeving is niet expliciet geconfigureerd.');
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

    public function createSession(array $parameters, string $idempotencyKey): StripeCheckoutSession
    {
        try {
            $session = $this->client->checkout->sessions->create($parameters, [
                'idempotency_key' => $idempotencyKey,
            ]);
        } catch (ApiErrorException $exception) {
            report($exception);

            throw new PaymentGatewayException('Stripe Checkout kon niet worden gestart. Probeer het later opnieuw.', previous: $exception);
        }

        if (! is_string($session->url) || $session->url === '') {
            throw new PaymentGatewayException('Stripe Checkout heeft geen geldige betaallink teruggegeven.');
        }

        return new StripeCheckoutSession(
            id: $session->id,
            url: $session->url,
            livemode: (bool) $session->livemode,
        );
    }
}
