<?php

namespace App\Policies;

use App\Models\Tag;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class TagPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(?User $user): bool
    {
        // Anyone can view tags (including guests)
        return true;
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(?User $user, Tag $tag): bool
    {
        // Anyone can view individual tags
        return true;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        // All authenticated users can create tags
        return true;
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Tag $tag): bool
    {
        // Only admins can update tags
        // TODO: Add admin role check when role system is implemented
        // For now, allow all authenticated users
        return true;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Tag $tag): bool
    {
        // Only admins can delete tags
        // Prevent deletion of tags that are in use
        if ($tag->usage_count > 0) {
            return false;
        }

        // TODO: Add admin role check when role system is implemented
        // For now, allow deletion of unused tags by any user
        return true;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Tag $tag): bool
    {
        // Only admins can restore tags
        // TODO: Add admin role check when role system is implemented
        return true;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Tag $tag): bool
    {
        // Only admins can force delete tags
        // TODO: Add admin role check when role system is implemented
        return false;
    }

    /**
     * Determine whether the user can feature/unfeature tags.
     */
    public function moderate(User $user): bool
    {
        // Only admins can moderate (feature/unfeature) tags
        // TODO: Add admin role check when role system is implemented
        return true;
    }

    /**
     * Determine whether the user can merge tags.
     */
    public function merge(User $user): bool
    {
        // Only admins can merge tags
        // TODO: Add admin role check when role system is implemented
        return true;
    }
}
