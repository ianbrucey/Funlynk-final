<?php

namespace App\Poster\Services\Posters;

use App\Poster\Enums\Platform;
use App\Poster\Exceptions\NotConfiguredException;
use App\Poster\Models\BundlePlatformPost;
use App\Poster\Models\PlatformAccount;
use App\Poster\Support\PlatformCapabilities;
use App\Poster\Support\PublishResult;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use RuntimeException;

/**
 * TikTok Content Posting API. No text-only format exists. Until the API
 * client passes TikTok's Content Posting API audit, Direct Posts are forced
 * private (SELF_ONLY) — the audit is the long pole and starts at account
 * setup time, not at publish time. User access tokens live only 24h.
 *
 * Phase 2: the connected app holds only the user.info.basic + video.upload
 * scopes, so this adapter implements the Upload (inbox/drafts) flow:
 * initialize a FILE_UPLOAD, PUT the video in chunks, then check status.
 * An approved bundle's video lands in the account's TikTok drafts/inbox
 * for review in the app — it is NOT directly posted. Photo posts are
 * PULL_FROM_URL-only and are not implemented here; they fail clearly.
 */
class TikTokPoster extends BasePoster
{
    private const API_BASE = 'https://open.tiktokapis.com/v2';

    /** Files at or under this size upload as a single chunk. */
    private const SINGLE_CHUNK_MAX = 64 * 1024 * 1024;

    private const CHUNK_SIZE = 10 * 1024 * 1024;

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

    public function publish(BundlePlatformPost $post): PublishResult
    {
        $account = $this->connectedAccount($post);
        $this->refreshTokenIfExpired($account);
        $token = (string) $account->access_token;

        $video = $this->firstAsset($post, 'video');

        if ($video === null) {
            throw new RuntimeException(
                'TikTok upload requires a video asset: the connected app has the video.upload scope only. '.
                'Photo posts use a separate PULL_FROM_URL flow that is not implemented, and TikTok has no text-only format.'
            );
        }

        $path = $this->assetLocalPath($video);

        if ($path === null) {
            throw new NotConfiguredException(
                'TikTok upload is not configured for this bundle: FILE_UPLOAD needs the video file on local disk, and the bundle video asset has no readable local file.'
            );
        }

        $size = filesize($path);

        if ($size === false || $size === 0) {
            throw new RuntimeException("TikTok upload failed: video file {$path} is empty or unreadable.");
        }

        [$chunkSize, $chunkCount] = $size <= self::SINGLE_CHUNK_MAX
            ? [$size, 1]
            : [self::CHUNK_SIZE, (int) ceil($size / self::CHUNK_SIZE)];

        [$publishId, $uploadUrl] = $this->initializeUpload($token, $size, $chunkSize, $chunkCount);

        $mime = is_string($video['mime'] ?? null) && $video['mime'] !== '' ? $video['mime'] : 'video/mp4';
        $this->uploadChunks($uploadUrl, $path, $size, $chunkSize, $mime);

        $this->assertNotFailed($token, $publishId);

        return new PublishResult($publishId);
    }

    /**
     * @return array{0: string, 1: string} publish id and upload URL
     */
    private function initializeUpload(string $token, int $size, int $chunkSize, int $chunkCount): array
    {
        $response = Http::withToken($token)
            ->post(self::API_BASE.'/post/publish/inbox/video/init/', [
                'source_info' => [
                    'source' => 'FILE_UPLOAD',
                    'video_size' => $size,
                    'chunk_size' => $chunkSize,
                    'total_chunk_count' => $chunkCount,
                ],
            ]);

        $this->throwOnError($response, 'upload initialization');

        $publishId = $response->json('data.publish_id');
        $uploadUrl = $response->json('data.upload_url');

        if (! is_string($publishId) || $publishId === '' || ! is_string($uploadUrl) || $uploadUrl === '') {
            throw new RuntimeException('TikTok upload initialization failed: the API response contained no publish_id/upload_url.');
        }

        return [$publishId, $uploadUrl];
    }

