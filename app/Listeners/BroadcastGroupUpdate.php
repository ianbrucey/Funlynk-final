<?php

namespace App\Listeners;

use App\Events\GroupCreated;
use App\Events\GroupEventCreated;
use App\Events\GroupJoinRequestApproved;
use App\Events\GroupMemberJoined;
use App\Events\GroupMemberRemoved;
use App\Events\GroupPostCreated;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

class BroadcastGroupUpdate implements ShouldQueue
{
    use InteractsWithQueue;

    /**
     * Create the event listener.
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle GroupCreated event.
     */
    public function handleGroupCreated(GroupCreated $event): void
    {
        // No specific broadcast needed for group creation to all users, as it's usually followed by a redirect or initial load.
        // If needed, a public broadcast could be added here for discovery feeds.
        Log::info('GroupCreated event handled for broadcasting: '.$event->group->id);
    }

    /**
     * Handle GroupMemberJoined event.
     */
    public function handleGroupMemberJoined(GroupMemberJoined $event): void
    {
        // Broadcast to the group channel that a member has joined
        // The event itself should handle the broadcast via ShouldBroadcast interface if needed.
        Log::info('GroupMemberJoined event handled for broadcasting: '.$event->group->id.' user: '.$event->user->id);
    }

    /**
     * Handle GroupMemberRemoved event.
     */
    public function handleGroupMemberRemoved(GroupMemberRemoved $event): void
    {
        // Broadcast to the group channel that a member has been removed
        Log::info('GroupMemberRemoved event handled for broadcasting: '.$event->group->id.' user: '.$event->user->id);
    }

    /**
     * Handle GroupPostCreated event.
     */
    public function handleGroupPostCreated(GroupPostCreated $event): void
    {
        // The event itself should handle the broadcast via ShouldBroadcast interface if needed.
        Log::info('GroupPostCreated event handled for broadcasting: '.$event->group->id.' post: '.$event->post->id);
    }

    /**
     * Handle GroupEventCreated event.
     */
    public function handleGroupEventCreated(GroupEventCreated $event): void
    {
        // The event itself should handle the broadcast via ShouldBroadcast interface if needed.
        Log::info('GroupEventCreated event handled for broadcasting: '.$event->group->id.' activity: '.$event->activity->id);
    }

    /**
     * Handle GroupJoinRequestApproved event.
     */
    public function handleGroupJoinRequestApproved(GroupJoinRequestApproved $event): void
    {
        // Broadcast to the user whose request was approved
        Log::info('GroupJoinRequestApproved event handled for broadcasting: '.$event->group->id.' user: '.$event->user->id);
    }

    /**
     * Register the listeners for the subscriber.
     *
     * @param  \Illuminate\Events\Dispatcher  $events
     * @return void
     */
    public function subscribe($events)
    {
        $events->listen(
            GroupCreated::class,
            [BroadcastGroupUpdate::class, 'handleGroupCreated']
        );

        $events->listen(
            GroupMemberJoined::class,
            [BroadcastGroupUpdate::class, 'handleGroupMemberJoined']
        );

        $events->listen(
            GroupMemberRemoved::class,
            [BroadcastGroupUpdate::class, 'handleGroupMemberRemoved']
        );

        $events->listen(
            GroupPostCreated::class,
            [BroadcastGroupUpdate::class, 'handleGroupPostCreated']
        );

        $events->listen(
            GroupEventCreated::class,
            [BroadcastGroupUpdate::class, 'handleGroupEventCreated']
        );

        $events->listen(
            GroupJoinRequestApproved::class,
            [BroadcastGroupUpdate::class, 'handleGroupJoinRequestApproved']
        );
    }
}
