<?php

namespace App\Poster\Contracts;

use App\Poster\Enums\Platform;
use App\Poster\Exceptions\NotConfiguredException;
use App\Poster\Models\BundlePlatformPost;
use App\Poster\Support\PlatformCapabilities;
use App\Poster\Support\PublishResult;

/**
 * One adapter per platform. Phase 1: adapters carry verified capability
 * metadata and real copy adaptation, but publish() throws
 * NotConfiguredException until a connected account + API client exist.
 */
interface PlatformPoster
{
    public function platform(): Platform;

    public function capabilities(): PlatformCapabilities;

    /**
     * Env keys (see .env.example / config/poster.php) that must be present
     * before this platform can be configured.
     *
     * @return list<string>
     */
    public function requiredConfigKeys(): array;

    /**
     * Adapt the bundle caption to this platform's conventions/limits.
     * The result is stored on the BundlePlatformPost before publishing.
     */
    public function adaptCopy(string $caption): string;

    /**
     * Publish one platform post.
     *
     * @throws NotConfiguredException when credentials/account/client are missing.
     */
    public function publish(BundlePlatformPost $post): PublishResult;
}
