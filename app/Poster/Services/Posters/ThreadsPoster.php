<?php

namespace App\Poster\Services\Posters;

use App\Poster\Enums\Platform;
use App\Poster\Support\PlatformCapabilities;

/**
 * Threads API (graph.threads.net). Media is URL-only — there is no upload
 * endpoint, so assets must sit on a public server (POSTER_ASSET_BASE_URL).
 * 250 API-published posts per profile per rolling 24h. Long-lived tokens
 * last 60 days and must be refreshed before expiry.
 */
class ThreadsPoster extends BasePoster
{
    public function platform(): Platform
    {
        return Platform::Threads;
    }

    public function capabilities(): PlatformCapabilities
    {
        return new PlatformCapabilities(
            supportsTextOnly: true,
            supportsPhotos: true,
            supportsVideo: true,
            supportsCarousel: true,
            requiresMedia: false,
            mediaMustBePublicUrl: true,
            maxTextLength: 500,
            notes: 'Text-only allowed (≤500 chars, UTF-8 bytes — emoji cost more). Container must finish processing (~30s, poll status) before publish; containers expire after 24h.',
        );
    }

    public function requiredConfigKeys(): array
    {
        return ['threads_user_id'];
    }
}
