<?php

namespace App\Poster\Services;

use App\Poster\Enums\Platform;
use RuntimeException;

/**
 * Turns a source asset into the variant a platform accepts.
 *
 * Phase 1 scope: images, via GD (no external services). The load-bearing
 * rule is Instagram's: JPEG only, aspect ratio between 4:5 (0.8) and
 * 1.91:1, max width 1080. Everything else gets a passthrough copy for now;
 * video transcoding is a Phase 2 concern.
 *
 * Output goes under storage/app/poster/normalized/<platform>/ and the
 * returned descriptors are what get stored on bundle_platform_posts
 * .normalized_assets.
 */
class MediaNormalizer
{
    public const INSTAGRAM_MIN_RATIO = 0.8;   // 4:5 portrait

    public const INSTAGRAM_MAX_RATIO = 1.91;  // 1.91:1 landscape

    public const INSTAGRAM_MAX_WIDTH = 1080;

    /**
     * @return list<array{kind: string, path: string, format: string}>
     */
    public function variantsFor(Platform $platform, string $sourcePath): array
    {
        $kind = $this->kindOf($sourcePath);

        if ($kind === 'video') {
            return [['kind' => 'video', 'path' => $this->passthrough($platform, $sourcePath), 'format' => pathinfo($sourcePath, PATHINFO_EXTENSION)]];
        }

        return match ($platform) {
            Platform::Instagram => [['kind' => 'image', 'path' => $this->instagramJpeg($sourcePath), 'format' => 'jpg']],
            default => [['kind' => 'image', 'path' => $this->passthrough($platform, $sourcePath), 'format' => pathinfo($sourcePath, PATHINFO_EXTENSION)]],
        };
    }

    /**
     * Convert any GD-readable image to an Instagram-safe JPEG: center-crop
     * to an aspect ratio inside [0.8, 1.91], downscale to ≤1080px wide,
     * encode as JPEG. Returns the absolute path of the new file.
     */
    public function instagramJpeg(string $sourcePath): string
    {
        if (! extension_loaded('gd')) {
            throw new RuntimeException('MediaNormalizer requires the GD extension.');
        }

        [$width, $height, $type] = $this->imageInfo($sourcePath);

        $image = match ($type) {
            IMAGETYPE_JPEG => imagecreatefromjpeg($sourcePath),
            IMAGETYPE_PNG => imagecreatefrompng($sourcePath),
            IMAGETYPE_WEBP => imagecreatefromwebp($sourcePath),
            IMAGETYPE_GIF => imagecreatefromgif($sourcePath),
            default => throw new RuntimeException("Unsupported image type for normalization: {$sourcePath}"),
        };

        if ($image === false) {
            throw new RuntimeException("Could not read image: {$sourcePath}");
        }

        // Center-crop to the clamped aspect ratio.
        $ratio = $width / $height;
        $targetRatio = max(self::INSTAGRAM_MIN_RATIO, min(self::INSTAGRAM_MAX_RATIO, $ratio));

        if ($ratio > $targetRatio) {
            $cropWidth = (int) round($height * $targetRatio);
            $srcX = intdiv($width - $cropWidth, 2);
            $srcY = 0;
            $cropHeight = $height;
        } else {
            $cropWidth = $width;
            $cropHeight = (int) round($width / $targetRatio);
            $srcX = 0;
            $srcY = intdiv($height - $cropHeight, 2);
        }

        // Downscale (never upscale) to Instagram's max width.
        $outWidth = max(1, min($cropWidth, self::INSTAGRAM_MAX_WIDTH));
        $outHeight = max(1, (int) round($outWidth / $targetRatio));

        $canvas = imagecreatetruecolor($outWidth, $outHeight);
        imagecopyresampled($canvas, $image, 0, 0, $srcX, $srcY, $outWidth, $outHeight, $cropWidth, $cropHeight);

        $dest = $this->outputPath(Platform::Instagram, pathinfo($sourcePath, PATHINFO_FILENAME).'.jpg');
        imagejpeg($canvas, $dest, 88);

        imagedestroy($image);
        imagedestroy($canvas);

        return $dest;
    }

    /**
     * Copy the asset unchanged into the platform's normalized directory.
     */
    public function passthrough(Platform $platform, string $sourcePath): string
    {
        $dest = $this->outputPath($platform, basename($sourcePath));
        if (! copy($sourcePath, $dest)) {
            throw new RuntimeException("Could not copy {$sourcePath} to {$dest}");
        }

        return $dest;
    }

    private function kindOf(string $path): string
    {
        $mime = mime_content_type($path) ?: '';

        return str_starts_with($mime, 'video/') ? 'video' : 'image';
    }

    /**
     * @return array{0: int, 1: int, 2: int} width, height, IMAGETYPE_* constant
     */
    private function imageInfo(string $path): array
    {
        $info = getimagesize($path);
        if ($info === false) {
            throw new RuntimeException("Not a readable image: {$path}");
        }

        return [$info[0], $info[1], $info[2]];
    }

    private function outputPath(Platform $platform, string $filename): string
    {
        $dir = storage_path("app/poster/normalized/{$platform->value}");
        if (! is_dir($dir)) {
            mkdir($dir, 0775, true);
        }

        return $dir.'/'.pathinfo($filename, PATHINFO_FILENAME).'-'.substr(md5($filename.microtime(true)), 0, 8).'.'.pathinfo($filename, PATHINFO_EXTENSION);
    }
}
