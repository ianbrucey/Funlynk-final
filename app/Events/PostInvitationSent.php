<?php

namespace App\Events;

use App\Models\Post;
use App\Models\PostInvitation;
use App\Models\User;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class PostInvitationSent implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * Create a new event instance.
     */
    public function __construct(
        public PostInvitation $invitation,
        public Post $post,
        public User $inviter,
        public User $invitee
    ) {}

    public function broadcastOn(): Channel
    {
        // Broadcast to the invitee's user channel
        return new Channel("user.{$this->invitee->id}");
    }

    public function broadcastAs(): string
    {
        return 'notification';
    }

    public function broadcastWith(): array
    {
        return [
            'id' => (string) \Illuminate\Support\Str::uuid(),
            'type' => 'post_invitation',
            'subtype' => 'invited',
            'timestamp' => now()->toIso8601String(),
            'data' => [
                'invitation_id' => $this->invitation->id,
                'post_id' => $this->post->id,
                'post_title' => $this->post->title,
                'inviter_id' => $this->inviter->id,
                'inviter_name' => $this->inviter->name,
                'inviter_avatar' => $this->inviter->avatar_url ?? null,
            ],
            'actions' => [
                ['label' => 'View Post', 'route' => "/posts/{$this->post->id}"],
            ],
        ];
    }
}
