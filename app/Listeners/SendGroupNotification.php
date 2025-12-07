<?php

namespace App\Listeners;

use App\Events\GroupCreated;
use App\Events\GroupEventCreated;
use App\Events\GroupJoinRequestApproved;
use App\Events\GroupJoinRequestReceived;
use App\Events\GroupMemberJoined;
use App\Events\GroupMemberRemoved;
use App\Events\GroupPostCreated;
use App\Models\User;
use App\Notifications\GroupNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Storage;

class SendGroupNotification implements ShouldQueue
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
     * Get the display name for a user (name, display_name, or username as fallback).
     */
    protected function getUserDisplayName(User $user): string
    {
        return $user->name ?: $user->display_name ?: $user->username ?: 'User';
    }

    /**
     * Get the full avatar URL for a user.
     */
    protected function getUserAvatarUrl(User $user): ?string
    {
        if (! $user->profile_image_url) {
            return null;
        }

        return Storage::url($user->profile_image_url);
    }

    /**
     * Handle GroupCreated event.
     */
    public function handleGroupCreated(GroupCreated $event): void
    {
        $group = $event->group;
        $creator = $group->creator;

        $creator->notify(new GroupNotification(
            'Group Created',
            'You created the group '.$group->name,
            'group-created',
            $group->id
        ));
    }

    /**
     * Handle GroupMemberJoined event.
     */
    public function handleGroupMemberJoined(GroupMemberJoined $event): void
    {
        $group = $event->group;
        $user = $event->user;

        // Notify the joined user
        $user->notify(new GroupNotification(
            'Joined Group',
            'You joined the group '.$group->name,
            'group-member-joined',
            $group->id
        ));

        // Notify group admins (excluding the joined user if they are an admin)
        $group->admins->each(function ($admin) use ($group, $user) {
            if ($admin->id !== $user->id) {
                $admin->notify(new GroupNotification(
                    'New Member',
                    $this->getUserDisplayName($user).' joined your group '.$group->name,
                    'group-member-joined',
                    $group->id,
                    null,
                    $user->id,
                    $this->getUserDisplayName($user),
                    $this->getUserAvatarUrl($user)
                ));
            }
        });
    }

    /**
     * Handle GroupMemberRemoved event.
     */
    public function handleGroupMemberRemoved(GroupMemberRemoved $event): void
    {
        $group = $event->group;
        $user = $event->user;

        // Notify the removed user
        $user->notify(new GroupNotification(
            'Removed from Group',
            'You were removed from the group '.$group->name,
            'group-member-removed',
            $group->id
        ));

        // Notify group admins
        $group->admins->each(function ($admin) use ($group, $user) {
            $admin->notify(new GroupNotification(
                'Member Removed',
                $this->getUserDisplayName($user).' was removed from your group '.$group->name,
                'group-member-removed',
                $group->id,
                null,
                $user->id,
                $this->getUserDisplayName($user),
                $this->getUserAvatarUrl($user)
            ));
        });
    }

    /**
     * Handle GroupPostCreated event.
     */
    public function handleGroupPostCreated(GroupPostCreated $event): void
    {
        $group = $event->group;
        $post = $event->post;
        $creator = $event->user;

        // Notify all group members except the creator
        // members() returns User models directly via BelongsToMany
        $group->members->each(function ($member) use ($group, $post, $creator) {
            if ($member->id !== $creator->id) {
                $member->notify(new GroupNotification(
                    'New Post',
                    $this->getUserDisplayName($creator).' created a new post in '.$group->name.': '.$post->title,
                    'group-post-created',
                    $group->id,
                    $post->id,
                    $creator->id,
                    $this->getUserDisplayName($creator),
                    $this->getUserAvatarUrl($creator)
                ));
            }
        });
    }

    /**
     * Handle GroupEventCreated event.
     */
    public function handleGroupEventCreated(GroupEventCreated $event): void
    {
        $group = $event->group;
        $activity = $event->activity;
        $creator = $event->user;

        // Notify all group members except the creator
        // members() returns User models directly via BelongsToMany
        $group->members->each(function ($member) use ($group, $activity, $creator) {
            if ($member->id !== $creator->id) {
                $member->notify(new GroupNotification(
                    'New Event',
                    $this->getUserDisplayName($creator).' created a new event in '.$group->name.': '.$activity->title,
                    'group-event-created',
                    $group->id,
                    $activity->id,
                    $creator->id,
                    $this->getUserDisplayName($creator),
                    $this->getUserAvatarUrl($creator)
                ));
            }
        });
    }

    /**
     * Handle GroupJoinRequestReceived event.
     */
    public function handleGroupJoinRequestReceived(GroupJoinRequestReceived $event): void
    {
        $group = $event->group;
        $user = $event->user;
        $joinRequest = $event->joinRequest;

        // Notify group admins
        $group->admins->each(function ($admin) use ($group, $user, $joinRequest) {
            $admin->notify(new GroupNotification(
                'Join Request',
                $this->getUserDisplayName($user).' requested to join your group '.$group->name,
                'group-join-request-received',
                $group->id,
                $joinRequest->id,
                $user->id,
                $this->getUserDisplayName($user),
                $this->getUserAvatarUrl($user)
            ));
        });
    }

    /**
     * Handle GroupJoinRequestApproved event.
     */
    public function handleGroupJoinRequestApproved(GroupJoinRequestApproved $event): void
    {
        $group = $event->group;
        $user = $event->user;
        $admin = $event->admin;

        // Notify the user whose request was approved
        $user->notify(new GroupNotification(
            'Request Approved',
            'Your request to join '.$group->name.' was approved by '.$this->getUserDisplayName($admin),
            'group-join-request-approved',
            $group->id,
            null,
            $admin->id,
            $this->getUserDisplayName($admin),
            $this->getUserAvatarUrl($admin)
        ));

        // Notify other group admins (excluding the approving admin)
        $group->admins->each(function ($groupAdmin) use ($group, $user, $admin) {
            if ($groupAdmin->id !== $admin->id) {
                $groupAdmin->notify(new GroupNotification(
                    'Request Approved',
                    $this->getUserDisplayName($user).'\'s join request for '.$group->name.' was approved by '.$this->getUserDisplayName($admin),
                    'group-join-request-approved',
                    $group->id,
                    null,
                    $user->id,
                    $this->getUserDisplayName($user),
                    $this->getUserAvatarUrl($user)
                ));
            }
        });
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
            [SendGroupNotification::class, 'handleGroupCreated']
        );

        $events->listen(
            GroupMemberJoined::class,
            [SendGroupNotification::class, 'handleGroupMemberJoined']
        );

        $events->listen(
            GroupMemberRemoved::class,
            [SendGroupNotification::class, 'handleGroupMemberRemoved']
        );

        $events->listen(
            GroupPostCreated::class,
            [SendGroupNotification::class, 'handleGroupPostCreated']
        );

        $events->listen(
            GroupEventCreated::class,
            [SendGroupNotification::class, 'handleGroupEventCreated']
        );

        $events->listen(
            GroupJoinRequestReceived::class,
            [SendGroupNotification::class, 'handleGroupJoinRequestReceived']
        );

        $events->listen(
            GroupJoinRequestApproved::class,
            [SendGroupNotification::class, 'handleGroupJoinRequestApproved']
        );
    }
}
