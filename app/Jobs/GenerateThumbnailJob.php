<?php

namespace App\Jobs;

use App\Models\Video;
use App\Services\VideoProcessingService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Throwable;

class GenerateThumbnailJob implements ShouldQueue
{
    use InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $timeout = 120;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public Video $video,
        public int $timestampSeconds = 1
    ) {}

    /**
     * Execute the job.
     */
    public function handle(VideoProcessingService $processor): void
    {
        Log::info("Generating thumbnail", [
            'video_id' => $this->video->id,
            'timestamp' => $this->timestampSeconds,
        ]);

        // Download original to temp
        $tempDir = storage_path('app/videos/temp/' . $this->video->id);
        if (!is_dir($tempDir)) {
            mkdir($tempDir, 0755, true);
        }

        $disk = Storage::disk(config('video.storage.disk'));
        $extension = pathinfo($this->video->original_path, PATHINFO_EXTENSION);
        $localPath = "{$tempDir}/original.{$extension}";

        $stream = $disk->readStream($this->video->original_path);
        file_put_contents($localPath, $stream);
        fclose($stream);

        try {
            $thumbnailPath = $processor->generateThumbnail(
                $this->video,
                $localPath,
                $this->timestampSeconds
            );

            $this->video->update([
                'thumbnail_path' => $thumbnailPath,
            ]);

            Log::info("Thumbnail generated", [
                'video_id' => $this->video->id,
                'path' => $thumbnailPath,
            ]);

        } finally {
            // Cleanup
            if (file_exists($localPath)) {
                unlink($localPath);
            }
            if (is_dir($tempDir)) {
                rmdir($tempDir);
            }
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(?Throwable $exception): void
    {
        Log::error("Thumbnail generation failed", [
            'video_id' => $this->video->id,
            'error' => $exception?->getMessage(),
        ]);
    }
}
