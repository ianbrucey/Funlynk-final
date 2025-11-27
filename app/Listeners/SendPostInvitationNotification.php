<?php

namespace App\Listeners;

use App\Events\PostInvitationSent;
use App\Models\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class SendPostInvitationNotification
{
    /**
     * Create the event listener.
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     */
    public function handle(PostInvitationSent $event): void
    {
        Notification::create([
            'user_id' => $event->invitee->id,
            'type' => 'post_invitation',
            'title' => "{$event->inviter->name} invited you to a post",
            'message' => "Check out \"{$event->post->title}\"",
            'data' => [
                'invitation_id' => $event->invitation->id,
                'post_id' => $event->post->id,
                'inviter_id' => $event->inviter->id,
            ],
            'delivery_method' => 'in_app',
            'delivery_status' => 'sent',
        ]);
    }
}
