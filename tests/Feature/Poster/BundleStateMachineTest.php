<?php

namespace Tests\Feature\Poster;

use App\Poster\Contracts\PlatformPoster;
use App\Poster\Enums\Platform;
use App\Poster\Enums\PlatformPostStatus;
use App\Poster\Enums\PostBundleState;
use App\Poster\Exceptions\InvalidStateTransitionException;
use App\Poster\Exceptions\PostingNotAllowedException;
use App\Poster\Models\BundlePlatformPost;
use App\Poster\Models\PostBundle;
use App\Poster\Models\PostingAttempt;
use App\Poster\Services\PosterRegistry;
use App\Poster\Services\PostingService;
use App\Poster\Support\PlatformCapabilities;
use App\Poster\Support\PublishResult;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BundleStateMachineTest extends TestCase
{
    use RefreshDatabase;

    private function fakePoster(Platform $platform): PlatformPoster
    {
        return new class($platform) implements PlatformPoster
        {
            public function __construct(private Platform $p) {}

            public function platform(): Platform
            {
                return $this->p;
            }

            public function capabilities(): PlatformCapabilities
            {
                return new PlatformCapabilities(
                    supportsTextOnly: true,
                    supportsPhotos: true,
                    supportsVideo: true,
                    supportsCarousel: false,
                    requiresMedia: false,
                    mediaMustBePublicUrl: false,
                    maxTextLength: 280,
                );
            }

            public function requiredConfigKeys(): array
            {
                return [];
            }

            public function adaptCopy(string $caption): string
            {
                return $caption;
            }

            public function publish(BundlePlatformPost $post): PublishResult
            {
                return new PublishResult('fake-post-123', 'https://example.test/p/fake-post-123');
            }
        };
    }

    public function test_posting_service_refuses_a_draft_bundle(): void
    {
        $bundle = PostBundle::factory()->create();

        $this->expectException(PostingNotAllowedException::class);
        app(PostingService::class)->publishBundle($bundle);
    }

    public function test_posting_service_refuses_a_pending_approval_bundle(): void
    {
        $bundle = PostBundle::factory()->create();
        $bundle->submitForApproval();

        $this->expectException(PostingNotAllowedException::class);
        app(PostingService::class)->publishBundle($bundle);
    }

    public function test_approval_is_recorded_with_who_and_when(): void
    {
        $bundle = PostBundle::factory()->create();

        $bundle->submitForApproval();
        $bundle->approve('ian');

        $bundle->refresh();
        $this->assertSame(PostBundleState::Approved, $bundle->state);
        $this->assertSame('ian', $bundle->approved_by);
        $this->assertNotNull($bundle->approved_at);
    }

    public function test_a_bundle_cannot_skip_pending_approval(): void
    {
        $bundle = PostBundle::factory()->create();

        $this->expectException(InvalidStateTransitionException::class);
        $bundle->approve('ian'); // draft -> approved directly is not a legal transition
    }

    public function test_approved_bundle_posts_via_registered_adapter(): void
    {
        $registry = app(PosterRegistry::class);
        $registry->register($this->fakePoster(Platform::X));
        $posting = new PostingService($registry);

        $bundle = PostBundle::factory()->approved()->create();
        $posting->preparePlatformPosts($bundle, [Platform::X]);

        $posting->publishBundle($bundle);

        $bundle->refresh();
        $this->assertSame(PostBundleState::Posted, $bundle->state);
        $this->assertNotNull($bundle->completed_at);

        $post = $bundle->platformPosts()->sole();
        $this->assertSame(PlatformPostStatus::Posted, $post->status);
        $this->assertSame('fake-post-123', $post->platform_post_id);
        $this->assertSame(PostingAttempt::STATUS_SUCCEEDED, $post->attempts()->sole()->status);
    }

    public function test_approved_bundle_with_unconfigured_platform_fails_on_the_record(): void
    {
        // Real XPoster, no connected account -> NotConfiguredException path.
        $posting = app(PostingService::class);

        $bundle = PostBundle::factory()->approved()->create();
        $posting->preparePlatformPosts($bundle, [Platform::X]);

        $posting->publishBundle($bundle);

        $bundle->refresh();
        $this->assertSame(PostBundleState::Failed, $bundle->state);

        $post = $bundle->platformPosts()->sole();
        $this->assertSame(PlatformPostStatus::Failed, $post->status);
        $this->assertStringContainsString('not configured', $post->error);

        $attempt = $post->attempts()->sole();
        $this->assertSame(PostingAttempt::STATUS_FAILED, $attempt->status);
        $this->assertNotNull($attempt->finished_at);
    }

    public function test_mixed_outcomes_land_on_partially_failed(): void
    {
        $registry = app(PosterRegistry::class);
        $registry->register($this->fakePoster(Platform::Threads)); // succeeds
        // Facebook stays the real (unconfigured) adapter -> fails.
        $posting = new PostingService($registry);

        $bundle = PostBundle::factory()->approved()->create();
        $posting->preparePlatformPosts($bundle, [Platform::Threads, Platform::Facebook]);

        $posting->publishBundle($bundle);

        $this->assertSame(PostBundleState::PartiallyFailed, $bundle->refresh()->state);
    }
}
