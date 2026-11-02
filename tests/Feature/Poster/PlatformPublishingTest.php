<?php

namespace Tests\Feature\Poster;

use App\Poster\Enums\Platform;
use App\Poster\Enums\PlatformPostStatus;
use App\Poster\Models\BundlePlatformPost;
use App\Poster\Models\PlatformAccount;
use App\Poster\Models\PostBundle;
use App\Poster\Models\PostingAttempt;
use App\Poster\Services\PostingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * Phase 2 adapter tests. Every platform call is Http::fake'd — zero real
 * network traffic — and publishing always goes through PostingService, so
 * the approval gate is exercised on every path.
 */
class PlatformPublishingTest extends TestCase
{
    use RefreshDatabase;

    private function connectedAccount(Platform $platform, string $externalId): PlatformAccount
    {
        return PlatformAccount::factory()->connected()->create([
            'platform' => $platform,
            'external_id' => $externalId,
            'access_token' => 'test-token-not-a-real-secret',
        ]);
    }

    private function pendingPost(PostBundle $bundle, PlatformAccount $account, array $attributes = []): BundlePlatformPost
    {
        return BundlePlatformPost::factory()->create(array_merge([
            'bundle_id' => $bundle->id,
            'platform_account_id' => $account->id,
            'platform' => $account->platform,
            'adapted_caption' => $bundle->caption,
            'status' => PlatformPostStatus::Pending,
        ], $attributes));
    }

    public function test_facebook_photo_post_publishes_and_records_the_post_id(): void
    {
        Http::fake(function (Request $request) {
            if (str_contains($request->url(), '/photos')) {
                return Http::response(['id' => 'photo-1', 'post_id' => '61563258179586_555'], 200);
            }

            return Http::response([], 404);
        });

        $bundle = PostBundle::factory()->approved()->create();
        $account = $this->connectedAccount(Platform::Facebook, '61563258179586');
        $post = $this->pendingPost($bundle, $account, [
            'normalized_assets' => [['kind' => 'image', 'url' => 'https://cdn.example.test/flyer.jpg', 'format' => 'jpg']],
        ]);

        app(PostingService::class)->publishBundle($bundle);

        $post->refresh();
        $this->assertSame(PlatformPostStatus::Posted, $post->status);
        $this->assertSame('61563258179586_555', $post->platform_post_id);
        $this->assertSame(PostingAttempt::STATUS_SUCCEEDED, $post->attempts()->first()?->status);
    }

    public function test_facebook_text_post_publishes_to_the_feed(): void
    {
        Http::fake(function (Request $request) {
            if (str_contains($request->url(), '/feed')) {
                return Http::response(['id' => '61563258179586_777'], 200);
            }

            return Http::response([], 404);
        });

        $bundle = PostBundle::factory()->approved()->textOnly()->create();
        $account = $this->connectedAccount(Platform::Facebook, '61563258179586');
        $post = $this->pendingPost($bundle, $account);

        app(PostingService::class)->publishBundle($bundle);

        $post->refresh();
        $this->assertSame(PlatformPostStatus::Posted, $post->status);
        $this->assertSame('61563258179586_777', $post->platform_post_id);
    }

    public function test_facebook_api_failure_is_recorded_with_the_api_error_message(): void
    {
        Http::fake([
            'https://graph.facebook.com/*' => Http::response(
                ['error' => ['message' => 'Invalid OAuth access token.', 'code' => 190]],
                400
            ),
        ]);

        $bundle = PostBundle::factory()->approved()->textOnly()->create();
        $account = $this->connectedAccount(Platform::Facebook, '61563258179586');
        $post = $this->pendingPost($bundle, $account);

        app(PostingService::class)->publishBundle($bundle);

        $post->refresh();
        $this->assertSame(PlatformPostStatus::Failed, $post->status);
        $this->assertStringContainsString('Invalid OAuth access token.', (string) $post->error);

        $attempt = $post->attempts()->first();
        $this->assertSame(PostingAttempt::STATUS_FAILED, $attempt?->status);
        $this->assertStringContainsString('Invalid OAuth access token.', (string) $attempt?->error);
    }

    public function test_instagram_container_flow_publishes_and_records_the_media_id(): void
    {
        Http::fake(function (Request $request) {
            $url = $request->url();

            if (str_contains($url, '/media_publish')) {
                return Http::response(['id' => 'media-1'], 200);
            }

            if (str_contains($url, 'container-1')) {
                return Http::response(['status_code' => 'FINISHED'], 200);
            }

            if (str_contains($url, 'media-1')) {
                return Http::response(['permalink' => 'https://www.instagram.com/p/abc123/'], 200);
            }

            if (str_ends_with($url, '/media')) {
                return Http::response(['id' => 'container-1'], 200);
            }

            return Http::response([], 404);
        });

        $bundle = PostBundle::factory()->approved()->create();
        $account = $this->connectedAccount(Platform::Instagram, 'ig-123');
        $post = $this->pendingPost($bundle, $account, [
            'normalized_assets' => [['kind' => 'image', 'url' => 'https://cdn.example.test/flyer.jpg', 'format' => 'jpg']],
        ]);

        app(PostingService::class)->publishBundle($bundle);

        $post->refresh();
        $this->assertSame(PlatformPostStatus::Posted, $post->status);
        $this->assertSame('media-1', $post->platform_post_id);
        $this->assertSame('https://www.instagram.com/p/abc123/', $post->platform_post_url);
    }

