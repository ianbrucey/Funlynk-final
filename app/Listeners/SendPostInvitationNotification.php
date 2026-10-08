<?php

namespace App\Listeners;

use App\Events\PostInvitationSent;
use App\Models\Notification;

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
        $inviterName = $event->inviter->display_name ?? $event->inviter->username;

        Notification::create([
            'user_id' => $event->invitee->id,
            'type' => 'post_invitation',
            'title' => "{$inviterName} invited you to a post",
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
