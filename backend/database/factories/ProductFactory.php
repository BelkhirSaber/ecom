<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Product>
 */
class ProductFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'category_id' => null,
            'type' => 'simple',
            'sku' => strtoupper($this->faker->unique()->bothify('SKU-#####')),
            'name' => $this->faker->unique()->words(3, true),
            'slug' => $this->faker->unique()->slug(),
            'short_description' => $this->faker->sentence(),
            'description' => $this->faker->paragraphs(3, true),
            'price' => $this->faker->randomFloat(2, 10, 500),
            'compare_price' => $this->faker->optional()->randomFloat(2, 15, 600),
            'cost_price' => $this->faker->optional()->randomFloat(2, 5, 300),
            'currency' => 'USD',
            'stock_quantity' => $this->faker->numberBetween(0, 100),
            'stock_status' => $this->faker->randomElement(['in_stock', 'out_of_stock', 'preorder']),
            'is_active' => true,
            'weight' => $this->faker->optional()->randomFloat(2, 0.1, 10),
            'width' => $this->faker->optional()->randomFloat(2, 5, 100),
            'height' => $this->faker->optional()->randomFloat(2, 5, 100),
            'length' => $this->faker->optional()->randomFloat(2, 5, 100),
            'attributes' => [
                'color' => $this->faker->safeColorName(),
                'size' => $this->faker->randomElement(['XS', 'S', 'M', 'L', 'XL']),
            ],
            'meta_title' => $this->faker->sentence(3),
            'meta_description' => $this->faker->sentence(12),
            'meta_keywords' => implode(',', $this->faker->words(5)),
            'published_at' => $this->faker->optional()->dateTimeBetween('-1 year', 'now'),
        ];
    }
}
