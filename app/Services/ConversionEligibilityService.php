<?php

namespace App\Services;

use App\Events\PostConversionPrompted;
use App\Models\Post;
use Illuminate\Support\Facades\DB;

class ConversionEligibilityService
{
    /**
     * Check if post should be prompted for conversion and dispatch event if eligible
     */
    public function checkAndPrompt(Post $post): array
    {
        // Idempotency check
        if (! $this->shouldPrompt($post)) {
            return [
                'should_prompt' => false,
                'reason' => $this->getNoPromptReason($post),
            ];
        }

        $threshold = $this->getThresholdLevel($post);

        // Mark as prompted (idempotent)
        DB::transaction(function () use ($post) {
            $post->update(['conversion_prompted_at' => now()]);
        });

        // Dispatch event for notification
        event(new PostConversionPrompted($post, $threshold));

        return [
            'should_prompt' => true,
            'threshold' => $threshold,
            'reaction_count' => $post->reaction_count,
        ];
    }

    /**
     * Determine if post should be prompted for conversion
     */
    protected function shouldPrompt(Post $post): bool
    {
        // Not eligible
        if (! $post->isEligibleForConversion()) {
            return false;
        }

        // Already prompted and not time to re-prompt
        if ($post->conversion_prompted_at && ! $post->shouldReprompt()) {
            return false;
        }

        // Reached dismiss limit
        if ($post->hasReachedDismissLimit()) {
            return false;
        }

        return true;
    }

    /**
     * Get threshold level based on reaction count
     */
    protected function getThresholdLevel(Post $post): string
    {
        if ($post->reaction_count >= Post::CONVERSION_STRONG_THRESHOLD) {
            return 'strong';
        }

        return 'soft';
    }

    /**
     * Get reason why post should not be prompted
     */
    protected function getNoPromptReason(Post $post): string
    {
        if ($post->status !== 'active') {
            return 'post_not_active';
        }

        if ($post->reaction_count < Post::CONVERSION_SOFT_THRESHOLD) {
            return 'insufficient_reactions';
        }

        if ($post->hasReachedDismissLimit()) {
            return 'dismiss_limit_reached';
        }

        if ($post->conversion_prompted_at && ! $post->shouldReprompt()) {
            return 'already_prompted';
        }

        return 'unknown';
    }
}
