<?php

namespace App\Poster\Support;

use App\Poster\Models\PostBundle;

/**
 * What a platform can accept, per the verified API research
 * (~/workspace/research_notes/funlink-platform-posting-apis-20261101/report.md).
 * Adapters declare these; the posting pipeline checks bundles against them
 * before any publish attempt.
 */
final readonly class PlatformCapabilities
{
    public function __construct(
        public bool $supportsTextOnly,
        public bool $supportsPhotos,
        public bool $supportsVideo,
        public bool $supportsCarousel,
        public bool $requiresMedia,
        /** Media must be fetchable by the platform from a public URL (no binary upload). */
        public bool $mediaMustBePublicUrl,
        public ?int $maxTextLength = null,
        public ?int $maxPhotos = null,
        public ?string $notes = null,
    ) {}

    /**
     * Human-readable reasons this bundle cannot be posted to the platform.
     * Empty array = the bundle shape is acceptable.
     *
     * @return list<string>
     */
    public function violationsFor(PostBundle $bundle): array
    {
        $violations = [];

        if ($this->requiresMedia && ! $bundle->hasMedia()) {
            $violations[] = 'This platform requires a photo or video asset; text-only bundles cannot be posted.';
        }

        if (! $this->supportsPhotos && $bundle->hasImage() && ! $bundle->hasVideo()) {
            $violations[] = 'This platform does not accept photo posts.';
        }

        if (! $this->supportsVideo && $bundle->hasVideo()) {
            $violations[] = 'This platform does not accept video posts.';
        }

        if ($this->maxPhotos !== null && $bundle->imageCount() > $this->maxPhotos) {
            $violations[] = "This platform accepts at most {$this->maxPhotos} photos per post; the bundle has {$bundle->imageCount()}.";
        }

        if ($this->maxTextLength !== null && mb_strlen($bundle->caption) > $this->maxTextLength) {
            $violations[] = 'Caption is '.mb_strlen($bundle->caption)." characters; this platform allows at most {$this->maxTextLength} (the adapter's adapted copy is what gets checked at publish time).";
        }

        return $violations;
    }
}
