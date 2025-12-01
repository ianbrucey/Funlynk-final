<?php

namespace App\Events;

use App\Models\Group;
use App\Models\Post;
use App\Models\User;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class GroupPostCreated
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public Group $group;

    public Post $post;

    public User $user;

    /**
     * Create a new event instance.
     */
    public function __construct(Group $group, Post $post, User $user)
    {
        $this->group = $group;
        $this->post = $post;
        $this->user = $user;
    }
}
