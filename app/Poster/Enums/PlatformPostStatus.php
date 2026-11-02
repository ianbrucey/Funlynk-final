<?php

namespace App\Poster\Enums;

enum PlatformPostStatus: string
{
    case Pending = 'pending';
    case Posting = 'posting';
    case Posted = 'posted';
    case Failed = 'failed';
    case Skipped = 'skipped';
}
