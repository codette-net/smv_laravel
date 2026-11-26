<?php

return [
    'provider' => env('PAYMENT_GATEWAY'),

    'stripe' => [
        'secret_key' => env('STRIPE_SECRET_KEY'),
        'publishable_key' => env('STRIPE_PUBLISHABLE_KEY'),
        'webhook_secret' => env('STRIPE_WEBHOOK_SECRET'),
        'livemode' => filter_var(env('STRIPE_LIVE_MODE'), FILTER_VALIDATE_BOOL, FILTER_NULL_ON_FAILURE),
        'timeout_seconds' => (int) env('STRIPE_TIMEOUT_SECONDS', 20),
        'connect_timeout_seconds' => (int) env('STRIPE_CONNECT_TIMEOUT_SECONDS', 10),
        'max_network_retries' => (int) env('STRIPE_MAX_NETWORK_RETRIES', 2),
    ],
];
