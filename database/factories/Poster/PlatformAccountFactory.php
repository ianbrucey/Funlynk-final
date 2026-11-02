<?php

namespace Database\Factories\Poster;

use App\Poster\Enums\Platform;
use App\Poster\Models\PlatformAccount;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PlatformAccount>
 */
class PlatformAccountFactory extends Factory
{
    protected $model = PlatformAccount::class;

    public function definition(): array
    {
        return [
            'platform' => Platform::Facebook,
            'label' => 'FunLink main',
            'handle' => '@funlink',
            'external_id' => null,
            'status' => PlatformAccount::STATUS_PENDING_SETUP,
        ];
    }

    public function connected(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => PlatformAccount::STATUS_CONNECTED,
            'access_token' => 'test-token-not-a-real-secret',
            'external_id' => '1234567890',
        ]);
    }
}
