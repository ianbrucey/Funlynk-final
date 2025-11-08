<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\User>
 */
class UserFactory extends Factory
{
    /**
     * The current password being used by the factory.
     */
    protected static ?string $password;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'email' => fake()->unique()->safeEmail(),
            'username' => Str::slug(fake()->unique()->userName()),
            'display_name' => fake()->name(),
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'bio' => fake()->paragraph(),
            'profile_image_url' => fake()->imageUrl(),
            'location_name' => fake()->city(),
            'location_coordinates' => [
                'lat' => fake()->latitude(),
                'lng' => fake()->longitude(),
            ],
            'interests' => fake()->randomElements(
                ['sports', 'music', 'outdoors', 'gaming', 'travel'],
                3
            ),
            'is_host' => fake()->boolean(),
            'stripe_account_id' => fake()->uuid(),
            'stripe_onboarding_complete' => fake()->boolean(),
            'follower_count' => fake()->numberBetween(0, 500),
            'following_count' => fake()->numberBetween(0, 500),
            'activity_count' => fake()->numberBetween(0, 50),
            'is_verified' => fake()->boolean(),
            'is_active' => true,
            'privacy_level' => fake()->randomElement(['public', 'friends', 'private']),
            'remember_token' => Str::random(10),
        ];
    }

    /**
     * Indicate that the model's email address should be unverified.
     */
    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }
}
