<?php

namespace App\Payments;

interface PaymentGateway
{
    public function start(PaymentRequest $request): PaymentGatewayResult;
}
