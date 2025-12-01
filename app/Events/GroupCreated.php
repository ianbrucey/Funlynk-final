<?php

namespace App\Events;

use App\Models\Group;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class GroupCreated
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public Group $group;

    /**
     * Create a new event instance.
     */
    public function __construct(Group $group)
    {
        $this->group = $group;
    }
}
