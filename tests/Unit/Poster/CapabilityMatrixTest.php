<?php

namespace Tests\Unit\Poster;

use App\Poster\Enums\Platform;
use App\Poster\Models\PostBundle;
use App\Poster\Services\PosterRegistry;
use Tests\TestCase;

class CapabilityMatrixTest extends TestCase
{
    private function bundle(array $assets, string $caption = 'Hello FunLink'): PostBundle
    {
        return new PostBundle(['caption' => $caption, 'assets' => $assets]);
    }

    public function test_all_six_platforms_have_adapters(): void
    {
        $registry = app(PosterRegistry::class);

        foreach (Platform::cases() as $platform) {
            $this->assertSame($platform, $registry->for($platform)->platform());
        }
    }

    public function test_youtube_rejects_text_only_and_photo_bundles(): void
    {
        $youtube = app(PosterRegistry::class)->for(Platform::YouTube);

        $this->assertFalse($youtube->capabilities()->supportsTextOnly);
        $this->assertFalse($youtube->capabilities()->supportsPhotos);

        $this->assertNotEmpty($youtube->capabilities()->violationsFor($this->bundle([])));
        $this->assertNotEmpty($youtube->capabilities()->violationsFor(
            $this->bundle([['kind' => 'image', 'name' => 'flyer.jpg']])
        ));
        $this->assertEmpty($youtube->capabilities()->violationsFor(
            $this->bundle([['kind' => 'video', 'name' => 'recap.mp4']])
        ));
    }

    public function test_instagram_rejects_text_only_but_accepts_an_image(): void
    {
        $instagram = app(PosterRegistry::class)->for(Platform::Instagram);

        $this->assertTrue($instagram->capabilities()->requiresMedia);
        $this->assertNotEmpty($instagram->capabilities()->violationsFor($this->bundle([])));
        $this->assertEmpty($instagram->capabilities()->violationsFor(
            $this->bundle([['kind' => 'image', 'name' => 'flyer.jpg']])
        ));
    }

    public function test_x_accepts_text_only_and_enforces_280_chars(): void
    {
        $x = app(PosterRegistry::class)->for(Platform::X);

        $this->assertEmpty($x->capabilities()->violationsFor($this->bundle([])));
        $this->assertNotEmpty($x->capabilities()->violationsFor(
            $this->bundle([], str_repeat('a', 281))
        ));
    }

    public function test_x_adapter_truncates_long_copy_to_280(): void
    {
        $x = app(PosterRegistry::class)->for(Platform::X);

        $adapted = $x->adaptCopy(str_repeat('a', 300));
        $this->assertSame(280, mb_strlen($adapted));
    }

    public function test_threads_and_facebook_accept_text_only(): void
    {
        $registry = app(PosterRegistry::class);

        $this->assertEmpty($registry->for(Platform::Threads)->capabilities()->violationsFor($this->bundle([])));
        $this->assertEmpty($registry->for(Platform::Facebook)->capabilities()->violationsFor($this->bundle([])));
    }
}
