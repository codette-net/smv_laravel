<?php

namespace App\Payments;

final readonly class PaymentLineItem
{
    public function __construct(
        public string $name,
        public int $quantity,
        public int $unitAmountCents,
        public int $vatAmountCents,
        public string $currency,
        public ?int $vatRateBasisPoints,
    ) {}
}
