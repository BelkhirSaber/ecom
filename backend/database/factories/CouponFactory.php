<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Coupon>
 */
class CouponFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'code' => strtoupper(fake()->unique()->lexify('????####')),
            'type' => fake()->randomElement(['fixed', 'percentage']),
            'value' => fake()->randomFloat(2, 5, 50),
            'min_order_amount' => fake()->optional()->randomFloat(2, 20, 100),
            'max_discount_amount' => fake()->optional()->randomFloat(2, 10, 50),
            'usage_limit' => fake()->optional()->numberBetween(10, 1000),
            'usage_count' => 0,
            'usage_limit_per_user' => fake()->optional()->numberBetween(1, 5),
            'starts_at' => fake()->optional()->dateTimeBetween('-1 month', 'now'),
            'expires_at' => fake()->optional()->dateTimeBetween('now', '+6 months'),
            'is_active' => true,
            'applicable_products' => null,
            'applicable_categories' => null,
        ];
    }
}
