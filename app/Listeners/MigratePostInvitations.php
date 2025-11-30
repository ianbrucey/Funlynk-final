<?php

namespace App\Listeners;

use App\Events\PostConvertedToEvent;
use App\Events\PostInvitationMigrated;
use App\Models\Notification;

class MigratePostInvitations
{
    /**
     * Handle the event.
     */
    public function handle(PostConvertedToEvent $event): void
    {
        // Get all pending invitations for the post
        $invitations = $event->post->invitations()
            ->where('status', 'pending')
            ->get();

        $invitedCount = 0;

        foreach ($invitations as $invitation) {
            // Update invitation status to migrated
            $invitation->update(['status' => 'migrated']);

            // Create notification for invited user
            Notification::create([
                'user_id' => $invitation->invitee_id,
                'type' => 'post_invitation_converted',
                'data' => [
                    'post_id' => $event->post->id,
                    'post_title' => $event->post->title,
                    'activity_id' => $event->activity->id,
                    'activity_title' => $event->activity->title,
                    'inviter_name' => $invitation->inviter->display_name ?? $invitation->inviter->name,
                    'start_time' => $event->activity->start_time->toIso8601String(),
                    'location' => $event->activity->location_name,
                    'price' => $event->activity->price,
                    'is_free' => $event->activity->price == 0,
                ],
            ]);

            // Dispatch event for each migrated invitation
            event(new PostInvitationMigrated($invitation, $event->activity));

            $invitedCount++;
        }

        // Update conversion record with invited users count
        $event->conversion->update([
            'invited_users_notified' => $invitedCount,
        ]);
    }
}
