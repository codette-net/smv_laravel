<?php

namespace App\Payments\Stripe;

interface StripeCheckoutClient
{
    /** @param array<string, mixed> $parameters */
    public function createSession(array $parameters, string $idempotencyKey): StripeCheckoutSession;
}
