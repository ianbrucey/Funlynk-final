<?php

namespace App\Poster\Support;

final readonly class PublishResult
{
    public function __construct(
        public string $platformPostId,
        public ?string $platformPostUrl = null,
    ) {}
}
