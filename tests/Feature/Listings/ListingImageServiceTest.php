<?php

namespace Tests\Feature\Listings;

use App\Services\ImageProcessor;
use App\Services\ListingImageException;
use App\Services\ListingImageService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * Spec 020 fix (2026-11-09): ListingImageService failure reporting.
 *
 * storeFromUrl used to relabel EVERY ImageProcessor::store() failure
 * as "image could not be decoded". In a production backfill run all
 * six images "failed to decode" when the real error was an S3
 * configuration exception (missing region). Only a genuine decode
 * failure (Intervention's DecoderException) may keep the decode
 * wording; anything else surfaces its own class and message.
 */
class ListingImageServiceTest extends TestCase
{
    use RefreshDatabase;

    private function jpegBytes(): string
    {
        $image = imagecreatetruecolor(1200, 800);
        ob_start();
        imagejpeg($image);

        return (string) ob_get_clean();
    }

    public function test_storage_layer_failure_surfaces_its_real_message(): void
    {
        Http::fake(['example.com/*' => Http::response($this->jpegBytes(), 200, ['Content-Type' => 'image/jpeg'])]);

        // Stand in for the S3 configuration error seen in production:
        // the storage layer throws before anything is written.
        $processor = new class extends ImageProcessor
        {
            public function store(mixed $source, string $directory, string $preset = 'post', string $disk = 'public'): string
            {
                throw new \RuntimeException('Missing region configuration or region is not a valid AWS region');
            }
        };
        $this->app->instance(ImageProcessor::class, $processor);

        $service = app(ListingImageService::class);

        try {
            $service->storeFromUrl('https://example.com/images/run.jpg');
            $this->fail('A storage failure should throw ListingImageException.');
        } catch (ListingImageException $e) {
            $this->assertStringContainsString('Missing region configuration', $e->getMessage());
            $this->assertStringContainsString(\RuntimeException::class, $e->getMessage());
            $this->assertStringNotContainsString('could not be decoded', $e->getMessage());
        }
    }

    public function test_genuine_decode_failure_still_reports_a_decode_failure(): void
    {
        // Bytes that are not an image at all, behind an image content
        // type: the real processor's decoder rejects them.
        Http::fake(['example.com/*' => Http::response('not an image', 200, ['Content-Type' => 'image/jpeg'])]);

        $service = app(ListingImageService::class);

        try {
            $service->storeFromUrl('https://example.com/images/fake.jpg');
            $this->fail('Undecodable bytes should throw ListingImageException.');
        } catch (ListingImageException $e) {
            $this->assertSame('image could not be decoded', $e->getMessage());
        }
    }
}
