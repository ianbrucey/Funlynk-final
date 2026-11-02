<?php

namespace App\Poster\Services\Posters;

use App\Poster\Contracts\PlatformPoster;
use App\Poster\Enums\Platform;
use App\Poster\Exceptions\NotConfiguredException;
use App\Poster\Models\BundlePlatformPost;
use App\Poster\Models\PlatformAccount;
use App\Poster\Support\PublishResult;

abstract class BasePoster implements PlatformPoster
{
    abstract public function platform(): Platform;

    public function adaptCopy(string $caption): string
    {
        $max = $this->capabilities()->maxTextLength;

        if ($max !== null && mb_strlen($caption) > $max) {
            return rtrim(mb_substr($caption, 0, $max - 1)).'…';
        }

        return $caption;
    }

    /**
     * Phase 1 publish(): always refuses, with a precise reason. There is no
     * code path in this phase that reports a successful post.
     *
     * Phase 2 adapters (Facebook, Instagram, TikTok) override this with real
     * implementations; the remaining adapters still land here.
     *
     * @throws NotConfiguredException
     */
    public function publish(BundlePlatformPost $post): PublishResult
    {
        $platform = $this->platform();
        $account = $post->platformAccount;

        if ($account === null || $account->access_token === null) {
            throw new NotConfiguredException(
                "{$platform->label()} is not configured: no connected platform account with a token. ".
                'Connect the account first (see README account checklist); tokens are stored encrypted on platform_accounts, never in git.'
            );
        }

        $missing = [];
        foreach ($this->requiredConfigKeys() as $key) {
            if (blank(config("poster.credentials.{$key}"))) {
                $missing[] = $key;
            }
        }

        if ($missing !== []) {
            throw new NotConfiguredException(
                "{$platform->label()} is not configured: missing env values for ".implode(', ', $missing).'.'
            );
        }

        throw new NotConfiguredException(
            "{$platform->label()} API client is not implemented yet (Phase 2). Account and credentials are present, but no publish call has been built — refusing rather than faking success."
        );
    }

    /**
     * Resolve the connected account an adapter should publish with: the
     * account linked to this platform post when it is usable, otherwise the
     * connected account for the platform.
     *
     * @throws NotConfiguredException when no connected account with a token exists.
     */
    protected function connectedAccount(BundlePlatformPost $post): PlatformAccount
    {
        $linked = $post->platformAccount;

        if ($linked !== null && $linked->platform === $this->platform() && $linked->hasUsableToken()) {
            return $linked;
        }

        $account = PlatformAccount::query()
            ->where('platform', $this->platform())
            ->where('status', PlatformAccount::STATUS_CONNECTED)
            ->whereNotNull('access_token')
            ->latest('updated_at')
            ->first();

        if ($account === null || ! $account->hasUsableToken()) {
            throw new NotConfiguredException(
                "{$this->platform()->label()} is not configured: no connected platform account with a token. ".
                'Connect the account first via the poster connect flow; tokens are stored encrypted on platform_accounts, never in git.'
            );
        }

        return $account;
    }

    /**
     * The assets an adapter should publish: the post's normalized assets
     * when present, otherwise the bundle's source assets.
     *
     * @return list<array<string, mixed>>
     */
    protected function assetsFor(BundlePlatformPost $post): array
    {
        $normalized = $post->normalized_assets;

        if (is_array($normalized) && $normalized !== []) {
            return $this->toAssetList($normalized);
        }

        return $this->toAssetList($post->bundle->assets ?? []);
    }

    /**
     * @param  array<mixed>  $assets
     * @return list<array<string, mixed>>
     */
    private function toAssetList(array $assets): array
    {
        $list = [];

        foreach ($assets as $asset) {
            if (is_array($asset)) {
                $list[] = $asset;
            }
        }

        return $list;
    }

    /**
     * @return array<string, mixed>|null
     */
    protected function firstAsset(BundlePlatformPost $post, string $kind): ?array
    {
        foreach ($this->assetsFor($post) as $asset) {
            if (($asset['kind'] ?? null) === $kind) {
                return $asset;
            }
        }

        return null;
    }

    /**
     * Absolute path of an asset's file on local disk, when it exists.
     * Asset descriptors carry either an absolute 'path' (MediaNormalizer
     * output) or a 'local_path' relative to storage/app (intake output).
     *
     * @param  array<string, mixed>  $asset
     */
    protected function assetLocalPath(array $asset): ?string
    {
        $path = $asset['path'] ?? null;

        if (is_string($path) && $path !== '' && is_file($path)) {
            return $path;
        }

        $local = $asset['local_path'] ?? null;

        if (is_string($local) && $local !== '') {
            $absolute = storage_path("app/{$local}");

            if (is_file($absolute)) {
                return $absolute;
            }
        }

        return null;
    }

    /**
     * The public URL a platform can fetch this asset from, when one can be
     * determined: an explicit 'url' on the descriptor, a file on the public
     * disk, or a storage/app path mapped through POSTER_ASSET_BASE_URL.
     * Null means "not publicly reachable" — URL-fetch platforms must refuse.
     *
     * @param  array<string, mixed>  $asset
     */
    protected function assetPublicUrl(array $asset): ?string
    {
        $url = $asset['url'] ?? null;

        if (is_string($url) && $url !== '') {
            return $url;
        }

        $path = $asset['path'] ?? null;

        if (! is_string($path) || $path === '') {
            $local = $asset['local_path'] ?? null;
            $path = is_string($local) && $local !== '' ? storage_path("app/{$local}") : null;
        }

        if (! is_string($path) || $path === '') {
            return null;
        }

        $publicPrefix = storage_path('app/public/');

        if (str_starts_with($path, $publicPrefix)) {
            return rtrim((string) config('app.url'), '/').'/storage/'.substr($path, strlen($publicPrefix));
        }

        $base = config('poster.asset_base_url');
        $appPrefix = storage_path('app/');

        if (is_string($base) && $base !== '' && str_starts_with($path, $appPrefix)) {
            return rtrim($base, '/').'/'.substr($path, strlen($appPrefix));
        }

        return null;
    }
}
