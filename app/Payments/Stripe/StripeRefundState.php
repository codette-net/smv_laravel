<?php

namespace App\Payments\Stripe;

final readonly class StripeRefundState
{
    public function __construct(
        public string $id,
        public string $status,
        public int $amountCents,
        public string $currency,
        public string $paymentIntent,
        public ?string $failureReason = null,
        public ?string $pendingReason = null,
    ) {}
}
