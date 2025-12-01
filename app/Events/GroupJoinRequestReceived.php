<?php

namespace App\Events;

use App\Models\Group;
use App\Models\GroupJoinRequest;
use App\Models\User;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class GroupJoinRequestReceived
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public Group $group;

    public User $user;

    public GroupJoinRequest $joinRequest;

    /**
     * Create a new event instance.
     */
    public function __construct(Group $group, User $user, GroupJoinRequest $joinRequest)
    {
        $this->group = $group;
        $this->user = $user;
        $this->joinRequest = $joinRequest;
    }
}
