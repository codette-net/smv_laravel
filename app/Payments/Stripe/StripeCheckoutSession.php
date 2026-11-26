<?php

namespace App\Payments\Stripe;

final readonly class StripeCheckoutSession
{
    public function __construct(
        public string $id,
        public string $url,
        public bool $livemode,
    ) {}
}
