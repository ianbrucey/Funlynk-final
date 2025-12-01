<?php

namespace App\Events;

use App\Models\Group;
use App\Models\User;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class GroupJoinRequestApproved
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public Group $group;

    public User $user;

    public User $admin;

    /**
     * Create a new event instance.
     */
    public function __construct(Group $group, User $user, User $admin)
    {
        $this->group = $group;
        $this->user = $user;
        $this->admin = $admin;
    }
}
