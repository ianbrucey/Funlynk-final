<?php

namespace App\DTOs;

use Carbon\CarbonInterface;

readonly class UploadUrlResult
{
    public function __construct(
        public string $videoId,
        public string $uploadUrl,
        public string $uploadToken,
        public string $uploadPath,
        public CarbonInterface $expiresAt,
    ) {}

    public function toArray(): array
    {
        return [
            'video_id' => $this->videoId,
            'upload_url' => $this->uploadUrl,
            'upload_token' => $this->uploadToken,
            'upload_path' => $this->uploadPath,
            'expires_at' => $this->expiresAt->toIso8601String(),
        ];
    }
}
