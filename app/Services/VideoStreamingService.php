<?php

namespace App\Services;

use App\DTOs\StreamingUrlResult;
use App\Exceptions\Video\VideoNotReadyException;
use App\Models\Video;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;

class VideoStreamingService
{
    /**
     * Generate signed streaming URLs for a video.
     */
    public function getStreamingUrl(Video $video): StreamingUrlResult
    {
        if (!$video->isReady()) {
            throw VideoNotReadyException::stillProcessing($video->status);
        }

        $disk = Storage::disk(config('video.storage.disk'));
        $ttl = config('video.streaming.url_ttl', 3600);
        $expiresAt = now()->addSeconds($ttl);

        // Generate signed HLS URL
        $hlsUrl = $this->generateSignedUrl($disk, $video->hls_path, $expiresAt);

        // Generate signed thumbnail URL
        $thumbnailUrl = $video->thumbnail_path
            ? $this->generateSignedUrl($disk, $video->thumbnail_path, $expiresAt)
            : null;

        return new StreamingUrlResult(
            hlsUrl: $hlsUrl,
            thumbnailUrl: $thumbnailUrl,
            expiresAt: $expiresAt,
            availableQualities: $video->available_qualities ?? [],
        );
    }

    /**
     * Generate signed thumbnail URL only.
     */
    public function getThumbnailUrl(Video $video): ?string
    {
        if (!$video->thumbnail_path) {
            return null;
        }

        $disk = Storage::disk(config('video.storage.disk'));
        $ttl = config('video.streaming.url_ttl', 3600);
        $expiresAt = now()->addSeconds($ttl);

        return $this->generateSignedUrl($disk, $video->thumbnail_path, $expiresAt);
    }

    /**
     * Check if video is ready for streaming.
     */
    public function isReady(Video $video): bool
    {
        return $video->isReady();
    }

    /**
     * Prepare videos for feed display with pre-computed URLs.
     */
    public function prepareForFeed(Collection $videos): Collection
    {
        $disk = Storage::disk(config('video.storage.disk'));
        $ttl = config('video.streaming.url_ttl', 3600);
        $expiresAt = now()->addSeconds($ttl);

        return $videos->map(function (Video $video) use ($disk, $expiresAt) {
            if (!$video->isReady()) {
                return [
                    'id' => $video->id,
                    'status' => $video->status,
                    'is_ready' => false,
                    'thumbnail_url' => null,
                    'hls_url' => null,
                ];
            }

            return [
                'id' => $video->id,
                'status' => $video->status,
                'is_ready' => true,
                'duration_seconds' => $video->duration_seconds,
                'aspect_ratio' => $video->aspect_ratio,
                'thumbnail_url' => $video->thumbnail_path
                    ? $this->generateSignedUrl($disk, $video->thumbnail_path, $expiresAt)
                    : null,
                'hls_url' => $this->generateSignedUrl($disk, $video->hls_path, $expiresAt),
                'available_qualities' => $video->available_qualities ?? [],
                'view_count' => $video->view_count,
            ];
        });
    }

    /**
     * Get streaming info for a single video (for API response).
     */
    public function getVideoStreamInfo(Video $video): array
    {
        if (!$video->isReady()) {
            return [
                'id' => $video->id,
                'status' => $video->status,
                'is_ready' => false,
                'processing_error' => $video->processing_error,
            ];
        }

        $streamingResult = $this->getStreamingUrl($video);

        return [
            'id' => $video->id,
            'status' => $video->status,
            'is_ready' => true,
            'duration_seconds' => $video->duration_seconds,
            'width' => $video->width,
            'height' => $video->height,
            'aspect_ratio' => $video->aspect_ratio,
            'thumbnail_url' => $streamingResult->thumbnailUrl,
            'hls_url' => $streamingResult->hlsUrl,
            'available_qualities' => $streamingResult->availableQualities,
            'expires_at' => $streamingResult->expiresAt->toIso8601String(),
            'view_count' => $video->view_count,
        ];
    }

    /**
     * Generate a signed URL for S3.
     */
    protected function generateSignedUrl($disk, string $path, \DateTimeInterface $expiresAt): string
    {
        // Check if CDN domain is configured
        $cdnDomain = config('video.streaming.cdn_domain');

        if ($cdnDomain) {
            // Use CDN URL (assumes CDN is properly configured with S3 origin)
            return "https://{$cdnDomain}/{$path}";
        }

        // Generate pre-signed S3 URL
        $client = $disk->getClient();
        $bucket = config('filesystems.disks.' . config('video.storage.disk') . '.bucket');

        $command = $client->getCommand('GetObject', [
            'Bucket' => $bucket,
            'Key' => $path,
        ]);

        $request = $client->createPresignedRequest($command, $expiresAt);

        return (string) $request->getUri();
    }
}
