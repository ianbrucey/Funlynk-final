<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Video\ConfirmUploadRequest;
use App\Http\Requests\Video\RequestUploadUrlRequest;
use App\Http\Requests\Video\RecordViewRequest;
use App\Models\Video;
use App\Services\VideoUploadService;
use App\Services\VideoStreamingService;
use App\Services\VideoAnalyticsService;
use App\Jobs\CleanupVideoJob;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class VideoController extends Controller
{
    public function __construct(
        protected VideoUploadService $uploadService,
        protected VideoStreamingService $streamingService,
        protected VideoAnalyticsService $analyticsService,
    ) {}

    /**
     * Request a pre-signed upload URL.
     * 
     * POST /api/v1/videos/upload-url
     */
    public function requestUploadUrl(RequestUploadUrlRequest $request): JsonResponse
    {
        $result = $this->uploadService->generateUploadUrl(
            user: $request->user(),
            videoableType: $request->validated('videoable_type'),
            videoableId: $request->validated('videoable_id'),
            filename: $request->validated('filename'),
            fileSize: $request->validated('file_size'),
            mimeType: $request->validated('mime_type'),
            durationSeconds: $request->validated('duration_seconds'),
        );

        return response()->json($result->toArray(), 201);
    }

    /**
     * Confirm upload completion and trigger processing.
     * 
     * POST /api/v1/videos/{video}/confirm-upload
     */
    public function confirmUpload(ConfirmUploadRequest $request, Video $video): JsonResponse
    {
        $this->authorize('update', $video);

        $video = $this->uploadService->confirmUpload(
            $video->id,
            $request->validated('upload_token')
        );

        return response()->json([
            'video_id' => $video->id,
            'status' => $video->status,
            'estimated_processing_time_seconds' => $this->estimateProcessingTime($video),
        ]);
    }

    /**
     * Get video details.
     * 
     * GET /api/v1/videos/{video}
     */
    public function show(Video $video): JsonResponse
    {
        $this->authorize('view', $video);

        return response()->json([
            'id' => $video->id,
            'videoable_type' => class_basename($video->videoable_type),
            'videoable_id' => $video->videoable_id,
            'uploader' => [
                'id' => $video->uploader_id,
                'name' => $video->uploader?->name,
                'avatar_url' => $video->uploader?->profile_image_url,
            ],
            'status' => $video->status,
            'duration_seconds' => $video->duration_seconds,
            'width' => $video->width,
            'height' => $video->height,
            'aspect_ratio' => $video->aspect_ratio,
            'thumbnail_url' => $video->isReady() ? $this->streamingService->getThumbnailUrl($video) : null,
            'available_qualities' => $video->available_qualities ?? [],
            'view_count' => $video->view_count,
            'visibility' => $video->visibility,
            'created_at' => $video->created_at->toIso8601String(),
            'processing_error' => $video->hasFailed() ? $video->processing_error : null,
        ]);
    }

    /**
     * Get streaming URLs for a video.
     * 
     * GET /api/v1/videos/{video}/stream
     */
    public function stream(Video $video): JsonResponse
    {
        $this->authorize('view', $video);

        if (!$video->isReady()) {
            return response()->json([
                'error' => 'Video not ready',
                'status' => $video->status,
                'message' => $video->isProcessing() 
                    ? 'Video is still processing. Please try again shortly.'
                    : 'Video processing failed.',
            ], 409);
        }

        $result = $this->streamingService->getStreamingUrl($video);

        return response()->json($result->toArray());
    }

    /**
     * Delete a video.
     * 
     * DELETE /api/v1/videos/{video}
     */
    public function destroy(Video $video): JsonResponse
    {
        $this->authorize('delete', $video);

        // Soft delete immediately
        $video->update(['status' => Video::STATUS_DELETED]);
        $video->delete();

        // Queue cleanup job to delete files
        CleanupVideoJob::dispatch($video)
            ->onQueue(config('video.queues.cleanup'));

        return response()->json(null, 204);
    }

    /**
     * Record a video view.
     * 
     * POST /api/v1/videos/{video}/view
     */
    public function recordView(RecordViewRequest $request, Video $video): JsonResponse
    {
        $this->analyticsService->recordView(
            video: $video,
            user: $request->user(),
            metadata: [
                'watch_duration_seconds' => $request->validated('watch_duration_seconds'),
                'completed' => $request->validated('completed', false),
                'referrer' => $request->validated('referrer'),
                'device_type' => $this->detectDeviceType($request),
                'ip' => $request->ip(),
            ]
        );

        return response()->json(['recorded' => true], 202);
    }

    /**
     * Handle S3 upload webhook.
     * 
     * POST /webhooks/s3/video-uploaded
     */
    public function handleS3Webhook(Request $request): JsonResponse
    {
        // Verify webhook signature if configured
        // $this->verifyS3Signature($request);

        $this->uploadService->handleS3Webhook($request->all());

        return response()->json(['processed' => true]);
    }

    /**
     * Get video analytics.
     * 
     * GET /api/v1/videos/{video}/analytics
     */
    public function analytics(Video $video, Request $request): JsonResponse
    {
        $this->authorize('update', $video); // Only owner can view analytics

        $period = $request->get('period', '7d');
        $analytics = $this->analyticsService->getAnalytics($video, $period);

        return response()->json($analytics);
    }

    /**
     * Get video processing status (for polling).
     * 
     * GET /api/v1/videos/{video}/status
     */
    public function status(Video $video): JsonResponse
    {
        $this->authorize('view', $video);

        return response()->json([
            'id' => $video->id,
            'status' => $video->status,
            'is_ready' => $video->isReady(),
            'is_processing' => $video->isProcessing(),
            'has_failed' => $video->hasFailed(),
            'processing_attempts' => $video->processing_attempts,
            'processing_error' => $video->hasFailed() ? $video->processing_error : null,
            'thumbnail_url' => $video->thumbnail_path 
                ? $this->streamingService->getThumbnailUrl($video) 
                : null,
        ]);
    }

    /**
     * Estimate processing time based on file size.
     */
    protected function estimateProcessingTime(Video $video): int
    {
        // Rough estimate: ~1 second per MB of video
        $fileSizeMb = ($video->file_size_bytes ?? 0) / 1024 / 1024;
        return max(10, (int) ceil($fileSizeMb * 1.5));
    }

    /**
     * Detect device type from request.
     */
    protected function detectDeviceType(Request $request): string
    {
        $userAgent = strtolower($request->userAgent() ?? '');

        if (str_contains($userAgent, 'mobile') || str_contains($userAgent, 'android') || str_contains($userAgent, 'iphone')) {
            return 'mobile';
        }

        if (str_contains($userAgent, 'tablet') || str_contains($userAgent, 'ipad')) {
            return 'tablet';
        }

        return 'desktop';
    }
}