    public function test_instagram_refuses_media_without_a_public_url(): void
    {
        Http::fake();
        config(['poster.asset_base_url' => null]);

        $bundle = PostBundle::factory()->approved()->create();
        $account = $this->connectedAccount(Platform::Instagram, 'ig-123');
        $post = $this->pendingPost($bundle, $account, [
            'normalized_assets' => [['kind' => 'image', 'path' => '/definitely/not/public/flyer.jpg', 'format' => 'jpg']],
        ]);

        app(PostingService::class)->publishBundle($bundle);

        $post->refresh();
        $this->assertSame(PlatformPostStatus::Failed, $post->status);
        $this->assertStringContainsString('public URL', (string) $post->error);
        Http::assertNothingSent();
    }

    public function test_tiktok_refuses_a_bundle_without_video_before_any_network_call(): void
    {
        Http::fake();

        $bundle = PostBundle::factory()->approved()->create(); // image asset only
        $account = $this->connectedAccount(Platform::TikTok, 'open-1');
        $post = $this->pendingPost($bundle, $account);

        app(PostingService::class)->publishBundle($bundle);

        $post->refresh();
        $this->assertSame(PlatformPostStatus::Failed, $post->status);
        $this->assertStringContainsString('video', (string) $post->error);
        Http::assertNothingSent();
    }

    public function test_tiktok_upload_init_failure_is_recorded_with_the_api_error(): void
    {
        $videoPath = $this->writeFakeVideo();

        Http::fake([
            'https://open.tiktokapis.com/*' => Http::response([
                'error' => ['code' => 'spam_risk_too_many_posts', 'message' => 'Too many posts in the last 24 hours.'],
            ], 200),
        ]);

        try {
            $bundle = PostBundle::factory()->approved()->video()->create();
            $account = $this->connectedAccount(Platform::TikTok, 'open-1');
            $post = $this->pendingPost($bundle, $account);

            app(PostingService::class)->publishBundle($bundle);

            $post->refresh();
            $this->assertSame(PlatformPostStatus::Failed, $post->status);
            $this->assertStringContainsString('Too many posts', (string) $post->error);
        } finally {
            @unlink($videoPath);
        }
    }

    public function test_tiktok_video_upload_lands_in_drafts_and_records_the_publish_id(): void
    {
        $videoPath = $this->writeFakeVideo();

        Http::fake(function (Request $request) {
            $url = $request->url();

            if (str_contains($url, '/inbox/video/init/')) {
                return Http::response([
                    'data' => ['publish_id' => 'pub-123', 'upload_url' => 'https://upload.tiktokapis.com/video/abc'],
                    'error' => ['code' => 'ok', 'message' => ''],
                ], 200);
            }

            if (str_contains($url, 'upload.tiktokapis.com')) {
                return Http::response('', 200);
            }

            if (str_contains($url, '/status/fetch/')) {
                return Http::response([
                    'data' => ['status' => 'SEND_TO_USER_INBOX'],
                    'error' => ['code' => 'ok', 'message' => ''],
                ], 200);
            }

            return Http::response([], 404);
        });

        try {
            $bundle = PostBundle::factory()->approved()->video()->create();
            $account = $this->connectedAccount(Platform::TikTok, 'open-1');
            $post = $this->pendingPost($bundle, $account);

            app(PostingService::class)->publishBundle($bundle);

            $post->refresh();
            $this->assertSame(PlatformPostStatus::Posted, $post->status);
            $this->assertSame('pub-123', $post->platform_post_id);

            Http::assertSent(fn (Request $request): bool => $request->method() === 'PUT'
                && str_contains($request->url(), 'upload.tiktokapis.com')
                && $request->hasHeader('Content-Range'));
        } finally {
            @unlink($videoPath);
        }
    }

    /**
     * The TikTok FILE_UPLOAD flow reads the video from local disk; create
     * the file the bundle factory points at (poster/intake/test/recap.mp4).
     */
    private function writeFakeVideo(): string
    {
        $path = storage_path('app/poster/intake/test/recap.mp4');

        if (! is_dir(dirname($path))) {
            mkdir(dirname($path), 0775, true);
        }

        file_put_contents($path, str_repeat('fake-video-bytes', 128));

        return $path;
    }
}
