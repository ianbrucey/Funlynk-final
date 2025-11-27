<?php

namespace App\Policies;

use App\Models\Comment;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class CommentPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Comment $comment): bool
    {
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
     * Determine whether the user can update the model.
     * Users can only edit their own comments.
     */
    public function update(User $user, Comment $comment): bool
    {
        return $user->id === $comment->user_id;
    }

    /**
     * Determine whether the user can delete the model.
     * Users can delete their own comments.
     * Content owners (Post/Activity creators) can delete comments on their content.
     */
    public function delete(User $user, Comment $comment): bool
    {
        // User is comment author
        if ($user->id === $comment->user_id) {
            return true;
        }

        // User is the content owner (Post or Activity creator)
        $commentable = $comment->commentable;
        if ($commentable && isset($commentable->user_id) && $user->id === $commentable->user_id) {
            return true;
        }

        // Activity has host_id instead of user_id
        if ($commentable && isset($commentable->host_id) && $user->id === $commentable->host_id) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can restore the model.
     * Only comment authors can restore their soft-deleted comments.
     */
    public function restore(User $user, Comment $comment): bool
    {
        return $user->id === $comment->user_id;
    }

    /**
     * Determine whether the user can permanently delete the model.
     * Only comment authors can force delete.
     */
    public function forceDelete(User $user, Comment $comment): bool
    {
        return $user->id === $comment->user_id;
    }

    /**
     * Determine whether the user can moderate comments on their content.
     * Content owners (Post/Activity creators) can moderate.
     */
    public function moderate(User $user, Comment $comment): bool
    {
        $commentable = $comment->commentable;
        
        if (!$commentable) {
            return false;
        }

        // Check Post owner
        if (isset($commentable->user_id) && $user->id === $commentable->user_id) {
            return true;
        }

        // Check Activity host
        if (isset($commentable->host_id) && $user->id === $commentable->host_id) {
            return true;
        }

        return false;
    }
}
