<?php

namespace App\Poster\Services;

use App\Poster\Contracts\PlatformPoster;
use App\Poster\Enums\Platform;
use App\Poster\Services\Posters\FacebookPoster;
use App\Poster\Services\Posters\InstagramPoster;
use App\Poster\Services\Posters\ThreadsPoster;
use App\Poster\Services\Posters\TikTokPoster;
use App\Poster\Services\Posters\XPoster;
use App\Poster\Services\Posters\YouTubePoster;
use InvalidArgumentException;

/**
 * Platform -> adapter map. Tests (and Phase 2 wiring) can substitute
 * adapters via register().
 */
class PosterRegistry
{
    /** @var array<string, PlatformPoster> */
    private array $posters = [];

    public function __construct()
    {
        foreach ([new FacebookPoster, new InstagramPoster, new TikTokPoster, new XPoster, new ThreadsPoster, new YouTubePoster] as $poster) {
            $this->posters[$poster->platform()->value] = $poster;
        }
    }

    public function register(PlatformPoster $poster): void
    {
        $this->posters[$poster->platform()->value] = $poster;
    }

    public function for(Platform $platform): PlatformPoster
    {
        return $this->posters[$platform->value]
            ?? throw new InvalidArgumentException("No poster registered for {$platform->value}.");
    }

    /**
     * @return array<string, PlatformPoster>
     */
    public function all(): array
    {
        return $this->posters;
    }
}
