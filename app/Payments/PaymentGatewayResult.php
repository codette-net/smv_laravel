<?php

namespace App\Payments;

use App\Enums\PaymentStatus;

final readonly class PaymentGatewayResult
{
    /** @param array<string, scalar|null> $metadata */
    public function __construct(
        public PaymentStatus $status,
        public string $provider,
        public string $providerReference,
        public array $metadata = [],
    ) {}
}
