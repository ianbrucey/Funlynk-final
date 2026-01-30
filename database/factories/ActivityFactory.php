<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use MatanYadaev\EloquentSpatial\Objects\Point;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Activity>
 */
class ActivityFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'host_id' => \App\Models\User::factory(),
            'title' => fake()->sentence(4),
            'description' => fake()->paragraph(),
            'activity_type' => fake()->randomElement(['sports', 'music', 'social', 'outdoor']),
            'location_name' => fake()->city(),
            'location_coordinates' => new Point(fake()->latitude(), fake()->longitude()),
            'start_time' => now()->addDays(fake()->numberBetween(1, 10)),
            'end_time' => null,
            'max_attendees' => fake()->optional(0.7)->numberBetween(5, 30),
            'current_attendees' => 0,
            'payment_type' => 'free',
            'price_cents' => null,
            'currency' => 'USD',
            'is_public' => true,
            'requires_approval' => false,
            'images' => [],
            'status' => 'active',
            'originated_from_post_id' => null,
            'conversion_date' => null,
        ];
    }
}
