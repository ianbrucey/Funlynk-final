<?php

namespace App\Events;

use App\Models\Group;
use App\Models\User;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class GroupMemberJoined
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public Group $group;

    public User $user;

    /**
     * Create a new event instance.
     */
    public function __construct(Group $group, User $user)
    {
        $this->group = $group;
        $this->user = $user;
    }
}
