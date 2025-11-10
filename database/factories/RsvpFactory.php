<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Rsvp>
 */
class RsvpFactory extends Factory
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
            'activity_id' => \App\Models\Activity::factory(),
            'status' => fake()->randomElement(['attending', 'maybe', 'declined']),
            'is_paid' => false,
            'payment_intent_id' => null,
            'payment_status' => null,
        ];
    }
}
