<?php

namespace App\Listeners;

use App\Events\ActivityInvitationSent;
use App\Models\Notification;

class SendActivityInvitationNotification
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
    public function handle(ActivityInvitationSent $event): void
    {
        $inviterName = $event->inviter->display_name ?? $event->inviter->username;

        Notification::create([
            'user_id' => $event->invitee->id,
            'type' => 'activity_invitation',
            'title' => "{$inviterName} invited you to an event",
            'message' => "Check out \"{$event->activity->title}\"",
            'data' => [
                'invitation_id' => $event->invitation->id,
                'activity_id' => $event->activity->id,
                'activity_title' => $event->activity->title,
                'inviter_id' => $event->inviter->id,
                'inviter_name' => $inviterName,
                'inviter_avatar' => $event->inviter->profile_image_url ?? null,
                'url' => route('activities.show', $event->activity->id),
            ],
            'delivery_method' => 'in_app',
            'delivery_status' => 'sent',
        ]);
    }
}
