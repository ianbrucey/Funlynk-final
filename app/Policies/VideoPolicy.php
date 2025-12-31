<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Video;
use Illuminate\Auth\Access\HandlesAuthorization;

class VideoPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any videos.
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can view the video.
     */
    public function view(?User $user, Video $video): bool
    {
        // Public videos are viewable by anyone
        if ($video->visibility === Video::VISIBILITY_PUBLIC) {
            return $video->moderation_status !== Video::MODERATION_REJECTED;
        }

        // Private videos only viewable by owner
        if ($video->visibility === Video::VISIBILITY_PRIVATE) {
            return $user && $user->id === $video->uploader_id;
        }

        // Followers-only videos
        if ($video->visibility === Video::VISIBILITY_FOLLOWERS_ONLY) {
            if (!$user) {
                return false;
            }

            // Owner can always view
            if ($user->id === $video->uploader_id) {
                return true;
            }

            // Check if user follows the uploader
            return $user->following()
                ->where('following_id', $video->uploader_id)
                ->exists();
        }

        return false;
    }

    /**
     * Determine whether the user can create videos.
     */
    public function create(User $user): bool
    {
        return true; // Any authenticated user can upload videos
    }

    /**
     * Determine whether the user can update the video.
     */
    public function update(User $user, Video $video): bool
    {
        return $user->id === $video->uploader_id;
    }

    /**
     * Determine whether the user can delete the video.
     */
    public function delete(User $user, Video $video): bool
    {
        // Owner can delete
        if ($user->id === $video->uploader_id) {
            return true;
        }

        // Admins can delete (if you have role system)
        // return $user->hasRole('admin');

        return false;
    }

    /**
     * Determine whether the user can restore the video.
     */
    public function restore(User $user, Video $video): bool
    {
        return $user->id === $video->uploader_id;
    }

    /**
     * Determine whether the user can permanently delete the video.
     */
    public function forceDelete(User $user, Video $video): bool
    {
        return false; // Only system/admin can force delete
    }

    /**
     * Determine whether the user can view video analytics.
     */
    public function viewAnalytics(User $user, Video $video): bool
    {
        return $user->id === $video->uploader_id;
    }
}
