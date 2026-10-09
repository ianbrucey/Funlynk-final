<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\PostConversion>
 */
class PostConversionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'post_id' => \App\Models\Post::factory(),
            'event_id' => \App\Models\Activity::factory(),
            'trigger_type' => fake()->randomElement(['manual', 'automatic', 'threshold']),
            'reactions_at_conversion' => fake()->numberBetween(0, 20),
            'comments_at_conversion' => 0,
            'views_at_conversion' => fake()->numberBetween(0, 200),
            'rsvp_conversion_rate' => null,
            'interested_users_notified' => 0,
            'invited_users_notified' => 0,
            'notification_sent_at' => null,
            'created_at' => now(),
        ];
    }
}
