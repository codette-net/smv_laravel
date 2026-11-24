<?php

namespace Database\Factories;

use App\Enums\OrderStatus;
use App\Models\Company;
use App\Models\Order;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Order>
 */
class OrderFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'company_id' => Company::factory(),
            'order_number' => 'SMV-'.fake()->unique()->bothify('##########??'),
            'status' => OrderStatus::Draft,
            'subtotal_cents' => 0,
            'vat_cents' => 0,
            'total_cents' => 0,
            'currency' => 'EUR',
        ];
    }
}
