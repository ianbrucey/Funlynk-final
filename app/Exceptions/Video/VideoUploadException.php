<?php

namespace App\Exceptions\Video;

use Exception;

class VideoUploadException extends Exception
{
    public static function fileTooLarge(int $fileSize, int $maxSize): self
    {
        $maxMb = round($maxSize / 1024 / 1024);
        return new self("File size exceeds maximum of {$maxMb}MB");
    }

    public static function durationTooLong(int $duration, int $maxDuration): self
    {
        return new self("Video duration exceeds maximum of {$maxDuration} seconds");
    }

    public static function invalidMimeType(string $mimeType, array $allowed): self
    {
        $allowedStr = implode(', ', $allowed);
        return new self("Invalid file type '{$mimeType}'. Allowed: {$allowedStr}");
    }

    public static function rateLimitExceeded(int $limit): self
    {
        return new self("Upload rate limit exceeded. Maximum {$limit} uploads per hour.");
    }

    public static function storageQuotaExceeded(int $used, int $max): self
    {
        $usedMb = round($used / 1024 / 1024);
        $maxMb = round($max / 1024 / 1024);
        return new self("Storage quota exceeded ({$usedMb}MB / {$maxMb}MB)");
    }
}
