<?php

namespace Database\Factories\Poster;

use App\Poster\Enums\PostBundleState;
use App\Poster\Models\PostBundle;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PostBundle>
 */
class PostBundleFactory extends Factory
{
    protected $model = PostBundle::class;

    public function definition(): array
    {
        return [
            'title' => 'Sunset Skate — Tuesday session',
            'caption' => 'Sunset Skate is back this Tuesday, 6–9 PM at Junipero Beach. All wheels welcome.',
            'source' => 'drive',
            'drive_folder_id' => 'test-folder',
            'drive_file_ids' => [],
            'assets' => [
                ['kind' => 'image', 'name' => 'flyer.png', 'mime' => 'image/png', 'local_path' => 'poster/intake/test/flyer.png'],
            ],
            'state' => PostBundleState::Draft,
        ];
    }

    public function textOnly(): static
    {
        return $this->state(fn (array $attributes) => ['assets' => []]);
    }

    public function video(): static
    {
        return $this->state(fn (array $attributes) => [
            'assets' => [
                ['kind' => 'video', 'name' => 'recap.mp4', 'mime' => 'video/mp4', 'local_path' => 'poster/intake/test/recap.mp4'],
            ],
        ]);
    }

    public function approved(): static
    {
        return $this->state(fn (array $attributes) => [
            'state' => PostBundleState::Approved,
            'submitted_at' => now()->subMinutes(10),
            'approved_by' => 'ian',
            'approved_at' => now()->subMinutes(5),
        ]);
    }
}
