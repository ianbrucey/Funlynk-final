<?php

namespace App\Livewire\Video;

use App\Services\VideoUploadService;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\On;
use Livewire\Component;

/**
 * VideoUploader Component
 * 
 * Handles direct-to-S3 video uploads with progress tracking.
 * Uses pre-signed URLs to bypass Laravel for large file transfers.
 */
class VideoUploader extends Component
{
    // Configuration
    public string $videoableType = 'post';
    public ?string $videoableId = null;
    public int $maxFileSize;
    public int $maxDuration;
    public array $allowedMimes;

    // Upload state
    public bool $isUploading = false;
    public int $uploadProgress = 0;
    public ?string $uploadError = null;
    public ?string $currentVideoId = null;

    // Processing state
    public bool $isProcessing = false;
    public int $processingProgress = 0;
    public ?string $processingStage = null;

    // Result
    public ?array $uploadedVideo = null;

    public function mount(
        string $videoableType = 'post',
        ?string $videoableId = null
    ): void {
        $this->videoableType = $videoableType;
        $this->videoableId = $videoableId ?? 'pending';

        // Load config
        $this->maxFileSize = config('video.upload.max_file_size', 104857600);
        $this->maxDuration = config('video.upload.max_duration', 60);
        $this->allowedMimes = config('video.upload.allowed_mimes', [
            'video/mp4',
            'video/webm',
            'video/quicktime',
            'video/x-msvideo',
        ]);
    }

    /**
     * Request a pre-signed upload URL from the server.
     */
    public function requestUploadUrl(
        string $filename,
        int $fileSize,
        string $mimeType,
        ?int $durationSeconds = null
    ): array {
        $this->resetState();
        $this->uploadError = null;

        // Validate on client first
        if ($fileSize > $this->maxFileSize) {
            $this->uploadError = 'File is too large. Maximum size is ' . $this->formatBytes($this->maxFileSize);
            return ['error' => $this->uploadError];
        }

        if (!in_array($mimeType, $this->allowedMimes)) {
            $this->uploadError = 'Invalid file type. Allowed types: MP4, WebM, MOV, AVI';
            return ['error' => $this->uploadError];
        }

        try {
            $uploadService = app(VideoUploadService::class);
            $result = $uploadService->generateUploadUrl(
                Auth::user(),
                $this->videoableType,
                $this->videoableId,
                $filename,
                $fileSize,
                $mimeType,
                $durationSeconds
            );

            $this->currentVideoId = $result->videoId;
            $this->isUploading = true;

            return [
                'videoId' => $result->videoId,
                'uploadUrl' => $result->uploadUrl,
                'uploadToken' => $result->uploadToken,
                'expiresAt' => $result->expiresAt->toIso8601String(),
            ];
        } catch (\Throwable $e) {
            $this->uploadError = $e->getMessage();
            return ['error' => $this->uploadError];
        }
    }

    /**
     * Update upload progress (called from JavaScript).
     */
    public function updateUploadProgress(int $progress): void
    {
        $this->uploadProgress = min(100, max(0, $progress));
    }

    /**
     * Confirm upload completion.
     */
    public function confirmUpload(string $videoId, string $uploadToken): array
    {
        try {
            $uploadService = app(VideoUploadService::class);
            $video = $uploadService->confirmUpload($videoId, $uploadToken);

            $this->isUploading = false;
            $this->uploadProgress = 100;
            $this->isProcessing = true;
            $this->processingProgress = 0;
            $this->processingStage = 'Queued';

            return [
                'success' => true,
                'videoId' => $video->id,
                'status' => $video->status,
            ];
        } catch (\Throwable $e) {
            $this->uploadError = $e->getMessage();
            $this->isUploading = false;
            return ['error' => $this->uploadError];
        }
    }

    /**
     * Handle upload failure.
     */
    public function uploadFailed(string $error): void
    {
        $this->uploadError = $error;
        $this->isUploading = false;
        $this->uploadProgress = 0;
    }

    /**
     * Listen for processing progress updates (via Echo/Reverb).
     */
    #[On('echo-private:user.{userId},video.processing.progress')]
    public function onProcessingProgress(array $data): void
    {
        if ($data['video_id'] === $this->currentVideoId) {
            $this->processingProgress = $data['progress'] ?? 0;
            $this->processingStage = $data['stage'] ?? 'Processing';
        }
    }

    /**
     * Listen for processing completion.
     */
    #[On('echo-private:user.{userId},video.processing.completed')]
    public function onProcessingCompleted(array $data): void
    {
        if ($data['video_id'] === $this->currentVideoId) {
            $this->isProcessing = false;
            $this->processingProgress = 100;
            $this->uploadedVideo = $data;

            $this->dispatch('video-ready', videoId: $data['video_id']);
        }
    }

    /**
     * Listen for processing failure.
     */
    #[On('echo-private:user.{userId},video.processing.failed')]
    public function onProcessingFailed(array $data): void
    {
        if ($data['video_id'] === $this->currentVideoId) {
            $this->isProcessing = false;
            $this->uploadError = $data['message'] ?? 'Processing failed';

            $this->dispatch('video-failed', 
                videoId: $data['video_id'],
                error: $this->uploadError
            );
        }
    }

    /**
     * Reset component state.
     */
    public function resetState(): void
    {
        $this->isUploading = false;
        $this->uploadProgress = 0;
        $this->uploadError = null;
        $this->isProcessing = false;
        $this->processingProgress = 0;
        $this->processingStage = null;
        $this->uploadedVideo = null;
        $this->currentVideoId = null;
    }

    /**
     * Format bytes to human readable.
     */
    protected function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $i = 0;
        while ($bytes >= 1024 && $i < count($units) - 1) {
            $bytes /= 1024;
            $i++;
        }
        return round($bytes, 2) . ' ' . $units[$i];
    }

    /**
     * Get the user ID for Echo channels.
     */
    public function getUserIdProperty(): ?int
    {
        return Auth::id();
    }

    public function render()
    {
        return view('livewire.video.video-uploader');
    }
}
