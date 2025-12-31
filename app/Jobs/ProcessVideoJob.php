<?php

namespace App\Jobs;

use App\Events\Video\VideoProcessingFailed;
use App\Events\Video\VideoProcessingStarted;
use App\Models\Video;
use App\Services\VideoProcessingService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Throwable;

class ProcessVideoJob implements ShouldQueue
{
    use InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The number of times the job may be attempted.
     */
    public int $tries = 3;

    /**
     * The maximum number of seconds the job can run.
     */
    public int $timeout = 600; // 10 minutes

    /**
     * The number of seconds to wait before retrying the job.
     */
    public int $backoff = 60;

    /**
     * The maximum number of unhandled exceptions to allow before failing.
     */
    public int $maxExceptions = 2;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public Video $video
    ) {}

    /**
     * Get the unique ID for the job.
     */
    public function uniqueId(): string
    {
        return $this->video->id;
    }

    /**
     * Execute the job.
     */
    public function handle(VideoProcessingService $processor): void
    {
        Log::info("Starting video processing", [
            'video_id' => $this->video->id,
            'attempt' => $this->attempts(),
        ]);

        try {
            $processor->startProcessing($this->video);

            Log::info("Video processing completed", [
                'video_id' => $this->video->id,
                'duration' => $this->video->fresh()->processing_completed_at?->diffInSeconds($this->video->processing_started_at),
            ]);

        } catch (Throwable $e) {
            Log::error("Video processing failed", [
                'video_id' => $this->video->id,
                'attempt' => $this->attempts(),
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(?Throwable $exception): void
    {
        Log::error("Video processing permanently failed", [
            'video_id' => $this->video->id,
            'error' => $exception?->getMessage(),
        ]);

        $this->video->update([
            'status' => Video::STATUS_FAILED,
            'processing_error' => $exception?->getMessage() ?? 'Unknown error',
        ]);

        // Broadcast failure event to user
        event(new VideoProcessingFailed(
            $this->video,
            $exception?->getMessage() ?? 'Unknown error',
            'PROCESSING_FAILED'
        ));
    }

    /**
     * Determine whether the job should be retried.
     */
    public function retryUntil(): \DateTime
    {
        return now()->addHours(1);
    }

    /**
     * Get the tags that should be assigned to the job.
     */
    public function tags(): array
    {
        return [
            'video-processing',
            'video:' . $this->video->id,
            'uploader:' . $this->video->uploader_id,
        ];
    }
}
