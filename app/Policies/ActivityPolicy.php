<?php

namespace App\Policies;

use App\Models\Activity;
use App\Models\Group;
use App\Models\User;

class ActivityPolicy
{
    /**
     * Spec 019: listings are curated by FunLynk. Approval, unpublishing
     * and editing are admin-only, using the app's email allowlist
     * pattern (same mechanism as the Horizon and Poster gates).
     */
    public function isListingsAdmin(?User $user): bool
    {
        if ($user === null) {
            return false;
        }

        $allowlist = array_map(
            fn (mixed $email): string => strtolower(trim((string) $email)),
            (array) config('listings.admin_emails', [])
        );

        return in_array(strtolower($user->email), $allowlist, true);
    }

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
        // Spec 019: unpublished listings, and all listings while the
        // listings feature is switched off, are admin-only.
        if ($activity->isListing()
            && ($activity->status !== 'published' || ! config('listings.enabled', true))) {
            return $this->isListingsAdmin($user);
        }

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
        // Spec 019: only an admin can edit a listing; nobody hosts one.
        if ($activity->isListing()) {
            return $this->isListingsAdmin($user);
        }

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

    /**
     * Who may add, edit, remove and assign items on an event's needs list.
     */
    public function manageNeeds(User $user, Activity $activity): bool
    {
        // Spec 019: listings have no needs list.
        if ($activity->isListing()) {
            return false;
        }

        return $this->update($user, $activity);
    }

    /**
     * Who may claim items: managers, and people going to the event.
     */
    public function claimNeeds(User $user, Activity $activity): bool
    {
        // Spec 019: listings have no needs list.
        if ($activity->isListing()) {
            return false;
        }

        return $this->update($user, $activity)
            || \App\Models\Rsvp::query()->where('activity_id', $activity->id)->where('user_id', $user->id)->where('status', 'attending')->exists();
    }

    // ---- Spec 019: external listings ----

    /**
     * Only an admin can approve a listing; approval records who and when.
     */
    public function approveListing(User $user, Activity $activity): bool
    {
        return $activity->isListing() && $this->isListingsAdmin($user);
    }

    /**
     * Only an admin can unpublish (take down) a listing.
     */
    public function unpublishListing(User $user, Activity $activity): bool
    {
        return $activity->isListing() && $this->isListingsAdmin($user);
    }

    /**
     * Hosted-only action: listings never accept RSVPs (C-11).
     */
    public function rsvp(User $user, Activity $activity): bool
    {
        return ! $activity->isListing();
    }

    /**
     * Hosted-only action: FunLynk never sells tickets for a listing (C-11).
     */
    public function checkout(User $user, Activity $activity): bool
    {
        return ! $activity->isListing();
    }

    /**
     * Chat gate. Hosted activities: unchanged from 019 (chat allowed).
     *
     * Spec 020: posting in a listing chat requires the user to be
     * signed in, the listing feature on, a published and current
     * listing, and a Going mark (an activity_interests row) on this
     * listing or on any row of its recurring series, so a regular
     * keeps access when the occurrence rolls over. Reading a listing
     * chat is governed by ConversationPolicy::view (sign-in plus the
     * listing view rule), not by this gate.
     */
    public function chat(User $user, Activity $activity): bool
    {
        if (! $activity->isListing()) {
            return true;
        }

        if (! config('listings.enabled', true)) {
            return false;
        }

        $series = $activity->recurring_schedule_id !== null
            ? Activity::query()->where('recurring_schedule_id', $activity->recurring_schedule_id)->get()
            : collect([$activity]);

        $publishedCurrent = $series->contains(
            fn (Activity $row) => $row->status === 'published' && $row->isCurrentListing()
        );

        if (! $publishedCurrent) {
            return false;
        }

        return \App\Models\ActivityInterest::query()
            ->where('user_id', $user->id)
            ->whereIn('activity_id', $series->pluck('id'))
            ->exists();
    }

    /**
     * The only attendance action a listing accepts: interest, and only
     * while the listing is published, switched on and current (C-14).
     */
    public function expressInterest(User $user, Activity $activity): bool
    {
        return $activity->isListing()
            && config('listings.enabled', true)
            && $activity->status === 'published'
            && $activity->isCurrentListing();
    }
}
