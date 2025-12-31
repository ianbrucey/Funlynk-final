<?php

namespace App\Exceptions\Video;

use Exception;

class VideoNotReadyException extends Exception
{
    public static function stillProcessing(string $status): self
    {
        return new self("Video is not ready for streaming. Current status: {$status}");
    }
}
