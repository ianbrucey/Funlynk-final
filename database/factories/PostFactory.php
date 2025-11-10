<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Post>
 */
class PostFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => \App\Models\User::factory(),
            'content' => fake()->sentence(10),
            'tags' => fake()->randomElements(['sports', 'music', 'outdoors', 'gaming', 'travel'], 2),
            'location_name' => fake()->optional()->city(),
            'geo_hash' => null,
            'approximate_time' => fake()->optional()->dateTimeBetween('now', '+2 days'),
            'expires_at' => now()->addHours(fake()->numberBetween(12, 72)),
            'mood' => fake()->optional()->randomElement(['creative', 'social', 'active', 'chill', 'adventurous']),
            'evolved_to_event_id' => null,
            'conversion_triggered_at' => null,
            'view_count' => fake()->numberBetween(0, 500),
            'reaction_count' => fake()->numberBetween(0, 200),
        ];
    }
}
