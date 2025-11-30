<?php

namespace App\Events;

use App\Models\Activity;
use App\Models\Post;
use App\Models\PostConversion;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class PostConvertedToEvent
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * Create a new event instance.
     */
    public function __construct(
        public Post $post,
        public Activity $activity,
        public PostConversion $conversion
    ) {}
}
