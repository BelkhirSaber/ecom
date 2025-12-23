<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories.Factory<\App\Models\Category>
 */
class CategoryFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'parent_id' => null,
            'name' => $this->faker->unique()->words(2, true),
            'slug' => $this->faker->unique()->slug(),
            'description' => $this->faker->sentence(),
            'is_active' => true,
            'position' => $this->faker->numberBetween(0, 100),
            'meta_title' => $this->faker->sentence(3),
            'meta_description' => $this->faker->sentence(10),
            'meta_keywords' => implode(',', $this->faker->words(5)),
        ];
    }
}
