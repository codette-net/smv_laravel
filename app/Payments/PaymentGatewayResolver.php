<?php

namespace App\Payments;

use App\Payments\Stripe\StripePaymentGateway;
use RuntimeException;

final class PaymentGatewayResolver
{
    public function resolve(): PaymentGateway
    {
        $provider = config('payments.provider');

        if ($provider === null && app()->environment(['local', 'testing', 'staging'])) {
            $provider = 'fake';
        }

        if ($provider === 'fake' && ! app()->environment(['local', 'testing', 'staging'])) {
            throw new RuntimeException('The fake payment gateway is not allowed in production.');
        }

        return match ($provider) {
            'fake' => app(FakePaymentGateway::class),
            'stripe' => app(StripePaymentGateway::class),
            default => throw new RuntimeException('No payment gateway is configured for this environment.'),
        };
    }
}
