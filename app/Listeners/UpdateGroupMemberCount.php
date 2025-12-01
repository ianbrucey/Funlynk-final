<?php

namespace App\Listeners;

use App\Events\GroupMemberJoined;
use App\Events\GroupMemberRemoved;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class UpdateGroupMemberCount implements ShouldQueue
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
     * Handle the GroupMemberJoined event.
     */
    public function handleGroupMemberJoined(GroupMemberJoined $event): void
    {
        $event->group->increment('member_count');
    }

    /**
     * Handle the GroupMemberRemoved event.
     */
    public function handleGroupMemberRemoved(GroupMemberRemoved $event): void
    {
        $event->group->decrement('member_count');
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
            GroupMemberJoined::class,
            [UpdateGroupMemberCount::class, 'handleGroupMemberJoined']
        );

        $events->listen(
            GroupMemberRemoved::class,
            [UpdateGroupMemberCount::class, 'handleGroupMemberRemoved']
        );
    }
}
