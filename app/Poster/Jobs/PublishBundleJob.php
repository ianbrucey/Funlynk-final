<?php

namespace App\Poster\Jobs;

use App\Poster\Models\PostBundle;
use App\Poster\Services\PostingService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

/**
 * Queued entry point for publishing. All enforcement lives in
 * PostingService — this job refuses unapproved bundles exactly like a
 * direct call does, because it *is* a direct call.
 */
class PublishBundleJob implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly int $bundleId,
    ) {}

    public function handle(PostingService $posting): void
    {
        $bundle = PostBundle::findOrFail($this->bundleId);

        $posting->publishBundle($bundle);
    }
}
