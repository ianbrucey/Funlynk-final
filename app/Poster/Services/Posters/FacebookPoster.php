<?php

namespace App\Poster\Services\Posters;

use App\Poster\Enums\Platform;
use App\Poster\Support\PlatformCapabilities;

/**
 * Facebook Pages via Graph API. Pages only — personal profiles cannot be
 * posted to via API (removed 2018). Needs the pages_manage_posts permission.
 */
class FacebookPoster extends BasePoster
{
    public function platform(): Platform
    {
        return Platform::Facebook;
    }

    public function capabilities(): PlatformCapabilities
    {
        return new PlatformCapabilities(
            supportsTextOnly: true,
            supportsPhotos: true,
            supportsVideo: true,
            supportsCarousel: true,
            requiresMedia: false,
            mediaMustBePublicUrl: false,
            maxTextLength: 63206,
            notes: 'Page posts only (feed/photos/videos/Reels). Reels capped at 30 API-published per 24h. No idempotency keys — never blind-retry a timed-out publish.',
        );
    }

    public function requiredConfigKeys(): array
    {
        return ['facebook_page_id'];
    }
}
