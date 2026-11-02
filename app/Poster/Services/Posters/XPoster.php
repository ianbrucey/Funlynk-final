<?php

namespace App\Poster\Services\Posters;

use App\Poster\Enums\Platform;
use App\Poster\Support\PlatformCapabilities;

/**
 * X API v2. No app review — just a developer account and prepaid credits.
 * Pay-per-use: $0.015 per post created, $0.200 if the post contains a URL —
 * the X copy template must treat links as expensive. Media ids expire 24h
 * after upload, so media is uploaded at publish time, never in advance.
 */
class XPoster extends BasePoster
{
    public function platform(): Platform
    {
        return Platform::X;
    }

    public function capabilities(): PlatformCapabilities
    {
        return new PlatformCapabilities(
            supportsTextOnly: true,
            supportsPhotos: true,
            supportsVideo: true,
            supportsCarousel: false,
            requiresMedia: false,
            mediaMustBePublicUrl: false,
            maxTextLength: 280,
            maxPhotos: 4,
            notes: 'Media types cannot be mixed in one post (4 photos OR 1 GIF OR 1 video). OAuth2 access tokens last 2h without the offline.access scope.',
        );
    }

    public function requiredConfigKeys(): array
    {
        return ['x_client_id', 'x_client_secret'];
    }
}
