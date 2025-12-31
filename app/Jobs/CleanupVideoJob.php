<?php

namespace App\Jobs;

use App\Models\Video;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Throwable;

class CleanupVideoJob implements ShouldQueue
{
    use InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $timeout = 300;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public Video $video
    ) {}

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        Log::info("Cleaning up video files", [
            'video_id' => $this->video->id,
        ]);

        $disk = Storage::disk(config('video.storage.disk'));

        // Delete original file
        if ($this->video->original_path && $disk->exists($this->video->original_path)) {
            $disk->delete($this->video->original_path);
            Log::debug("Deleted original", ['path' => $this->video->original_path]);
        }

        // Delete thumbnail
        if ($this->video->thumbnail_path && $disk->exists($this->video->thumbnail_path)) {
            $disk->delete($this->video->thumbnail_path);
            Log::debug("Deleted thumbnail", ['path' => $this->video->thumbnail_path]);
        }

        // Delete HLS directory
        if ($this->video->hls_path) {
            $hlsDir = dirname($this->video->hls_path);
            $this->deleteDirectory($disk, $hlsDir);
            Log::debug("Deleted HLS directory", ['path' => $hlsDir]);
        }

        // Delete related records
        $this->video->uploadTokens()->delete();
        $this->video->processingJobs()->delete();
        $this->video->views()->delete();

        // Hard delete the video record
        $this->video->forceDelete();

        Log::info("Video cleanup completed", [
            'video_id' => $this->video->id,
        ]);
    }

    /**
     * Delete a directory and all its contents from S3.
     */
    protected function deleteDirectory($disk, string $directory): void
    {
        $files = $disk->files($directory);
        $subdirs = $disk->directories($directory);

        // Delete files
        foreach ($files as $file) {
            $disk->delete($file);
        }

        // Recursively delete subdirectories
        foreach ($subdirs as $subdir) {
            $this->deleteDirectory($disk, $subdir);
        }

        // Note: S3 doesn't have "directories" per se, so no need to delete the directory itself
    }

    /**
     * Handle a job failure.
     */
    public function failed(?Throwable $exception): void
    {
        Log::error("Video cleanup failed", [
            'video_id' => $this->video->id,
            'error' => $exception?->getMessage(),
        ]);
    }
}
