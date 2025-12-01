<?php

namespace App\Policies;

use App\Models\Group;
use App\Models\Post;
use App\Models\User;

class PostPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(?User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(?User $user, Post $post): bool
    {
        if ($post->group_id) {
            // If it's a group post, apply GroupPolicy view logic
            return app(GroupPolicy::class)->view($user, $post->group);
        }

        // Default logic for non-group posts
        return true;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can create posts within a group.
     */
    public function createInGroup(User $user, Group $group): bool
    {
        return $group->members()->where('user_id', $user->id)->exists();
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Post $post): bool
    {
        if ($post->group_id) {
            // If it's a group post, check if user is creator or group admin
            return $user->id === $post->user_id || app(GroupPolicy::class)->update($user, $post->group);
        }

        return $user->id === $post->user_id;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Post $post): bool
    {
        if ($post->group_id) {
            // If it's a group post, check if user is creator or group admin
            return $user->id === $post->user_id || app(GroupPolicy::class)->delete($user, $post->group);
        }

        return $user->id === $post->user_id;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Post $post): bool
    {
        return $user->id === $post->user_id;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Post $post): bool
    {
        return $user->id === $post->user_id;
    }
}