    private function uploadChunks(string $uploadUrl, string $path, int $size, int $chunkSize, string $mime): void
    {
        $handle = fopen($path, 'rb');

        if ($handle === false) {
            throw new RuntimeException("TikTok upload failed: could not open video file {$path}.");
        }

        try {
            $offset = 0;

            while ($offset < $size) {
                $length = min($chunkSize, $size - $offset);

                if ($length < 1) {
                    break;
                }

                $contents = fread($handle, $length);

                if ($contents === false || $contents === '') {
                    throw new RuntimeException("TikTok upload failed: could not read video chunk at offset {$offset}.");
                }

                $end = $offset + strlen($contents) - 1;

                $response = Http::withHeaders([
                    'Content-Type' => $mime,
                    'Content-Length' => (string) strlen($contents),
                    'Content-Range' => "bytes {$offset}-{$end}/{$size}",
                ])->withBody($contents, $mime)->put($uploadUrl);

                if ($response->failed()) {
                    throw new RuntimeException(
                        "TikTok chunk upload failed at offset {$offset}: HTTP {$response->status()}"
                    );
                }

                $offset = $end + 1;
            }
        } finally {
            fclose($handle);
        }
    }

    private function assertNotFailed(string $token, string $publishId): void
    {
        $response = Http::withToken($token)
            ->post(self::API_BASE.'/post/publish/status/fetch/', [
                'publish_id' => $publishId,
            ]);

        if ($response->failed()) {
            // The upload itself was accepted; a failed status probe must not
            // turn an accepted upload into a reported failure.
            return;
        }

        if ($response->json('data.status') === 'FAILED') {
            $reason = $response->json('data.fail_reason');
            $reason = is_string($reason) && $reason !== '' ? $reason : 'unknown reason';

            throw new RuntimeException("TikTok processing failed for publish {$publishId}: {$reason}");
        }
    }

    /**
     * TikTok access tokens live ~24h. When the stored token has expired and
     * a refresh token + client secret are available, exchange it and persist
     * the new tokens (encrypted) on the account.
     */
    private function refreshTokenIfExpired(PlatformAccount $account): void
    {
        if ($account->token_expires_at === null || $account->token_expires_at->isFuture()) {
            return;
        }

        $clientKey = config('poster.tiktok.client_key');
        $clientSecret = config('poster.tiktok.client_secret');

        if (blank($account->refresh_token) || blank($clientKey) || blank($clientSecret)) {
            throw new RuntimeException(
                'TikTok access token has expired and cannot be refreshed (missing refresh token or client secret). Reconnect the account via /poster/connect/tiktok.'
            );
        }

        $response = Http::asForm()->post(self::API_BASE.'/oauth/token/', [
            'client_key' => $clientKey,
            'client_secret' => $clientSecret,
            'grant_type' => 'refresh_token',
            'refresh_token' => $account->refresh_token,
        ]);

        $accessToken = $response->json('access_token') ?? $response->json('data.access_token');

        if ($response->failed() || ! is_string($accessToken) || $accessToken === '') {
            $message = $response->json('error_description') ?? $response->json('error.message');
            $message = is_string($message) && $message !== '' ? $message : "HTTP {$response->status()}";

            throw new RuntimeException("TikTok access token has expired and refresh failed: {$message}");
        }

        $refreshToken = $response->json('refresh_token') ?? $response->json('data.refresh_token');
        $expiresIn = $response->json('expires_in') ?? $response->json('data.expires_in');

        $account->update([
            'access_token' => $accessToken,
            'refresh_token' => is_string($refreshToken) && $refreshToken !== '' ? $refreshToken : $account->refresh_token,
            'token_expires_at' => is_numeric($expiresIn) ? now()->addSeconds((int) $expiresIn) : $account->token_expires_at,
        ]);
    }

    private function throwOnError(Response $response, string $action): void
    {
        $code = $response->json('error.code');

        if ($response->successful() && ($code === null || $code === 'ok')) {
            return;
        }

        $message = $response->json('error.message');
        $message = is_string($message) && $message !== '' ? $message : "HTTP {$response->status()}";

        throw new RuntimeException("TikTok {$action} failed: {$message}");
    }
}
