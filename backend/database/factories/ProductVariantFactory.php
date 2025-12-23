<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ProductVariant>
 */
class ProductVariantFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'product_id' => null,
            'sku' => strtoupper($this->faker->unique()->bothify('VAR-#####')),
            'name' => $this->faker->words(2, true),
            'price' => $this->faker->randomFloat(2, 5, 300),
            'compare_price' => $this->faker->optional()->randomFloat(2, 6, 350),
            'cost_price' => $this->faker->optional()->randomFloat(2, 3, 200),
            'currency' => 'USD',
            'stock_quantity' => $this->faker->numberBetween(0, 200),
            'stock_status' => $this->faker->randomElement(['in_stock', 'out_of_stock', 'preorder']),
            'is_active' => true,
            'attributes' => [
                'color' => $this->faker->safeColorName(),
                'size' => $this->faker->randomElement(['XS', 'S', 'M', 'L', 'XL']),
            ],
            'weight' => $this->faker->optional()->randomFloat(2, 0.05, 5),
            'width' => $this->faker->optional()->randomFloat(2, 2, 50),
            'height' => $this->faker->optional()->randomFloat(2, 2, 50),
            'length' => $this->faker->optional()->randomFloat(2, 2, 50),
        ];
    }
}
