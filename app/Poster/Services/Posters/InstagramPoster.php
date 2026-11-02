<?php

namespace App\Poster\Services\Posters;

use App\Poster\Enums\Platform;
use App\Poster\Support\PlatformCapabilities;

/**
 * Instagram Content Publishing API. Professional (Business/Creator) accounts
 * only; media is mandatory; photos are JPEG only with aspect ratio between
 * 4:5 (0.8) and 1.91:1. Media is fetched by Meta from a public URL.
 */
class InstagramPoster extends BasePoster
{
    public function platform(): Platform
    {
        return Platform::Instagram;
    }

    public function capabilities(): PlatformCapabilities
    {
        return new PlatformCapabilities(
            supportsTextOnly: false,
            supportsPhotos: true,
            supportsVideo: true,
            supportsCarousel: true,
            requiresMedia: true,
            mediaMustBePublicUrl: true,
            maxTextLength: 2200,
            maxPhotos: 10,
            notes: 'JPEG only, aspect 0.8–1.91, ≤8MB per image. Two-step container flow; containers expire after 24h. Daily publish cap is documented inconsistently by Meta (50 vs 100) — query content_publishing_limit at runtime in Phase 2.',
        );
    }

    public function requiredConfigKeys(): array
    {
        return ['instagram_user_id'];
    }
}
