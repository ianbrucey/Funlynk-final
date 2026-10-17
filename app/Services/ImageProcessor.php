<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Intervention\Image\Encoders\WebpEncoder;
use Intervention\Image\ImageManager;

/**
 * Single entry point for storing user-uploaded images.
 *
 * Every upload is decoded, auto-rotated from EXIF, shrunk to a size preset and
 * re-encoded as WebP so original multi-megabyte camera photos never reach storage.
 */
class ImageProcessor
{
    public const PRESETS = [
        'avatar' => ['width' => 400, 'height' => 400, 'crop' => true],
        'group_avatar' => ['width' => 400, 'height' => 400, 'crop' => true],
        'cover' => ['width' => 1600, 'height' => 900, 'crop' => false],
        'post' => ['width' => 1600, 'height' => 1600, 'crop' => false],
    ];

    public const QUALITY = 82;

    /**
     * Resize and store an uploaded file (or raw image bytes). Returns the storage path.
     *
     * @param  UploadedFile|object|string  $source  Uploaded file, Livewire temp upload, or binary string
     */
    public function store(mixed $source, string $directory, string $preset = 'post', string $disk = 'public'): string
    {
        $config = self::PRESETS[$preset] ?? self::PRESETS['post'];

        $manager = $this->manager();
        $image = $manager->read($this->contents($source));

        if ($config['crop']) {
            $image->cover($config['width'], $config['height']);
        } else {
            $image->scaleDown($config['width'], $config['height']);
        }

        $encoded = $image->encode(new WebpEncoder(quality: self::QUALITY));

        $path = trim($directory, '/').'/'.Str::uuid().'.webp';
        Storage::disk($disk)->put($path, (string) $encoded);

        return $path;
    }

    protected function manager(): ImageManager
    {
        return extension_loaded('imagick') ? ImageManager::imagick() : ImageManager::gd();
    }

    protected function contents(mixed $source): string
    {
        if (is_string($source)) {
            return $source;
        }

        return $source->get();
    }
}
