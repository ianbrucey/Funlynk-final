<?php

namespace App\Poster\Enums;

enum Platform: string
{
    case Facebook = 'facebook';
    case Instagram = 'instagram';
    case TikTok = 'tiktok';
    case X = 'x';
    case Threads = 'threads';
    case YouTube = 'youtube';

    public function label(): string
    {
        return match ($this) {
            self::Facebook => 'Facebook',
            self::Instagram => 'Instagram',
            self::TikTok => 'TikTok',
            self::X => 'X',
            self::Threads => 'Threads',
            self::YouTube => 'YouTube',
        };
    }
}
