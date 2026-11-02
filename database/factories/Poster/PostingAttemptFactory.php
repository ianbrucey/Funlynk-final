<?php

namespace Database\Factories\Poster;

use App\Poster\Enums\Platform;
use App\Poster\Models\BundlePlatformPost;
use App\Poster\Models\PostingAttempt;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PostingAttempt>
 */
class PostingAttemptFactory extends Factory
{
    protected $model = PostingAttempt::class;

    public function definition(): array
    {
        return [
            'bundle_platform_post_id' => BundlePlatformPost::factory(),
            'platform' => Platform::X,
            'attempt_number' => 1,
            'status' => PostingAttempt::STATUS_STARTED,
            'started_at' => now(),
        ];
    }
}
