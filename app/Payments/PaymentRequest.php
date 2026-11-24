<?php

namespace App\Payments;

final readonly class PaymentRequest
{
    public function __construct(
        public string $idempotencyKey,
        public int $amountCents,
        public string $currency,
        public string $orderReference,
    ) {}
}
