<?php

namespace App\Policies;

use App\Models\Rsvp;
use App\Models\User;

class RsvpPolicy
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
    public function view(User $user, Rsvp $rsvp): bool
    {
        // User can view their own RSVP or if they are the activity host
        return $user->id === $rsvp->user_id || $user->id === $rsvp->activity->host_id;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        // Any authenticated user can create an RSVP
        return true;
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Rsvp $rsvp): bool
    {
        // User can update their own RSVP or if they are the activity host
        return $user->id === $rsvp->user_id || $user->id === $rsvp->activity->host_id;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Rsvp $rsvp): bool
    {
        // User can delete their own RSVP or if they are the activity host
        return $user->id === $rsvp->user_id || $user->id === $rsvp->activity->host_id;
    }

    /**
     * Determine whether the user can mark attendance.
     */
    public function markAttended(User $user, Rsvp $rsvp): bool
    {
        // Only the activity host can mark attendance
        return $user->id === $rsvp->activity->host_id;
    }
}
