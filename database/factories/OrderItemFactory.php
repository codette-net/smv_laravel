<?php

namespace Database\Factories;

use App\Models\Order;
use App\Models\OrderItem;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<OrderItem>
 */
class OrderItemFactory extends Factory
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
            'product_code' => 'standard',
            'title_snapshot' => 'Standaard',
            'price_cents' => 18_900,
            'currency' => 'EUR',
            'vat_rate_basis_points' => null,
            'quantity' => 1,
            'subtotal_cents' => 18_900,
            'vat_cents' => 0,
            'total_cents' => 18_900,
        ];
    }
}
