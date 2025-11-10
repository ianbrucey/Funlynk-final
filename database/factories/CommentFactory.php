<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Comment>
 */
class CommentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'activity_id' => \App\Models\Activity::factory(),
            'user_id' => \App\Models\User::factory(),
            'parent_comment_id' => null,
            'content' => fake()->sentence(8),
            'is_edited' => false,
            'is_deleted' => false,
        ];
    }

    public function reply(\App\Models\Comment $parent): static
    {
        return $this->state(fn () => [
            'activity_id' => $parent->activity_id,
            'parent_comment_id' => $parent->id,
        ]);
    }
}
