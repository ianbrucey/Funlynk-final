<?php

namespace Database\Factories\Poster;

use App\Poster\Enums\Platform;
use App\Poster\Enums\PlatformPostStatus;
use App\Poster\Models\BundlePlatformPost;
use App\Poster\Models\PostBundle;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<BundlePlatformPost>
 */
class BundlePlatformPostFactory extends Factory
{
    protected $model = BundlePlatformPost::class;

    public function definition(): array
    {
        return [
            'bundle_id' => PostBundle::factory(),
            'platform_account_id' => null,
            'platform' => Platform::X,
            'adapted_caption' => 'Sunset Skate is back this Tuesday, 6–9 PM at Junipero Beach.',
            'normalized_assets' => [],
            'status' => PlatformPostStatus::Pending,
        ];
    }
}
