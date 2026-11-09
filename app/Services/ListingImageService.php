<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

/**
 * Spec 020: download one researched listing image and store it through
 * the hosted-activity image pipeline (ImageProcessor, directory
 * "activities", preset "cover", disk "s3"). The stored path is the only
 * thing a listing keeps in its `images` array; the source URL is kept
 * separately in `image_source_url` for credit and takedown. A source
 * URL is never stored in `images`: ImageProcessor::url() passes full
 * URLs through untouched, so that would silently hotlink.
 *
 * Every failure mode (non-https, unreachable, wrong content type, over
 * the size cap, undecodable bytes) throws ListingImageException with a
 * plain reason. Callers catch it and continue: an image failure never
 * fails a listing import.
 */
class ListingImageService
{
    public const ALLOWED_CONTENT_TYPES = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];

    public function __construct(private ImageProcessor $processor) {}

    /**
     * @throws \App\Services\ListingImageException
     */
    public function storeFromUrl(mixed $url): string
    {
        if (! is_string($url) || trim($url) === '') {
            throw new ListingImageException('invalid image URL');
        }

        $parts = parse_url($url);
        if ($parts === false || ($parts['scheme'] ?? null) !== 'https' || empty($parts['host'])) {
            throw new ListingImageException('image URL is not https');
        }

        try {
            $response = Http::timeout(10)->get($url);
        } catch (\Throwable $e) {
            throw new ListingImageException('download failed ('.$e->getMessage().')');
        }

        if (! $response->successful()) {
            throw new ListingImageException('download failed (HTTP '.$response->status().')');
        }

        $contentType = strtolower(trim(explode(';', (string) $response->header('Content-Type'))[0]));
        if (! in_array($contentType, self::ALLOWED_CONTENT_TYPES, true)) {
            throw new ListingImageException('content type '.($contentType !== '' ? $contentType : 'unknown'));
        }

        $bytes = $response->body();
        $max = (int) config('listings.image_max_bytes', 10 * 1024 * 1024);
        if (strlen($bytes) > $max) {
            throw new ListingImageException('image over the size cap');
        }

        try {
            return $this->processor->store($bytes, 'activities', 'cover', 's3');
        } catch (\Throwable) {
            throw new ListingImageException('image could not be decoded');
        }
    }
}
