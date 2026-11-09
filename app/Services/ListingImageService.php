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
 * plain reason. Storage-layer failures from the processor surface
 * their underlying exception class and message instead of a generic
 * reason: mislabeling them once hid an S3 configuration error.
 * Callers catch it and continue: an image failure never
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
        } catch (\Intervention\Image\Exceptions\DecoderException) {
            // The bytes are not a decodable image. This is the only
            // failure that may call itself a decode failure.
            throw new ListingImageException('image could not be decoded');
        } catch (\Throwable $e) {
            // Anything else (a storage or S3 configuration error, an
            // encode failure) surfaces as itself, class and message.
            // Blanketing these as "could not be decoded" once hid a
            // missing S3 region behind six bogus decode failures in a
            // production backfill run (2026-11-09).
            throw new ListingImageException('image store failed ('.$e::class.': '.$e->getMessage().')');
        }
    }
}
