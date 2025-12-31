<?php

namespace App\DTOs;

readonly class VideoMetadata
{
    public function __construct(
        public int $durationSeconds,
        public int $width,
        public int $height,
        public string $aspectRatio,
        public int $fileSizeBytes,
        public string $mimeType,
        public ?string $codec = null,
        public ?int $bitrate = null,
    ) {}

    public static function fromFfprobe(array $data): self
    {
        $videoStream = collect($data['streams'] ?? [])
            ->firstWhere('codec_type', 'video');

        $format = $data['format'] ?? [];

        $width = (int) ($videoStream['width'] ?? 0);
        $height = (int) ($videoStream['height'] ?? 0);

        return new self(
            durationSeconds: (int) ceil((float) ($format['duration'] ?? 0)),
            width: $width,
            height: $height,
            aspectRatio: self::calculateAspectRatio($width, $height),
            fileSizeBytes: (int) ($format['size'] ?? 0),
            mimeType: self::detectMimeType($format['format_name'] ?? ''),
            codec: $videoStream['codec_name'] ?? null,
            bitrate: isset($format['bit_rate']) ? (int) $format['bit_rate'] : null,
        );
    }

    private static function calculateAspectRatio(int $width, int $height): string
    {
        if ($width === 0 || $height === 0) {
            return 'unknown';
        }

        $gcd = self::gcd($width, $height);
        $w = $width / $gcd;
        $h = $height / $gcd;

        // Normalize common ratios
        $ratio = $width / $height;

        if (abs($ratio - 16/9) < 0.1) {
            return '16:9';
        }
        if (abs($ratio - 9/16) < 0.1) {
            return '9:16';
        }
        if (abs($ratio - 4/3) < 0.1) {
            return '4:3';
        }
        if (abs($ratio - 1) < 0.1) {
            return '1:1';
        }
        if (abs($ratio - 4/5) < 0.1) {
            return '4:5';
        }

        return "{$w}:{$h}";
    }

    private static function gcd(int $a, int $b): int
    {
        return $b === 0 ? $a : self::gcd($b, $a % $b);
    }

    private static function detectMimeType(string $formatName): string
    {
        return match (true) {
            str_contains($formatName, 'mp4') => 'video/mp4',
            str_contains($formatName, 'mov') => 'video/quicktime',
            str_contains($formatName, 'webm') => 'video/webm',
            str_contains($formatName, 'avi') => 'video/x-msvideo',
            default => 'video/mp4',
        };
    }

    public function toArray(): array
    {
        return [
            'duration_seconds' => $this->durationSeconds,
            'width' => $this->width,
            'height' => $this->height,
            'aspect_ratio' => $this->aspectRatio,
            'file_size_bytes' => $this->fileSizeBytes,
            'mime_type' => $this->mimeType,
            'codec' => $this->codec,
            'bitrate' => $this->bitrate,
        ];
    }
}
