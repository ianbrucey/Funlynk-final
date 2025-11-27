<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use MatanYadaev\EloquentSpatial\Objects\Point;

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
        $lat = fake()->latitude(37.0, 38.0); // San Francisco area
        $lng = fake()->longitude(-122.5, -122.0);
        
        return [
            'user_id' => \App\Models\User::factory(),
            'title' => fake()->sentence(rand(3, 6)),
            'description' => fake()->optional(0.7)->paragraph(),
            'location_coordinates' => new Point($lat, $lng, 4326),
            'location_name' => fake()->city() . ', CA',
            'time_hint' => fake()->randomElement(['Tonight around 8pm', 'Tomorrow afternoon', 'This weekend', 'Later today', null]),
            'tags' => fake()->randomElements(['sports', 'music', 'outdoors', 'gaming', 'travel', 'food', 'art'], rand(1, 3)),
            'geo_hash' => null,
            'approximate_time' => fake()->optional()->dateTimeBetween('now', '+2 days'),
            'expires_at' => now()->addHours(fake()->numberBetween(24, 48)),
            'status' => 'active',
            'mood' => fake()->optional()->randomElement(['creative', 'social', 'active', 'chill', 'adventurous']),
            'converted_to_activity_id' => null,
            'conversion_suggested_at' => null,
            'view_count' => fake()->numberBetween(0, 50),
            'reaction_count' => 0, // Will be set by reactions
        ];
    }
}
