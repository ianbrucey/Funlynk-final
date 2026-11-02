<?php

namespace App\Poster\Services\Posters;

use App\Poster\Enums\Platform;
use App\Poster\Exceptions\NotConfiguredException;
use App\Poster\Models\BundlePlatformPost;
use App\Poster\Support\PlatformCapabilities;
use App\Poster\Support\PublishResult;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use RuntimeException;

/**
 * Instagram Content Publishing API. Professional (Business/Creator) accounts
 * only; media is mandatory; photos are JPEG only with aspect ratio between
 * 4:5 (0.8) and 1.91:1. Media is fetched by Meta from a public URL.
 *
 * Phase 2: real two-step container flow against the Instagram account
 * connected to the Funlynk Page (graph.facebook.com): create a media
 * container from the normalized asset's public URL, poll its status, then
 * publish. Media without a public URL is refused — Meta cannot fetch it.
 */
class InstagramPoster extends BasePoster
{
    private const MAX_STATUS_POLLS = 15;

    private const POLL_INTERVAL_SECONDS = 2;

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

    public function publish(BundlePlatformPost $post): PublishResult
    {
        $account = $this->connectedAccount($post);
        $token = (string) $account->access_token;
        $igId = $account->external_id ?: config('poster.credentials.instagram_user_id');

        if (blank($igId)) {
            throw new NotConfiguredException(
                'Instagram is not configured: no Instagram account id. Connect via /poster/connect/meta (stores the IG account linked to the Page), or set INSTAGRAM_USER_ID.'
            );
        }

        $image = $this->firstAsset($post, 'image');
        $video = $this->firstAsset($post, 'video');
        $media = $image ?? $video;

        if ($media === null) {
            throw new NotConfiguredException(
                'Instagram is not configured for this bundle: Instagram requires a photo or video asset, and this bundle has none.'
            );
        }

        $mediaUrl = $this->assetPublicUrl($media);

        if ($mediaUrl === null) {
            throw new NotConfiguredException(
                'Instagram media is not reachable at a public URL: Instagram fetches media by URL, and this asset has no public URL. '.
                'Host the normalized asset publicly (see POSTER_ASSET_BASE_URL) before publishing.'
            );
        }

        $caption = $post->adapted_caption ?? $post->bundle->caption;
        $containerId = $this->createContainer((string) $igId, $token, $media, $mediaUrl, $caption);

        $this->waitForContainer($containerId, $token);

        $mediaId = $this->publishContainer((string) $igId, $token, $containerId);

        return new PublishResult($mediaId, $this->fetchPermalink($mediaId, $token));
    }

    /**
     * @param  array<string, mixed>  $media
     */
    private function createContainer(string $igId, string $token, array $media, string $mediaUrl, string $caption): string
    {
        $isVideo = ($media['kind'] ?? null) === 'video';

        $params = [
            'caption' => $caption,
            'access_token' => $token,
        ];

        if ($isVideo) {
            $params['media_type'] = 'REELS';
            $params['video_url'] = $mediaUrl;
        } else {
            $params['image_url'] = $mediaUrl;
        }

        $response = Http::post($this->graphUrl("/{$igId}/media"), $params);

        $this->throwOnError($response, 'media container creation');

        $id = $response->json('id');

        if (! is_string($id) || $id === '') {
            throw new RuntimeException('Instagram media container creation failed: the API response contained no container id.');
        }

        return $id;
    }

    private function waitForContainer(string $containerId, string $token): void
    {
        for ($attempt = 0; $attempt < self::MAX_STATUS_POLLS; $attempt++) {
            $response = Http::get($this->graphUrl("/{$containerId}"), [
                'fields' => 'status_code,status',
                'access_token' => $token,
            ]);

            $this->throwOnError($response, 'media container status check');

            $status = $response->json('status_code');

            if ($status === 'FINISHED') {
                return;
            }

            if ($status === 'ERROR' || $status === 'EXPIRED') {
                $detail = $response->json('status');
                $detail = is_string($detail) && $detail !== '' ? ": {$detail}" : '';

                throw new RuntimeException("Instagram media container {$status}{$detail}");
            }

            if ($attempt < self::MAX_STATUS_POLLS - 1) {
                sleep(self::POLL_INTERVAL_SECONDS);
            }
        }

        throw new RuntimeException(
            'Instagram media container was still processing after '.(self::MAX_STATUS_POLLS * self::POLL_INTERVAL_SECONDS).' seconds; refusing to publish an unready container.'
        );
    }

    private function publishContainer(string $igId, string $token, string $containerId): string
    {
        $response = Http::post($this->graphUrl("/{$igId}/media_publish"), [
            'creation_id' => $containerId,
            'access_token' => $token,
        ]);

        $this->throwOnError($response, 'media publish');

        $id = $response->json('id');

        if (! is_string($id) || $id === '') {
            throw new RuntimeException('Instagram media publish failed: the API response contained no media id.');
        }

        return $id;
    }

    private function fetchPermalink(string $mediaId, string $token): ?string
    {
        try {
            $response = Http::get($this->graphUrl("/{$mediaId}"), [
                'fields' => 'permalink',
                'access_token' => $token,
            ]);

            $permalink = $response->json('permalink');

            return is_string($permalink) && $permalink !== '' ? $permalink : null;
        } catch (\Throwable) {
            // The post exists; a missing permalink must not fail the publish.
            return null;
        }
    }

    private function graphUrl(string $path): string
    {
        $version = (string) (config('poster.meta.graph_version') ?: 'v21.0');

        return "https://graph.facebook.com/{$version}{$path}";
    }

    private function throwOnError(Response $response, string $action): void
    {
        if ($response->successful()) {
            return;
        }

        $message = $response->json('error.message');
        $message = is_string($message) && $message !== '' ? $message : "HTTP {$response->status()}";

        throw new RuntimeException("Instagram {$action} failed: {$message}");
    }
}
