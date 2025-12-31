<?php

namespace App\Services;

use App\DTOs\VideoMetadata;
use App\Events\Video\VideoProcessingCompleted;
use App\Events\Video\VideoProcessingFailed;
use App\Events\Video\VideoProcessingProgress;
use App\Events\Video\VideoProcessingStarted;
use App\Exceptions\Video\VideoProcessingException;
use App\Models\Video;
use App\Models\VideoProcessingJob;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Str;

class VideoProcessingService
{
    protected string $ffmpegPath;
    protected string $ffprobePath;
    protected string $tempDir;

    public function __construct()
    {
        $this->ffmpegPath = config('video.processing.ffmpeg_path') ?: 'ffmpeg';
        $this->ffprobePath = config('video.processing.ffprobe_path') ?: 'ffprobe';
        $this->tempDir = storage_path('app/' . config('video.storage.temp_path'));

        if (!is_dir($this->tempDir)) {
            mkdir($this->tempDir, 0755, true);
        }
    }

    /**
     * Start the full processing pipeline for a video.
     */
    public function startProcessing(Video $video): void
    {
        $video->update([
            'status' => Video::STATUS_PROCESSING,
            'processing_started_at' => now(),
            'processing_attempts' => $video->processing_attempts + 1,
        ]);

        // Broadcast processing started event
        event(new VideoProcessingStarted($video, Str::uuid()->toString()));

        try {
            // 1. Download original from S3 to temp
            event(new VideoProcessingProgress($video, 10, 'Downloading'));
            $localPath = $this->downloadToTemp($video);

            // 2. Analyze video metadata
            event(new VideoProcessingProgress($video, 20, 'Analyzing'));
            $metadata = $this->analyzeVideo($localPath);
            $this->updateVideoMetadata($video, $metadata);

            // 3. Validate duration after analysis
            if ($metadata->durationSeconds > config('video.upload.max_duration')) {
                throw VideoProcessingException::analysisFailedError(
                    "Video duration ({$metadata->durationSeconds}s) exceeds maximum allowed"
                );
            }

            // 4. Generate thumbnail
            event(new VideoProcessingProgress($video, 30, 'Generating Thumbnail'));
            $thumbnailPath = $this->generateThumbnail($video, $localPath);

            // 5. Transcode to HLS
            event(new VideoProcessingProgress($video, 40, 'Transcoding'));
            $qualities = $this->transcodeToHls($video, $localPath, $metadata);

            // 6. Generate master playlist
            event(new VideoProcessingProgress($video, 90, 'Finalizing'));
            $hlsPath = $this->generateMasterPlaylist($video, $qualities, $metadata);

            // 7. Update video as ready
            $this->markProcessingComplete($video, $qualities, $hlsPath, $thumbnailPath);

            // 8. Cleanup temp files
            $this->cleanupTempFiles($video->id);

            // Broadcast processing completed event
            event(new VideoProcessingCompleted($video->fresh(), $qualities));

        } catch (\Throwable $e) {
            $this->markProcessingFailed($video, $e->getMessage());
            $this->cleanupTempFiles($video->id);
            
            // Broadcast processing failed event
            event(new VideoProcessingFailed($video, $e->getMessage(), 'PROCESSING_ERROR'));
            
            throw $e;
        }
    }

    /**
     * Analyze video metadata with ffprobe.
     */
    public function analyzeVideo(string $inputPath): VideoMetadata
    {
        $this->ensureFfprobeExists();

        $result = Process::run([
            $this->ffprobePath,
            '-v', 'quiet',
            '-print_format', 'json',
            '-show_format',
            '-show_streams',
            $inputPath,
        ]);

        if (!$result->successful()) {
            throw VideoProcessingException::analysisFailedError($result->errorOutput());
        }

        $data = json_decode($result->output(), true);

        if (!$data) {
            throw VideoProcessingException::analysisFailedError('Failed to parse ffprobe output');
        }

        return VideoMetadata::fromFfprobe($data);
    }

