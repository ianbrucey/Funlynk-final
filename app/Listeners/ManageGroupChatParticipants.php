<?php

namespace App\Listeners;

use App\Events\GroupMemberJoined;
use App\Events\GroupMemberRemoved;
use App\Services\GroupChatService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class ManageGroupChatParticipants implements ShouldQueue
{
    use InteractsWithQueue;

    protected GroupChatService $groupChatService;

    /**
     * Create the event listener.
     */
    public function __construct(GroupChatService $groupChatService)
    {
        $this->groupChatService = $groupChatService;
    }

    /**
     * Handle the GroupMemberJoined event.
     * Add the user to the group chat when they join the group.
     */
    public function handleGroupMemberJoined(GroupMemberJoined $event): void
    {
        $this->groupChatService->addParticipantToGroupChat($event->group, $event->user);
    }

    /**
     * Handle the GroupMemberRemoved event.
     * Remove the user from the group chat when they leave the group.
     */
    public function handleGroupMemberRemoved(GroupMemberRemoved $event): void
    {
        $this->groupChatService->removeParticipantFromGroupChat($event->group, $event->user);
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
            [ManageGroupChatParticipants::class, 'handleGroupMemberJoined']
        );

        $events->listen(
            GroupMemberRemoved::class,
            [ManageGroupChatParticipants::class, 'handleGroupMemberRemoved']
        );
    }
}

