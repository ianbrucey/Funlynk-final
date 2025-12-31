<?php

namespace App\Exceptions\Video;

use Exception;

class VideoProcessingException extends Exception
{
    public static function ffmpegNotFound(): self
    {
        return new self('FFmpeg binary not found. Please install FFmpeg.');
    }

    public static function ffprobeNotFound(): self
    {
        return new self('FFprobe binary not found. Please install FFmpeg.');
    }

    public static function analysisFailedError(string $error): self
    {
        return new self("Video analysis failed: {$error}");
    }

    public static function transcodingFailed(string $quality, string $error): self
    {
        return new self("Transcoding to {$quality} failed: {$error}");
    }

    public static function thumbnailGenerationFailed(string $error): self
    {
        return new self("Thumbnail generation failed: {$error}");
    }

    public static function inputFileNotFound(string $path): self
    {
        return new self("Input video file not found: {$path}");
    }

    public static function maxAttemptsExceeded(int $attempts): self
    {
        return new self("Maximum processing attempts ({$attempts}) exceeded");
    }
}
