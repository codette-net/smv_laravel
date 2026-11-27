<?php

namespace App\Payments;

final readonly class RefundRequest
{
    public function __construct(
        public string $idempotencyKey,
        public int $operationId,
        public int $orderId,
        public int $paymentId,
        public int $amountCents,
        public string $currency,
        public string $providerPaymentId,
    ) {}
}
