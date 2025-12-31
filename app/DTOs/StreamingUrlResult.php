<?php

namespace App\DTOs;

use Carbon\CarbonInterface;

readonly class StreamingUrlResult
{
    public function __construct(
        public string $hlsUrl,
        public ?string $thumbnailUrl,
        public CarbonInterface $expiresAt,
        public array $availableQualities,
    ) {}

    public function toArray(): array
    {
        return [
            'hls_url' => $this->hlsUrl,
            'thumbnail_url' => $this->thumbnailUrl,
            'expires_at' => $this->expiresAt->toIso8601String(),
            'available_qualities' => $this->availableQualities,
        ];
    }
}
