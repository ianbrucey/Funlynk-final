<?php

namespace App\Exceptions\Video;

use Exception;

class VideoNotFoundException extends Exception
{
    public static function withId(string $id): self
    {
        return new self("Video not found with ID: {$id}");
    }
}
