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
 * Facebook Pages via Graph API. Pages only — personal profiles cannot be
 * posted to via API (removed 2018). Needs the pages_manage_posts permission.
 *
 * Phase 2: real publishing with the Page access token stored (encrypted) on
 * the connected PlatformAccount. Supports feed (text/link), photo, and
 * video posts — matching the declared capabilities. Errors from the Graph
 * API surface verbatim; there is no fake-success path.
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

    public function publish(BundlePlatformPost $post): PublishResult
    {
        $account = $this->connectedAccount($post);
        $token = (string) $account->access_token;
        $pageId = $account->external_id ?: config('poster.meta.page_id');

        if (blank($pageId)) {
            throw new NotConfiguredException(
                'Facebook is not configured: no Page id. Connect the Funlynk Page via /poster/connect/meta, or set POSTER_META_PAGE_ID.'
            );
        }

        $caption = $post->adapted_caption ?? $post->bundle->caption;
        $image = $this->firstAsset($post, 'image');
        $video = $this->firstAsset($post, 'video');

        if ($image !== null) {
            return $this->publishPhoto($post, (string) $pageId, $token, $image, $caption);
        }

        if ($video !== null) {
            return $this->publishVideo((string) $pageId, $token, $video, $caption);
        }

        return $this->publishFeedPost($post, (string) $pageId, $token, $caption);
    }

    /**
     * @param  array<string, mixed>  $image
     */
    private function publishPhoto(BundlePlatformPost $post, string $pageId, string $token, array $image, string $caption): PublishResult
    {
        $localPath = $this->assetLocalPath($image);

        if ($localPath !== null) {
            $contents = file_get_contents($localPath);

            if ($contents === false) {
                throw new RuntimeException("Facebook photo post failed: could not read image file {$localPath}.");
            }

            $response = Http::attach('source', $contents, basename($localPath))
                ->post($this->graphUrl("/{$pageId}/photos"), [
                    'caption' => $caption,
                    'access_token' => $token,
                ]);
        } else {
            $url = $this->assetPublicUrl($image);

            if ($url === null) {
                throw new RuntimeException(
                    'Facebook photo post failed: the image is neither on local disk nor reachable at a public URL.'
                );
            }

            $response = Http::post($this->graphUrl("/{$pageId}/photos"), [
                'url' => $url,
                'caption' => $caption,
                'access_token' => $token,
            ]);
        }

        $this->throwOnError($response, 'photo post');

        $id = $response->json('post_id') ?? $response->json('id');

        if (! is_string($id) || $id === '') {
            throw new RuntimeException('Facebook photo post failed: the API response contained no post id.');
        }

        return new PublishResult($id, "https://www.facebook.com/{$id}");
    }

    /**
     * @param  array<string, mixed>  $video
     */
    private function publishVideo(string $pageId, string $token, array $video, string $caption): PublishResult
    {
        $localPath = $this->assetLocalPath($video);

        if ($localPath !== null) {
            $contents = file_get_contents($localPath);

            if ($contents === false) {
                throw new RuntimeException("Facebook video post failed: could not read video file {$localPath}.");
            }

            $response = Http::attach('source', $contents, basename($localPath))
                ->post($this->graphUrl("/{$pageId}/videos"), [
                    'description' => $caption,
                    'access_token' => $token,
                ]);
        } else {
            $url = $this->assetPublicUrl($video);

            if ($url === null) {
                throw new RuntimeException(
                    'Facebook video post failed: the video is neither on local disk nor reachable at a public URL.'
                );
            }

            $response = Http::post($this->graphUrl("/{$pageId}/videos"), [
                'file_url' => $url,
                'description' => $caption,
                'access_token' => $token,
            ]);
        }

        $this->throwOnError($response, 'video post');

        $id = $response->json('id');

        if (! is_string($id) || $id === '') {
            throw new RuntimeException('Facebook video post failed: the API response contained no video id.');
        }

        return new PublishResult($id, "https://www.facebook.com/{$id}");
    }

    private function publishFeedPost(BundlePlatformPost $post, string $pageId, string $token, string $caption): PublishResult
    {
        $params = [
            'message' => $caption,
            'access_token' => $token,
        ];

        foreach ($this->assetsFor($post) as $asset) {
            if (($asset['kind'] ?? null) === 'link' && is_string($asset['url'] ?? null) && $asset['url'] !== '') {
                $params['link'] = $asset['url'];
                break;
            }
        }

        $response = Http::post($this->graphUrl("/{$pageId}/feed"), $params);

        $this->throwOnError($response, 'feed post');

        $id = $response->json('id');

        if (! is_string($id) || $id === '') {
            throw new RuntimeException('Facebook feed post failed: the API response contained no post id.');
        }

        return new PublishResult($id, "https://www.facebook.com/{$id}");
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

        throw new RuntimeException("Facebook {$action} failed: {$message}");
    }
}
