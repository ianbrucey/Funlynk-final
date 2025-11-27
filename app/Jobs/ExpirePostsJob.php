<?php

namespace App\Jobs;

use App\Services\PostService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class ExpirePostsJob implements ShouldQueue
{
    use Queueable;

    /**
     * Execute the job.
     */
    public function handle(PostService $postService): void
    {
        // Mark all overdue posts as expired. The exact count is currently unused
        // but kept for potential future logging/metrics.
        $postService->expirePosts();
    }
}