    /**
     * Generate thumbnail at specified timestamp.
     */
    public function generateThumbnail(
        Video $video,
        string $inputPath,
        int $timestampSeconds = null
    ): string {
        $this->ensureFfmpegExists();

        $timestampSeconds ??= config('video.processing.thumbnail_timestamp', 1);
        $thumbnailFilename = "{$video->id}.jpg";
        $localThumbnailPath = "{$this->tempDir}/{$video->id}/{$thumbnailFilename}";
        $storagePath = config('video.storage.thumbnails_path') . "/{$thumbnailFilename}";

        // Ensure directory exists
        $dir = dirname($localThumbnailPath);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        // Create processing job record
        $job = $this->createProcessingJob($video, VideoProcessingJob::TYPE_THUMBNAIL, $inputPath, $storagePath);

        $result = Process::run([
            $this->ffmpegPath,
            '-i', $inputPath,
            '-ss', (string) $timestampSeconds,
            '-vframes', '1',
            '-q:v', '2',
            '-y',
            $localThumbnailPath,
        ]);

        if (!$result->successful() || !file_exists($localThumbnailPath)) {
            $job->markAsFailed($result->errorOutput());
            throw VideoProcessingException::thumbnailGenerationFailed($result->errorOutput());
        }

        // Upload to S3
        $disk = Storage::disk(config('video.storage.disk'));
        $disk->put($storagePath, file_get_contents($localThumbnailPath));

        $job->markAsCompleted();

        return $storagePath;
    }

    /**
     * Transcode to HLS with multiple qualities.
     */
    public function transcodeToHls(
        Video $video,
        string $inputPath,
        VideoMetadata $metadata,
        array $qualities = null
    ): array {
        $this->ensureFfmpegExists();

        $qualityConfigs = config('video.processing.qualities');
        $qualities ??= array_keys($qualityConfigs);
        $segmentDuration = config('video.processing.hls_segment_duration', 6);
        $preset = config('video.processing.ffmpeg_preset', 'fast');

        $availableQualities = [];
        $localOutputDir = "{$this->tempDir}/{$video->id}/hls";
        $storageBaseDir = config('video.storage.hls_path') . "/{$video->id}";

        // Ensure directory exists
        if (!is_dir($localOutputDir)) {
            mkdir($localOutputDir, 0755, true);
        }

        // Filter qualities based on input resolution
        $inputHeight = $metadata->height;
        $filteredQualities = array_filter($qualities, function ($quality) use ($qualityConfigs, $inputHeight) {
            $height = $qualityConfigs[$quality]['height'] ?? 0;
            return $height <= $inputHeight;
        });

        // Always include at least the lowest quality
        if (empty($filteredQualities) && !empty($qualities)) {
            $filteredQualities = [end($qualities)];
        }

        foreach ($filteredQualities as $quality) {
            $config = $qualityConfigs[$quality] ?? null;
            if (!$config) {
                continue;
            }

            $qualityDir = "{$localOutputDir}/{$quality}";
            if (!is_dir($qualityDir)) {
                mkdir($qualityDir, 0755, true);
            }

            $playlistPath = "{$qualityDir}/playlist.m3u8";
            $segmentPattern = "{$qualityDir}/segment_%03d.ts";

            // Create processing job record
            $job = $this->createProcessingJob(
                $video,
                VideoProcessingJob::TYPE_TRANSCODE,
                $inputPath,
                "{$storageBaseDir}/{$quality}/playlist.m3u8"
            );
            $job->update(['ffmpeg_command' => $this->buildFfmpegCommand($inputPath, $config, $segmentPattern, $playlistPath, $preset, $segmentDuration)]);

            $result = Process::timeout(config('video.processing.timeout', 600))->run([
                $this->ffmpegPath,
                '-i', $inputPath,
                '-vf', "scale=-2:{$config['height']}",
                '-c:v', 'libx264',
                '-preset', $preset,
                '-crf', (string) $config['crf'],
                '-c:a', 'aac',
                '-b:a', $config['audio_bitrate'],
                '-hls_time', (string) $segmentDuration,
                '-hls_list_size', '0',
                '-hls_segment_filename', $segmentPattern,
                '-y',
                $playlistPath,
            ]);

            if (!$result->successful() || !file_exists($playlistPath)) {
                $job->markAsFailed($result->errorOutput());
                throw VideoProcessingException::transcodingFailed($quality, $result->errorOutput());
            }

            // Upload all files in quality directory to S3
            $this->uploadDirectoryToS3($qualityDir, "{$storageBaseDir}/{$quality}");

            $job->markAsCompleted();
            $availableQualities[] = $quality;
        }

        return $availableQualities;
    }

