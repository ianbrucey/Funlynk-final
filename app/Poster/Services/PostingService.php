<?php

namespace App\Poster\Services;

use App\Poster\Enums\Platform;
use App\Poster\Enums\PlatformPostStatus;
use App\Poster\Enums\PostBundleState;
use App\Poster\Exceptions\PostingNotAllowedException;
use App\Poster\Models\BundlePlatformPost;
use App\Poster\Models\PostBundle;
use App\Poster\Models\PostingAttempt;
use Throwable;

/**
 * The only path that publishes bundles.
 *
 * APPROVAL RULE (hard requirement): publishBundle() refuses any bundle whose
 * state is not `approved` — with approved_by/approved_at on record. Intake,
 * scheduling, and jobs all funnel through here, so there is no side door.
 */
class PostingService
{
    public function __construct(
        private readonly PosterRegistry $registry,
    ) {}

    /**
     * Fan a bundle out to one BundlePlatformPost per target platform,
     * with copy adapted by that platform's adapter. Safe to call on a draft:
     * it prepares posts, it never publishes.
     *
     * @param  list<Platform>  $platforms
     * @return list<BundlePlatformPost>
     */
    public function preparePlatformPosts(PostBundle $bundle, array $platforms): array
    {
        $posts = [];

        foreach ($platforms as $platform) {
            $poster = $this->registry->for($platform);

            $posts[] = $bundle->platformPosts()->firstOrCreate(
                ['platform' => $platform],
                [
                    'adapted_caption' => $poster->adaptCopy($bundle->caption),
                    'status' => PlatformPostStatus::Pending,
                ]
            );
        }

        return $posts;
    }

    /**
     * @throws PostingNotAllowedException when the bundle is not approved.
     */
    public function publishBundle(PostBundle $bundle): PostBundle
    {
        $bundle->refresh();

        if ($bundle->state !== PostBundleState::Approved || blank($bundle->approved_by) || $bundle->approved_at === null) {
            throw new PostingNotAllowedException(
                "Bundle #{$bundle->id} is in state '{$bundle->state->value}' and cannot be posted. ".
                'Posting requires an explicit recorded approval (state=approved with approved_by/approved_at).'
            );
        }

        $bundle->beginPosting();

        foreach ($bundle->platformPosts()->where('status', PlatformPostStatus::Pending)->get() as $post) {
            $this->publishPlatformPost($post);
        }

        $bundle->completePosting();

        return $bundle->refresh();
    }

    private function publishPlatformPost(BundlePlatformPost $post): void
    {
        $poster = $this->registry->for($post->platform);
        $post->update(['status' => PlatformPostStatus::Posting, 'attempted_at' => now()]);

        $attempt = $post->attempts()->create([
            'platform' => $post->platform,
            'attempt_number' => $post->attempts()->count() + 1,
            'status' => PostingAttempt::STATUS_STARTED,
            'started_at' => now(),
        ]);

        try {
            // Shape-check before any network call would happen (Phase 2):
            // a bundle the platform cannot accept fails here, on the record.
            // The check runs against the adapted copy, since that is what
            // would actually be published.
            $shape = $post->bundle->replicate();
            $shape->caption = $post->adapted_caption ?? $post->bundle->caption;
            $violations = $poster->capabilities()->violationsFor($shape);
            if ($violations !== []) {
                throw new \RuntimeException('Capability check failed: '.implode(' ', $violations));
            }

            $result = $poster->publish($post);

            $attempt->update([
                'status' => PostingAttempt::STATUS_SUCCEEDED,
                'finished_at' => now(),
            ]);
            $post->update([
                'status' => PlatformPostStatus::Posted,
                'platform_post_id' => $result->platformPostId,
                'platform_post_url' => $result->platformPostUrl,
                'error' => null,
            ]);
        } catch (Throwable $e) {
            $attempt->update([
                'status' => PostingAttempt::STATUS_FAILED,
                'error' => $e->getMessage(),
                'finished_at' => now(),
            ]);
            $post->update([
                'status' => PlatformPostStatus::Failed,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
