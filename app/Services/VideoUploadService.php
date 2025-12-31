<?php

namespace App\Services;

use App\DTOs\UploadUrlResult;
use App\Events\Video\VideoUploaded;
use App\Events\Video\VideoUploadRequested;
use App\Exceptions\Video\VideoUploadException;
use App\Models\User;
use App\Models\Video;
use App\Models\VideoUploadToken;
use App\Jobs\ProcessVideoJob;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class VideoUploadService
{
    /**
     * Generate a pre-signed upload URL for direct S3 upload.
     */
    public function generateUploadUrl(
        User $user,
        string $videoableType,
        string $videoableId,
        string $filename,
        int $fileSize,
        string $mimeType,
        ?int $durationSeconds = null
    ): UploadUrlResult {
        // Validate upload constraints
        $this->validateUploadRequest([
            'file_size' => $fileSize,
            'mime_type' => $mimeType,
            'duration_seconds' => $durationSeconds,
        ]);

        // Check rate limits
        $this->checkRateLimits($user);

        // Normalize videoable type to full class name
        $videoableClass = $this->normalizeVideoableType($videoableType);

        // Generate unique filename
        $videoId = Str::uuid()->toString();
        $extension = pathinfo($filename, PATHINFO_EXTENSION) ?: 'mp4';
        $storagePath = config('video.storage.originals_path') . "/{$videoId}.{$extension}";

        // Create video record (pending upload)
        $video = Video::create([
            'id' => $videoId,
            'videoable_type' => $videoableClass,
            'videoable_id' => $videoableId,
            'uploader_id' => $user->id,
            'storage_provider' => config('video.storage.disk'),
            'original_path' => $storagePath,
            'file_size_bytes' => $fileSize,
            'mime_type' => $mimeType,
            'duration_seconds' => $durationSeconds,
            'status' => Video::STATUS_PENDING_UPLOAD,
        ]);

        // Generate upload token
        $token = VideoUploadToken::generateToken();
        $expiresAt = now()->addSeconds(config('video.upload.upload_url_ttl'));

        VideoUploadToken::create([
            'user_id' => $user->id,
            'video_id' => $video->id,
            'token' => $token,
            'upload_path' => $storagePath,
            'max_file_size_bytes' => config('video.upload.max_file_size'),
            'allowed_mime_types' => config('video.upload.allowed_mimes'),
            'expires_at' => $expiresAt,
        ]);

        // Generate pre-signed PUT URL
        $disk = Storage::disk(config('video.storage.disk'));
        $uploadUrl = $this->generatePresignedUrl($disk, $storagePath, $expiresAt);

        // Dispatch upload requested event
        $uploadToken = VideoUploadToken::where('video_id', $video->id)->first();
        event(new VideoUploadRequested(
            $user,
            $uploadToken,
            $videoableClass,
            $videoableId,
            $filename,
            $fileSize
        ));

        return new UploadUrlResult(
            videoId: $video->id,
            uploadUrl: $uploadUrl,
            uploadToken: $token,
            uploadPath: $storagePath,
            expiresAt: $expiresAt,
        );
    }

    /**
     * Validate upload request constraints.
     */
    public function validateUploadRequest(array $data): void
    {
        $maxFileSize = config('video.upload.max_file_size');
        $maxDuration = config('video.upload.max_duration');
        $allowedMimes = config('video.upload.allowed_mimes');

        // Check file size
        if (isset($data['file_size']) && $data['file_size'] > $maxFileSize) {
            throw VideoUploadException::fileTooLarge($data['file_size'], $maxFileSize);
        }

        // Check duration
        if (isset($data['duration_seconds']) && $data['duration_seconds'] > $maxDuration) {
            throw VideoUploadException::durationTooLong($data['duration_seconds'], $maxDuration);
        }

        // Check MIME type
        if (isset($data['mime_type']) && !in_array($data['mime_type'], $allowedMimes)) {
            throw VideoUploadException::invalidMimeType($data['mime_type'], $allowedMimes);
        }
    }

    /**
     * Confirm upload completion and trigger processing.
     */
    public function confirmUpload(string $videoId, string $uploadToken): Video
    {
        $token = VideoUploadToken::where('video_id', $videoId)
            ->where('token', $uploadToken)
            ->first();

        if (!$token) {
            throw new \InvalidArgumentException('Invalid upload token');
        }

        if ($token->isExpired()) {
            throw new \InvalidArgumentException('Upload token has expired');
        }

        if ($token->isUsed()) {
            throw new \InvalidArgumentException('Upload token has already been used');
        }

        $video = Video::findOrFail($videoId);

        // Verify file exists in storage
        $disk = Storage::disk(config('video.storage.disk'));
        if (!$disk->exists($video->original_path)) {
            throw new \RuntimeException('Video file not found in storage');
        }

        // Mark token as used
        $token->markAsUsed();

        // Update video status
        $video->update([
            'status' => Video::STATUS_UPLOADED,
        ]);

        // Dispatch uploaded event (broadcasts to user)
        event(new VideoUploaded($video));

        // Dispatch processing job
        ProcessVideoJob::dispatch($video)
            ->onQueue(config('video.queues.transcode'));

        return $video->fresh();
    }

    /**
     * Handle S3 webhook notification for upload completion.
     */
    public function handleS3Webhook(array $payload): void
    {
        // Parse S3 event notification
        foreach ($payload['Records'] ?? [] as $record) {
            $eventName = $record['eventName'] ?? '';
            
            if (!str_starts_with($eventName, 's3:ObjectCreated:')) {
                continue;
            }

            $bucket = $record['s3']['bucket']['name'] ?? '';
            $key = urldecode($record['s3']['object']['key'] ?? '');

            // Find video by original path
            $video = Video::where('original_path', $key)
                ->where('status', Video::STATUS_PENDING_UPLOAD)
                ->first();

            if (!$video) {
                continue;
            }

            // Update status and dispatch processing
            $video->update(['status' => Video::STATUS_UPLOADED]);
            
            ProcessVideoJob::dispatch($video)
                ->onQueue(config('video.queues.transcode'));
        }
    }

    /**
     * Cancel a pending upload.
     */
    public function cancelUpload(string $videoId): void
    {
        $video = Video::findOrFail($videoId);

        if (!in_array($video->status, [Video::STATUS_PENDING_UPLOAD, Video::STATUS_UPLOADING])) {
            throw new \InvalidArgumentException('Cannot cancel upload in current status');
        }

        // Delete file if exists
        $disk = Storage::disk(config('video.storage.disk'));
        if ($disk->exists($video->original_path)) {
            $disk->delete($video->original_path);
        }

        // Delete video record
        $video->forceDelete();
    }

    /**
     * Check rate limits for user.
     */
    protected function checkRateLimits(User $user): void
    {
        $limit = config('video.rate_limits.uploads_per_hour');
        
        $recentUploads = Video::where('uploader_id', $user->id)
            ->where('created_at', '>=', now()->subHour())
            ->count();

        if ($recentUploads >= $limit) {
            throw VideoUploadException::rateLimitExceeded($limit);
        }
    }

    /**
     * Normalize videoable type string to full class name.
     */
    protected function normalizeVideoableType(string $type): string
    {
        return match (strtolower($type)) {
            'post' => \App\Models\Post::class,
            'activity' => \App\Models\Activity::class,
            'user' => \App\Models\User::class,
            'group' => \App\Models\Group::class,
            default => $type,
        };
    }

    /**
     * Generate a pre-signed PUT URL for S3.
     */
    protected function generatePresignedUrl($disk, string $path, \DateTimeInterface $expiresAt): string
    {
        $client = $disk->getClient();
        $bucket = config('filesystems.disks.' . config('video.storage.disk') . '.bucket');

        $command = $client->getCommand('PutObject', [
            'Bucket' => $bucket,
            'Key' => $path,
            'ACL' => 'private',
        ]);

        $request = $client->createPresignedRequest($command, $expiresAt);

        return (string) $request->getUri();
    }
}
