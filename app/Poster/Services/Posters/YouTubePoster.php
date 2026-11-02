<?php

namespace App\Poster\Services\Posters;

use App\Poster\Enums\Platform;
use App\Poster\Support\PlatformCapabilities;

/**
 * YouTube Data API v3 (videos.insert). Video only — Community posts are NOT
 * API-publishable at all, and photos exist only as Community posts. Shorts
 * are just vertical uploads within the Shorts length limit; there is no
 * separate Shorts endpoint. Quota: 100 videos.insert calls/day by default.
 */
class YouTubePoster extends BasePoster
{
    public function platform(): Platform
    {
        return Platform::YouTube;
    }

    public function capabilities(): PlatformCapabilities
    {
        return new PlatformCapabilities(
            supportsTextOnly: false,
            supportsPhotos: false,
            supportsVideo: true,
            supportsCarousel: false,
            requiresMedia: true,
            mediaMustBePublicUrl: false,
            maxTextLength: 5000,
            notes: 'Requires a Google account WITH a YouTube channel. After upload, always read back status.privacyStatus — Google docs contradict themselves on whether unaudited projects get forced to private. selfDeclaredMadeForKids and synthetic-media disclosure must be set per upload.',
        );
    }

    public function requiredConfigKeys(): array
    {
        return ['youtube_client_id', 'youtube_client_secret', 'youtube_channel_id'];
    }

    public function adaptCopy(string $caption): string
    {
        // YouTube uses the caption as the video description; titles come from
        // the bundle title at publish time. No truncation surprises here.
        return $caption;
    }
}
