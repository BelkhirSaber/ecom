<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Promotion>
 */
class PromotionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->words(3, true),
            'type' => fake()->randomElement(['product', 'category', 'cart']),
            'discount_type' => fake()->randomElement(['fixed', 'percentage']),
            'discount_value' => fake()->randomFloat(2, 5, 50),
            'applicable_products' => null,
            'applicable_categories' => null,
            'min_order_amount' => fake()->optional()->randomFloat(2, 20, 100),
            'priority' => fake()->numberBetween(0, 100),
            'starts_at' => fake()->optional()->dateTimeBetween('-1 month', 'now'),
            'expires_at' => fake()->optional()->dateTimeBetween('now', '+6 months'),
            'is_active' => true,
        ];
    }
}
