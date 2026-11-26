<?php

namespace App\Payments;

use App\Enums\PaymentStatus;

final class FakePaymentGateway implements PaymentGateway
{
    private FakePaymentOutcome $outcome;

    public function __construct(?FakePaymentOutcome $outcome = null)
    {
        $this->outcome = $outcome ?? FakePaymentOutcome::Pending;
    }

    public function provider(): string
    {
        return 'fake';
    }

    public function start(PaymentRequest $request): PaymentGatewayResult
    {
        return new PaymentGatewayResult(
            status: match ($this->outcome) {
                FakePaymentOutcome::Pending => PaymentStatus::Pending,
                FakePaymentOutcome::Success => PaymentStatus::Paid,
                FakePaymentOutcome::Failed => PaymentStatus::Failed,
                FakePaymentOutcome::Cancelled => PaymentStatus::Cancelled,
            },
            provider: 'fake',
            providerReference: 'fake-'.$request->idempotencyKey,
            metadata: [
                'scenario' => $this->outcome->value,
                'order_reference' => $request->orderReference,
            ],
        );
    }

    public function pending(): self
    {
        $this->outcome = FakePaymentOutcome::Pending;

        return $this;
    }

    public function succeed(): self
    {
        $this->outcome = FakePaymentOutcome::Success;

        return $this;
    }

    public function fail(): self
    {
        $this->outcome = FakePaymentOutcome::Failed;

        return $this;
    }

    public function cancel(): self
    {
        $this->outcome = FakePaymentOutcome::Cancelled;

        return $this;
    }
}
