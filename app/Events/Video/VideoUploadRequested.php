<?php

namespace App\Events\Video;

use App\Models\User;
use App\Models\VideoUploadToken;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Fired when a user requests a pre-signed upload URL.
 * 
 * Use cases:
 * - Rate limiting monitoring
 * - Analytics tracking
 * - Fraud detection
 */
class VideoUploadRequested
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly User $user,
        public readonly VideoUploadToken $token,
        public readonly string $videoableType,
        public readonly string $videoableId,
        public readonly string $filename,
        public readonly int $contentLength,
    ) {}
}
