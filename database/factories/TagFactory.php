<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Tag>
 */
class TagFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->unique()->word(),
            'category' => fake()->randomElement(['sports', 'music', 'social', 'outdoors']),
            'description' => fake()->sentence(),
            'usage_count' => fake()->numberBetween(0, 1000),
            'is_featured' => fake()->boolean(10),
            'created_at' => now(),
        ];
    }
}
