<?php

namespace App\Events;

use App\Models\Activity;
use App\Models\ActivityInvitation;
use App\Models\User;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ActivityInvitationSent implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * Create a new event instance.
     */
    public function __construct(
        public ActivityInvitation $invitation,
        public Activity $activity,
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
        $inviterName = $this->inviter->display_name ?? $this->inviter->username;

        return [
            'id' => (string) \Illuminate\Support\Str::uuid(),
            'type' => 'activity_invitation',
            'subtype' => 'invited',
            'timestamp' => now()->toIso8601String(),
            'data' => [
                'invitation_id' => $this->invitation->id,
                'activity_id' => $this->activity->id,
                'activity_title' => $this->activity->title,
                'inviter_id' => $this->inviter->id,
                'inviter_name' => $inviterName,
                'inviter_avatar' => $this->inviter->profile_image_url ?? null,
            ],
            'actions' => [
                ['label' => 'View Event', 'route' => "/activities/{$this->activity->id}"],
            ],
        ];
    }
}
