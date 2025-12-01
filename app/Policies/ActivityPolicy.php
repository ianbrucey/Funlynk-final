<?php

namespace App\Policies;

use App\Models\Activity;
use App\Models\Group;
use App\Models\User;

class ActivityPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(?User $user): bool
    {
        // Anyone can view activities (including guests)
        return true;
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(?User $user, Activity $activity): bool
    {
        if ($activity->group_id) {
            // If it's a group activity, apply GroupPolicy view logic
            return app(GroupPolicy::class)->view($user, $activity->group);
        }
        // Anyone can view public activities
        if ($activity->is_public) {
            return true;
        }

        // Only host can view private activities
        return $user && $activity->host_id === $user->id;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(?User $user): bool
    {
        return $user !== null;
    }

    /**
     * Determine whether the user can create events within a group.
     */
    public function createInGroup(User $user, Group $group): bool
    {
        return $group->members()->where('user_id', $user->id)->exists();
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Activity $activity): bool
    {
        if ($activity->group_id) {
            // If it's a group activity, check if user is creator or group admin
            return $user->id === $activity->user_id || app(GroupPolicy::class)->update($user, $activity->group);
        }
        // Only the host can update their activity
        if ($activity->host_id === $user->id) {
            return true;
        }

        // TODO: Add admin role check when role system is implemented

        return false;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Activity $activity): bool
    {
        if ($activity->group_id) {
            // If it's a group activity, check if user is creator or group admin
            return $user->id === $activity->user_id || app(GroupPolicy::class)->delete($user, $activity->group);
        }
        // Only host can delete
        if ($activity->host_id !== $user->id) {
            return false;
        }

        // Cannot delete if activity has attendees
        if ($activity->current_attendees > 0) {
            return false;
        }

        // Cannot delete if activity is completed
        if ($activity->status === 'completed') {
            return false;
        }

        return true;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Activity $activity): bool
    {
        // Only host can restore
        return $activity->host_id === $user->id;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Activity $activity): bool
    {
        // Only admins can force delete
        // TODO: Add admin role check when role system is implemented
        return false;
    }

    /**
     * Determine whether the user can cancel the activity.
     */
    public function cancel(User $user, Activity $activity): bool
    {
        // Only host can cancel
        if ($activity->host_id !== $user->id) {
            return false;
        }

        // Cannot cancel if already completed or cancelled
        if (in_array($activity->status, ['completed', 'cancelled'])) {
            return false;
        }

        return true;
    }

    /**
     * Determine whether the user can publish the activity.
     */
    public function publish(User $user, Activity $activity): bool
    {
        // Only host can publish
        if ($activity->host_id !== $user->id) {
            return false;
        }

        // Can only publish drafts
        return $activity->status === 'draft';
    }
}
