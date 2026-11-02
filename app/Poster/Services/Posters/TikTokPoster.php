<?php

namespace App\Poster\Services\Posters;

use App\Poster\Enums\Platform;
use App\Poster\Support\PlatformCapabilities;

/**
 * TikTok Content Posting API. No text-only format exists. Until the API
 * client passes TikTok's Content Posting API audit, Direct Posts are forced
 * private (SELF_ONLY) — the audit is the long pole and starts at account
 * setup time, not at publish time. User access tokens live only 24h.
 */
class TikTokPoster extends BasePoster
{
    public function platform(): Platform
    {
        return Platform::TikTok;
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
            maxPhotos: 35,
            notes: 'Photo posts are PULL_FROM_URL only, from a verified domain we own. ~15 Direct Posts/day per creator across all API clients. Audit also imposes posting-UI rules (no default privacy selection, disclosure toggles off by default).',
        );
    }

    public function requiredConfigKeys(): array
    {
        return ['tiktok_client_key', 'tiktok_client_secret'];
    }
}
