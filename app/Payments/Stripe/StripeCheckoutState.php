<?php

namespace App\Payments\Stripe;

final readonly class StripeCheckoutState
{
    public function __construct(
        public string $id,
        public string $status,
        public string $paymentStatus,
        public ?string $paymentIntent,
        public bool $livemode,
    ) {}
}
