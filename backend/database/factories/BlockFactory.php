<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Block>
 */
class BlockFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $type = fake()->randomElement(['slider', 'banner', 'featured_products', 'text', 'html']);
        
        $content = match($type) {
            'slider' => [
                'slides' => [
                    [
                        'image' => fake()->imageUrl(),
                        'title' => fake()->sentence(),
                        'link' => fake()->url(),
                    ]
                ],
                'autoplay' => true,
                'interval' => 5000,
            ],
            'banner' => [
                'image' => fake()->imageUrl(),
                'text' => fake()->sentence(),
                'link' => fake()->url(),
            ],
            'featured_products' => [
                'product_ids' => fake()->randomElements([1, 2, 3, 4, 5], 4),
                'display_mode' => 'grid',
                'columns' => 4,
            ],
            'text' => [
                'text' => fake()->paragraph(),
                'alignment' => 'center',
            ],
            'html' => [
                'html' => '<div>' . fake()->paragraph() . '</div>',
            ],
        };
        
        return [
            'key' => fake()->unique()->slug(),
            'type' => $type,
            'title' => fake()->optional()->sentence(3),
            'content' => $content,
            'order' => fake()->numberBetween(0, 100),
            'is_active' => true,
        ];
    }
}
