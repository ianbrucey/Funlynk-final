<?php

namespace App\Jobs;

use App\Events\PostAutoConverted;
use App\Events\PostConversionSuggested;
use App\Models\Post;
use App\Services\PostService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class CheckPostConversionEligibility implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public string $postId
    ) {}

    /**
     * Execute the job.
     */
    public function handle(PostService $postService): void
    {
        $post = Post::find($this->postId);

        if (!$post || $post->status !== 'active') {
            return;
        }

        $eligibility = $postService->checkConversionEligibility($this->postId);

        // Auto-convert at 10+ reactions
        if ($eligibility['auto_convert']) {
            event(new PostAutoConverted($post, $eligibility));
            return;
        }

        // Suggest conversion at 5+ reactions (only once)
        if ($eligibility['eligible'] && !$post->conversion_suggested_at) {
            $post->update(['conversion_suggested_at' => now()]);
            event(new PostConversionSuggested($post, $eligibility));
        }
    }
}
