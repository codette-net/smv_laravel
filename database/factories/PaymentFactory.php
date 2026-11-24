<?php

namespace Database\Factories;

use App\Enums\PaymentStatus;
use App\Models\Order;
use App\Models\Payment;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Payment>
 */
class PaymentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'order_id' => Order::factory(),
            'idempotency_key' => 'payment-'.fake()->unique()->uuid(),
            'provider' => 'fake',
            'provider_payment_id' => 'fake-'.fake()->unique()->uuid(),
            'provider_reference' => 'fake-'.fake()->unique()->uuid(),
            'status' => PaymentStatus::Pending,
            'amount_cents' => 0,
            'currency' => 'EUR',
            'paid_at' => null,
            'completed_at' => null,
            'raw_payload' => null,
            'metadata' => [],
        ];
    }
}
