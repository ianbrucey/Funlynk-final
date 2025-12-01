<?php

namespace App\Events;

use App\Models\Activity;
use App\Models\Group;
use App\Models\User;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class GroupEventCreated
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public Group $group;

    public Activity $activity;

    public User $user;

    /**
     * Create a new event instance.
     */
    public function __construct(Group $group, Activity $activity, User $user)
    {
        $this->group = $group;
        $this->activity = $activity;
        $this->user = $user;
    }
}