    /**
     * Generate master HLS playlist.
     */
    public function generateMasterPlaylist(
        Video $video,
        array $qualities,
        VideoMetadata $metadata
    ): string {
        $qualityConfigs = config('video.processing.qualities');
        $storageBaseDir = config('video.storage.hls_path') . "/{$video->id}";
        $localMasterPath = "{$this->tempDir}/{$video->id}/hls/master.m3u8";
        $storageMasterPath = "{$storageBaseDir}/master.m3u8";

        // Build master playlist content
        $lines = ['#EXTM3U', '#EXT-X-VERSION:3'];

        foreach ($qualities as $quality) {
            $config = $qualityConfigs[$quality] ?? null;
            if (!$config) {
                continue;
            }

            // Calculate approximate bandwidth
            $videoBitrate = $this->parseBitrate($config['video_bitrate']);
            $audioBitrate = $this->parseBitrate($config['audio_bitrate']);
            $bandwidth = $videoBitrate + $audioBitrate;

            // Calculate resolution maintaining aspect ratio
            $height = $config['height'];
            $width = (int) round($metadata->width * ($height / $metadata->height));
            // Ensure width is even (required by many codecs)
            $width = $width % 2 === 0 ? $width : $width + 1;

            $lines[] = "#EXT-X-STREAM-INF:BANDWIDTH={$bandwidth},RESOLUTION={$width}x{$height}";
            $lines[] = "{$quality}/playlist.m3u8";
        }

        $content = implode("\n", $lines);

        // Write locally and upload
        file_put_contents($localMasterPath, $content);

        $disk = Storage::disk(config('video.storage.disk'));
        $disk->put($storageMasterPath, $content);

        return $storageMasterPath;
    }

    /**
     * Mark video processing as complete.
     */
    public function markProcessingComplete(
        Video $video,
        array $availableQualities,
        string $hlsPath,
        string $thumbnailPath
    ): void {
        $video->update([
            'status' => Video::STATUS_READY,
            'processing_completed_at' => now(),
            'available_qualities' => $availableQualities,
            'hls_path' => $hlsPath,
            'thumbnail_path' => $thumbnailPath,
            'processing_error' => null,
        ]);
    }

    /**
     * Mark video processing as failed.
     */
    public function markProcessingFailed(Video $video, string $error): void
    {
        $maxAttempts = config('video.processing.max_attempts', 3);
        $status = $video->processing_attempts >= $maxAttempts
            ? Video::STATUS_FAILED
            : Video::STATUS_UPLOADED; // Allow retry

        $video->update([
            'status' => $status,
            'processing_error' => $error,
        ]);
    }

    /**
     * Download video from S3 to temp directory.
     */
    protected function downloadToTemp(Video $video): string
    {
        $disk = Storage::disk(config('video.storage.disk'));
        $extension = pathinfo($video->original_path, PATHINFO_EXTENSION);
        $localPath = "{$this->tempDir}/{$video->id}/original.{$extension}";

        // Ensure directory exists
        $dir = dirname($localPath);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        // Download file
        $stream = $disk->readStream($video->original_path);
        if (!$stream) {
            throw VideoProcessingException::inputFileNotFound($video->original_path);
        }

        file_put_contents($localPath, $stream);
        fclose($stream);

        return $localPath;
    }

