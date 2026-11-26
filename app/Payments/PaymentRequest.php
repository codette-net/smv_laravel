<?php

namespace App\Payments;

final readonly class PaymentRequest
{
    /** @param array<int, PaymentLineItem> $lineItems */
    public function __construct(
        public string $idempotencyKey,
        public int $paymentId,
        public int $amountCents,
        public string $currency,
        public string $orderReference,
        public string $customerEmail,
        public string $successUrl,
        public string $cancelUrl,
        public array $lineItems,
    ) {}
}
