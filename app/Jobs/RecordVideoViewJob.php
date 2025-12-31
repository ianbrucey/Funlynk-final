<?php

namespace App\Jobs;

use App\Services\VideoAnalyticsService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;

class RecordVideoViewJob implements ShouldQueue
{
    use InteractsWithQueue, Queueable;

    public int $tries = 3;
    public int $timeout = 30;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public string $videoId,
        public ?string $userId,
        public array $metadata = []
    ) {}

    /**
     * Execute the job.
     */
    public function handle(VideoAnalyticsService $analytics): void
    {
        $analytics->recordViewSync(
            $this->videoId,
            $this->userId,
            $this->metadata
        );
    }

    /**
     * Get the tags that should be assigned to the job.
     */
    public function tags(): array
    {
        return [
            'video-analytics',
            'video:' . $this->videoId,
        ];
    }
}
