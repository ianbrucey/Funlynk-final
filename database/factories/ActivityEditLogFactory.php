<?php

namespace Database\Factories;

use App\Models\Activity;
use App\Models\ActivityEditLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ActivityEditLog>
 */
class ActivityEditLogFactory extends Factory
{
    protected $model = ActivityEditLog::class;

    public function definition(): array
    {
        return [
            'activity_id' => Activity::factory(),
            'editor_id' => User::factory(),
            'field_name' => fake()->randomElement(['title', 'start_time', 'location_name', 'description']),
            'old_value' => fake()->sentence(),
            'new_value' => fake()->sentence(),
            'change_category' => fake()->randomElement(['cosmetic', 'minor', 'significant']),
            'reason' => fake()->optional()->sentence(),
            'triggered_refund_window' => false,
            'paid_attendee_count' => fake()->numberBetween(0, 50),
        ];
    }

    public function cosmetic(): static
    {
        return $this->state(fn (array $attributes) => [
            'field_name' => 'description',
            'change_category' => 'cosmetic',
            'triggered_refund_window' => false,
        ]);
    }

    public function minor(): static
    {
        return $this->state(fn (array $attributes) => [
            'field_name' => 'end_time',
            'change_category' => 'minor',
            'triggered_refund_window' => false,
        ]);
    }

    public function significant(): static
    {
        return $this->state(fn (array $attributes) => [
            'field_name' => 'start_time',
            'change_category' => 'significant',
            'triggered_refund_window' => true,
        ]);
    }
}
