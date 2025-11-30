<?php

namespace App\Events;

use App\Models\Activity;
use App\Models\PostInvitation;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class PostInvitationMigrated
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * Create a new event instance.
     */
    public function __construct(
        public PostInvitation $invitation,
        public Activity $activity
    ) {}
}
