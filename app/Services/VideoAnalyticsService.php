<?php

namespace App\Services;

use App\Models\User;
use App\Models\Video;
use App\Models\VideoView;
use App\Jobs\RecordVideoViewJob;
use Illuminate\Support\Facades\DB;

class VideoAnalyticsService
{
    /**
     * Record a video view (dispatches async job).
     */
    public function recordView(
        Video $video,
        ?User $user,
        array $metadata = []
    ): void {
        RecordVideoViewJob::dispatch(
            $video->id,
            $user?->id,
            $metadata
        )->onQueue(config('video.queues.analytics', 'low'));
    }

    /**
     * Record a video view synchronously (used by job).
     */
    public function recordViewSync(
        string $videoId,
        ?string $userId,
        array $metadata = []
    ): void {
        VideoView::create([
            'video_id' => $videoId,
            'user_id' => $userId,
            'viewed_at' => now(),
            'watch_duration_seconds' => $metadata['watch_duration_seconds'] ?? null,
            'completed' => $metadata['completed'] ?? false,
            'device_type' => $metadata['device_type'] ?? null,
            'referrer' => $metadata['referrer'] ?? null,
            'ip_hash' => isset($metadata['ip']) ? VideoView::hashIp($metadata['ip']) : null,
        ]);

        // Increment view count on video (consider moving to scheduled job for high traffic)
        Video::where('id', $videoId)->increment('view_count');
    }

    /**
     * Get video analytics for a period.
     */
    public function getAnalytics(Video $video, string $period = '7d'): array
    {
        $startDate = $this->parsePeriod($period);

        // Total views
        $totalViews = VideoView::where('video_id', $video->id)
            ->where('viewed_at', '>=', $startDate)
            ->count();

        // Unique viewers
        $uniqueViewers = VideoView::where('video_id', $video->id)
            ->where('viewed_at', '>=', $startDate)
            ->whereNotNull('user_id')
            ->distinct('user_id')
            ->count('user_id');

        // Average watch duration
        $avgWatchDuration = VideoView::where('video_id', $video->id)
            ->where('viewed_at', '>=', $startDate)
            ->whereNotNull('watch_duration_seconds')
            ->avg('watch_duration_seconds');

        // Completion rate
        $completionRate = $totalViews > 0
            ? VideoView::where('video_id', $video->id)
                ->where('viewed_at', '>=', $startDate)
                ->where('completed', true)
                ->count() / $totalViews * 100
            : 0;

        // Views by device
        $viewsByDevice = VideoView::where('video_id', $video->id)
            ->where('viewed_at', '>=', $startDate)
            ->whereNotNull('device_type')
            ->groupBy('device_type')
            ->select('device_type', DB::raw('count(*) as count'))
            ->pluck('count', 'device_type')
            ->toArray();

        // Views by referrer
        $viewsByReferrer = VideoView::where('video_id', $video->id)
            ->where('viewed_at', '>=', $startDate)
            ->whereNotNull('referrer')
            ->groupBy('referrer')
            ->select('referrer', DB::raw('count(*) as count'))
            ->pluck('count', 'referrer')
            ->toArray();

        // Daily views
        $dailyViews = VideoView::where('video_id', $video->id)
            ->where('viewed_at', '>=', $startDate)
            ->groupBy(DB::raw('DATE(viewed_at)'))
            ->select(DB::raw('DATE(viewed_at) as date'), DB::raw('count(*) as count'))
            ->orderBy('date')
            ->pluck('count', 'date')
            ->toArray();

        return [
            'period' => $period,
            'start_date' => $startDate->toIso8601String(),
            'total_views' => $totalViews,
            'unique_viewers' => $uniqueViewers,
            'avg_watch_duration_seconds' => round($avgWatchDuration ?? 0, 2),
            'completion_rate_percent' => round($completionRate, 2),
            'views_by_device' => $viewsByDevice,
            'views_by_referrer' => $viewsByReferrer,
            'daily_views' => $dailyViews,
        ];
    }

    /**
     * Flush view counts from queue to database (for batch processing).
     */
    public function flushViewCounts(): void
    {
        // This would be used with Redis-based counting for high traffic
        // For now, views are counted directly in recordViewSync
    }

    /**
     * Get top videos by views.
     */
    public function getTopVideos(int $limit = 10, string $period = '7d'): array
    {
        $startDate = $this->parsePeriod($period);

        return Video::query()
            ->where('status', Video::STATUS_READY)
            ->where('visibility', Video::VISIBILITY_PUBLIC)
            ->whereHas('views', function ($query) use ($startDate) {
                $query->where('viewed_at', '>=', $startDate);
            })
            ->withCount(['views' => function ($query) use ($startDate) {
                $query->where('viewed_at', '>=', $startDate);
            }])
            ->orderByDesc('views_count')
            ->limit($limit)
            ->get()
            ->map(function ($video) {
                return [
                    'id' => $video->id,
                    'views_count' => $video->views_count,
                    'uploader' => [
                        'id' => $video->uploader_id,
                        'name' => $video->uploader?->name,
                    ],
                ];
            })
            ->toArray();
    }

    /**
     * Parse period string to Carbon date.
     */
    protected function parsePeriod(string $period): \Carbon\Carbon
    {
        return match ($period) {
            '24h' => now()->subDay(),
            '7d' => now()->subWeek(),
            '30d' => now()->subMonth(),
            '90d' => now()->subMonths(3),
            '1y' => now()->subYear(),
            default => now()->subWeek(),
        };
    }
}
