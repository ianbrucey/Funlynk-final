<?php

namespace Tests\Unit\Poster;

use App\Poster\Enums\Platform;
use App\Poster\Services\MediaNormalizer;
use Tests\TestCase;

class MediaNormalizerTest extends TestCase
{
    private string $tmpDir;

    protected function setUp(): void
    {
        parent::setUp();

        if (! extension_loaded('gd')) {
            $this->markTestSkipped('GD extension is required for MediaNormalizer.');
        }

        $this->tmpDir = sys_get_temp_dir().'/poster-normalizer-'.uniqid();
        mkdir($this->tmpDir, 0775, true);
    }

    protected function tearDown(): void
    {
        foreach (glob($this->tmpDir.'/*') ?: [] as $file) {
            unlink($file);
        }
        rmdir($this->tmpDir);

        parent::tearDown();
    }

    private function makePng(int $width, int $height, string $name): string
    {
        $path = $this->tmpDir.'/'.$name;
        $image = imagecreatetruecolor($width, $height);
        imagepng($image, $path);
        imagedestroy($image);

        return $path;
    }

    public function test_instagram_variant_is_a_jpeg_within_aspect_limits(): void
    {
        // 2000x1000 = 2.0 ratio, too wide for Instagram's 1.91 max.
        $source = $this->makePng(2000, 1000, 'wide-flyer.png');

        $variants = app(MediaNormalizer::class)->variantsFor(Platform::Instagram, $source);

        $this->assertCount(1, $variants);
        $this->assertSame('jpg', $variants[0]['format']);

        $info = getimagesize($variants[0]['path']);
        $this->assertSame('image/jpeg', $info['mime']);

        $ratio = $info[0] / $info[1];
        $this->assertLessThanOrEqual(MediaNormalizer::INSTAGRAM_MAX_RATIO + 0.01, $ratio);
        $this->assertGreaterThanOrEqual(MediaNormalizer::INSTAGRAM_MIN_RATIO - 0.01, $ratio);
        $this->assertLessThanOrEqual(MediaNormalizer::INSTAGRAM_MAX_WIDTH, $info[0]);

        unlink($variants[0]['path']);
    }

    public function test_instagram_variant_handles_a_tall_portrait(): void
    {
        // 800x1600 = 0.5 ratio, too tall for Instagram's 0.8 (4:5) min.
        $source = $this->makePng(800, 1600, 'tall-flyer.png');

        $path = app(MediaNormalizer::class)->instagramJpeg($source);
        $info = getimagesize($path);

        $this->assertGreaterThanOrEqual(MediaNormalizer::INSTAGRAM_MIN_RATIO - 0.01, $info[0] / $info[1]);

        unlink($path);
    }

    public function test_non_instagram_platforms_pass_images_through(): void
    {
        $source = $this->makePng(600, 600, 'square.png');

        $variants = app(MediaNormalizer::class)->variantsFor(Platform::Threads, $source);

        $this->assertCount(1, $variants);
        $this->assertSame('png', $variants[0]['format']);
        $this->assertFileExists($variants[0]['path']);

        unlink($variants[0]['path']);
    }
}
