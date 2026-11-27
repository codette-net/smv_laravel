<?php

namespace App\Payments;

use App\Enums\OrderOperationStatus;
use App\Enums\PaymentStatus;
use App\Models\Payment;

final class FakePaymentGateway implements PaymentGateway
{
    private FakePaymentOutcome $outcome;

    private FakeRefundOutcome $refundOutcome;

    public function __construct(?FakePaymentOutcome $outcome = null, ?FakeRefundOutcome $refundOutcome = null)
    {
        $this->outcome = $outcome ?? FakePaymentOutcome::Pending;
        $this->refundOutcome = $refundOutcome ?? FakeRefundOutcome::Success;
    }

    public function expire(Payment $payment, string $idempotencyKey): PaymentOperationResult
    {
        return new PaymentOperationResult(
            status: OrderOperationStatus::Succeeded,
            provider: 'fake',
            providerReference: $payment->provider_reference,
            providerStatus: 'expired',
        );
    }

    public function refund(RefundRequest $request): PaymentOperationResult
    {
        if ($this->refundOutcome === FakeRefundOutcome::Indeterminate) {
            throw new PaymentGatewayException('De testterugbetaling heeft een onzekere uitkomst.');
        }

        return new PaymentOperationResult(
            status: match ($this->refundOutcome) {
                FakeRefundOutcome::Success => OrderOperationStatus::Succeeded,
                FakeRefundOutcome::Pending => OrderOperationStatus::Pending,
                FakeRefundOutcome::Failed => OrderOperationStatus::Failed,
                FakeRefundOutcome::Indeterminate => OrderOperationStatus::RequiresReview,
            },
            provider: 'fake',
            providerReference: 'fake-refund-'.$request->operationId,
            providerStatus: $this->refundOutcome === FakeRefundOutcome::Success ? 'succeeded' : $this->refundOutcome->value,
            failureCode: $this->refundOutcome === FakeRefundOutcome::Failed ? 'fake_refund_failed' : null,
            failureMessage: $this->refundOutcome === FakeRefundOutcome::Failed ? 'Deterministische testterugbetaling mislukt.' : null,
        );
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

    public function refundWith(FakeRefundOutcome $outcome): self
    {
        $this->refundOutcome = $outcome;

        return $this;
    }
}
