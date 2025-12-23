<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Page>
 */
class PageFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'title' => fake()->sentence(3),
            'slug' => fake()->unique()->slug(),
            'content' => fake()->paragraphs(5, true),
            'meta_description' => fake()->sentence(),
            'meta_keywords' => fake()->words(5),
            'is_published' => true,
            'order' => fake()->numberBetween(0, 100),
        ];
    }
}
