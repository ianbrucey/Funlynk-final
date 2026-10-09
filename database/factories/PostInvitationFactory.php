<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\PostInvitation>
 */
class PostInvitationFactory extends Factory
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
            'inviter_id' => \App\Models\User::factory(),
            'invitee_id' => \App\Models\User::factory(),
            'status' => 'pending',
            'created_at' => now(),
        ];
    }
}
