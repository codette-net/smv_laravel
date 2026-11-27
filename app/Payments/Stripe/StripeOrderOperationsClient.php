<?php

namespace App\Payments\Stripe;

interface StripeOrderOperationsClient
{
    public function retrieveCheckoutSession(string $sessionId): StripeCheckoutState;

    public function expireCheckoutSession(string $sessionId): StripeCheckoutState;

    /** @param array<string, mixed> $parameters */
    public function createRefund(array $parameters, string $idempotencyKey): StripeRefundState;
}
