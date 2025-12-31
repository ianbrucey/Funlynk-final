<?php

namespace App\Livewire\Video;

use App\Models\Video;
use App\Services\VideoStreamingService;
use App\Services\VideoAnalyticsService;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

/**
 * VideoPlayer Component
 * 
 * HLS video player with adaptive bitrate streaming.
 * Uses HLS.js for broad browser support.
 */
class VideoPlayer extends Component
{
    public Video $video;
    public ?array $streamUrls = null;
    public bool $autoplay = false;
    public bool $muted = false;
    public bool $loop = false;
    public bool $controls = true;
    public string $aspectRatio = '16/9';
    public string $poster = '';
    public bool $isReady = false;
    public bool $hasError = false;
    public ?string $errorMessage = null;

    // Analytics
    public bool $trackViews = true;
    public bool $viewRecorded = false;

    public function mount(
        Video $video,
        bool $autoplay = false,
        bool $muted = false,
        bool $loop = false,
        bool $controls = true,
        string $aspectRatio = '16/9',
        bool $trackViews = true
    ): void {
        $this->video = $video;
        $this->autoplay = $autoplay;
        $this->muted = $muted;
        $this->loop = $loop;
        $this->controls = $controls;
        $this->aspectRatio = $aspectRatio;
        $this->trackViews = $trackViews;

        // Check video status
        if ($video->status !== Video::STATUS_READY) {
            $this->hasError = true;
            $this->errorMessage = $this->getStatusMessage($video->status);
            return;
        }

        // Get streaming URLs
        $this->loadStreamUrls();

        // Set poster from thumbnail
        if ($video->thumbnail_path) {
            $this->poster = $video->thumbnail_url;
        }
    }

    /**
     * Load signed streaming URLs.
     */
    protected function loadStreamUrls(): void
    {
        try {
            $streamingService = app(VideoStreamingService::class);
            $result = $streamingService->getStreamingUrl($this->video);

            $this->streamUrls = [
                'master' => $result->hlsUrl,
                'qualities' => $result->availableQualities,
                'expires_at' => $result->expiresAt->toIso8601String(),
            ];

            $this->isReady = true;
        } catch (\Throwable $e) {
            $this->hasError = true;
            $this->errorMessage = 'Failed to load video. Please try again.';
        }
    }

    /**
     * Refresh streaming URLs (called when URLs expire).
     */
    public function refreshUrls(): array
    {
        $this->loadStreamUrls();

        if ($this->streamUrls) {
            return [
                'success' => true,
                'urls' => $this->streamUrls,
            ];
        }

        return [
            'success' => false,
            'error' => $this->errorMessage,
        ];
    }

    /**
     * Record a video view.
     */
    public function recordView(?float $watchDuration = null, ?string $quality = null): void
    {
        if (!$this->trackViews || $this->viewRecorded) {
            return;
        }

        try {
            $analyticsService = app(VideoAnalyticsService::class);
            $analyticsService->recordView(
                $this->video,
                Auth::user(),
                [
                    'watch_duration_seconds' => (int) ($watchDuration ?? 0),
                    'quality' => $quality,
                ]
            );

            $this->viewRecorded = true;
        } catch (\Throwable $e) {
            // Silently fail - analytics shouldn't break playback
        }
    }

    /**
     * Get user-friendly status message.
     */
    protected function getStatusMessage(string $status): string
    {
        return match ($status) {
            Video::STATUS_PENDING_UPLOAD => 'Video is still uploading...',
            Video::STATUS_UPLOADED => 'Video is being processed...',
            Video::STATUS_PROCESSING => 'Video is being processed...',
            Video::STATUS_FAILED => 'Video processing failed.',
            Video::STATUS_DELETED => 'This video has been deleted.',
            default => 'Video is not available.',
        };
    }

    /**
     * Format duration for display.
     */
    public function getFormattedDurationProperty(): string
    {
        $seconds = $this->video->duration_seconds ?? 0;
        $minutes = floor($seconds / 60);
        $remainingSeconds = $seconds % 60;

        return sprintf('%d:%02d', $minutes, $remainingSeconds);
    }

    public function render()
    {
        return view('livewire.video.video-player');
    }
}
