<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\SocialAccount>
 */
class SocialAccountFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $provider = fake()->randomElement(['google', 'facebook']);

        return [
            'user_id' => User::factory(),
            'provider' => $provider,
            'provider_id' => (string) fake()->uuid(),
            'provider_email' => fake()->safeEmail(),
            'name' => fake()->name(),
            'nickname' => fake()->userName(),
            'avatar_url' => fake()->imageUrl(),
            'token' => Str::random(60),
            'refresh_token' => Str::random(60),
            'token_expires_at' => now()->addDays(7),
            'meta' => [
                'locale' => fake()->locale(),
            ],
        ];
    }
}
