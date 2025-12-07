<?php

namespace App\Policies;

use App\Models\Group;
use App\Models\User;

class GroupPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(?User $user): bool
    {
        return true; // Anyone can search for groups
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(?User $user, Group $group): bool
    {
        if ($group->privacy === 'public') {
            return true;
        }

        // Private group, only members can view
        return $user && $group->memberships()->where('user_id', $user->id)->exists();
    }

    /**
     * Determine whether the user can create models.
     * Returns false for guests (null user).
     */
    public function create(?User $user): bool
    {
        return $user !== null; // Only authenticated users can create groups
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Group $group): bool
    {
        return $group->memberships()->where('user_id', $user->id)->where('role', 'admin')->exists();
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Group $group): bool
    {
        return $group->memberships()->where('user_id', $user->id)->where('role', 'admin')->exists();
    }

    /**
     * Determine whether the user can join the group.
     */
    public function join(User $user, Group $group): bool
    {
        return ! $group->memberships()->where('user_id', $user->id)->exists();
    }

    /**
     * Determine whether the user can leave the group.
     */
    public function leave(User $user, Group $group): bool
    {
        $membership = $group->memberships()->where('user_id', $user->id)->first();

        if (! $membership) {
            return false; // Not a member
        }

        // Prevent last admin from leaving
        if ($membership->role === 'admin' && $group->memberships()->where('role', 'admin')->count() === 1) {
            return false;
        }

        return true;
    }

    /**
     * Determine whether the user can invite members to the group.
     */
    public function invite(User $user, Group $group): bool
    {
        return $group->memberships()->where('user_id', $user->id)->exists(); // Members can invite
    }

    /**
     * Determine whether the user can remove a member from the group.
     */
    public function removeMember(User $user, Group $group): bool
    {
        return $group->memberships()->where('user_id', $user->id)->where('role', 'admin')->exists();
    }

    /**
     * Determine whether the user can approve a join request.
     */
    public function approveRequest(User $user, Group $group): bool
    {
        return $group->memberships()->where('user_id', $user->id)->where('role', 'admin')->exists();
    }

    /**
     * Determine whether the user can manage group members (for Filament authorization).
     */
    public function manageMembers(User $user, Group $group): bool
    {
        return $group->memberships()->where('user_id', $user->id)->where('role', 'admin')->exists();
    }
}
