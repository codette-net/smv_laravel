<?php

namespace App\Payments;

use App\Models\Payment;

interface PaymentGateway
{
    public function provider(): string;

    public function start(PaymentRequest $request): PaymentGatewayResult;

    public function expire(Payment $payment, string $idempotencyKey): PaymentOperationResult;

    public function refund(RefundRequest $request): PaymentOperationResult;
}
