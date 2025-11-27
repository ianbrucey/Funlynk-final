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
        // Default to Activity, can be overridden
        $commentable = \App\Models\Activity::factory()->create();
        
        return [
            'commentable_type' => get_class($commentable),
            'commentable_id' => $commentable->id,
            'user_id' => \App\Models\User::factory(),
            'parent_comment_id' => null,
            'depth' => 0,
            'content' => fake()->sentence(rand(8, 20)),
            'is_edited' => false,
            'is_deleted' => false,
        ];
    }

    /**
     * Create a comment on a Post instead of Activity.
     */
    public function forPost(\App\Models\Post $post): static
    {
        return $this->state(fn () => [
            'commentable_type' => \App\Models\Post::class,
            'commentable_id' => $post->id,
        ]);
    }

    /**
     * Create a comment on an Activity.
     */
    public function forActivity(\App\Models\Activity $activity): static
    {
        return $this->state(fn () => [
            'commentable_type' => \App\Models\Activity::class,
            'commentable_id' => $activity->id,
        ]);
    }

    /**
     * Create a reply to an existing comment.
     */
    public function reply(\App\Models\Comment $parent): static
    {
        return $this->state(fn () => [
            'commentable_type' => $parent->commentable_type,
            'commentable_id' => $parent->commentable_id,
            'parent_comment_id' => $parent->id,
            'depth' => $parent->depth + 1,
        ]);
    }

    /**
     * Create an edited comment.
     */
    public function edited(): static
    {
        return $this->state(fn () => [
            'is_edited' => true,
        ]);
    }
}
