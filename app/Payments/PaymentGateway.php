<?php

namespace App\Payments;

interface PaymentGateway
{
    public function provider(): string;

    public function start(PaymentRequest $request): PaymentGatewayResult;
}
