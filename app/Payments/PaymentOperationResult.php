<?php

namespace App\Payments;

use App\Enums\OrderOperationStatus;

final readonly class PaymentOperationResult
{
    /** @param array<string, scalar|null> $metadata */
    public function __construct(
        public OrderOperationStatus $status,
        public string $provider,
        public ?string $providerReference,
        public string $providerStatus,
        public ?string $failureCode = null,
        public ?string $failureMessage = null,
        public array $metadata = [],
    ) {}
}