    /**
     * Update video with analyzed metadata.
     */
    protected function updateVideoMetadata(Video $video, VideoMetadata $metadata): void
    {
        $video->update([
            'duration_seconds' => $metadata->durationSeconds,
            'width' => $metadata->width,
            'height' => $metadata->height,
            'aspect_ratio' => $metadata->aspectRatio,
            'file_size_bytes' => $metadata->fileSizeBytes ?: $video->file_size_bytes,
            'mime_type' => $metadata->mimeType,
        ]);
    }

    /**
     * Upload a local directory to S3.
     */
    protected function uploadDirectoryToS3(string $localDir, string $s3Prefix): void
    {
        $disk = Storage::disk(config('video.storage.disk'));

        $files = scandir($localDir);
        foreach ($files as $file) {
            if ($file === '.' || $file === '..') {
                continue;
            }

            $localPath = "{$localDir}/{$file}";
            $s3Path = "{$s3Prefix}/{$file}";

            if (is_file($localPath)) {
                $disk->put($s3Path, file_get_contents($localPath));
            }
        }
    }

    /**
     * Cleanup temp files for a video.
     */
    protected function cleanupTempFiles(string $videoId): void
    {
        $dir = "{$this->tempDir}/{$videoId}";
        if (is_dir($dir)) {
            $this->recursiveDelete($dir);
        }
    }

    /**
     * Recursively delete a directory.
     */
    protected function recursiveDelete(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }

        $items = scandir($dir);
        foreach ($items as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }

            $path = "{$dir}/{$item}";
            if (is_dir($path)) {
                $this->recursiveDelete($path);
            } else {
                unlink($path);
            }
        }
        rmdir($dir);
    }

    /**
     * Create a processing job record.
     */
    protected function createProcessingJob(
        Video $video,
        string $type,
        string $inputPath,
        string $outputPath
    ): VideoProcessingJob {
        return VideoProcessingJob::create([
            'video_id' => $video->id,
            'job_type' => $type,
            'status' => VideoProcessingJob::STATUS_PROCESSING,
            'input_path' => $inputPath,
            'output_path' => $outputPath,
            'started_at' => now(),
            'worker_id' => gethostname(),
        ]);
    }

    /**
     * Parse bitrate string to integer (e.g., "1400k" -> 1400000).
     */
    protected function parseBitrate(string $bitrate): int
    {
        $value = (int) $bitrate;
        if (str_ends_with($bitrate, 'k') || str_ends_with($bitrate, 'K')) {
            return $value * 1000;
        }
        if (str_ends_with($bitrate, 'm') || str_ends_with($bitrate, 'M')) {
            return $value * 1000000;
        }
        return $value;
    }

    /**
     * Build FFmpeg command string for logging.
     */
    protected function buildFfmpegCommand(
        string $input,
        array $config,
        string $segmentPattern,
        string $playlistPath,
        string $preset,
        int $segmentDuration
    ): string {
        return implode(' ', [
            $this->ffmpegPath,
            '-i', $input,
            '-vf', "scale=-2:{$config['height']}",
            '-c:v', 'libx264',
            '-preset', $preset,
            '-crf', $config['crf'],
            '-c:a', 'aac',
            '-b:a', $config['audio_bitrate'],
            '-hls_time', $segmentDuration,
            '-hls_list_size', '0',
            '-hls_segment_filename', $segmentPattern,
            '-y',
            $playlistPath,
        ]);
    }

    /**
     * Ensure FFmpeg is available.
     */
    protected function ensureFfmpegExists(): void
    {
        $result = Process::run(['which', $this->ffmpegPath]);
        if (!$result->successful()) {
            throw VideoProcessingException::ffmpegNotFound();
        }
    }

    /**
     * Ensure FFprobe is available.
     */
    protected function ensureFfprobeExists(): void
    {
        $result = Process::run(['which', $this->ffprobePath]);
        if (!$result->successful()) {
            throw VideoProcessingException::ffprobeNotFound();
        }
    }
}
