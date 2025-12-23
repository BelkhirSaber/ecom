<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\OrderReturn>
 */
class OrderReturnFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'order_id' => \App\Models\Order::factory(),
            'user_id' => \App\Models\User::factory(),
            'status' => 'requested',
            'reason' => fake()->randomElement(['defective', 'wrong_item', 'not_as_described', 'changed_mind', 'other']),
            'description' => fake()->sentence(),
            'items' => null,
            'refund_amount' => null,
            'return_tracking_number' => null,
            'return_tracking_carrier' => null,
            'approved_at' => null,
            'received_at' => null,
            'refunded_at' => null,
        ];
    }
}
