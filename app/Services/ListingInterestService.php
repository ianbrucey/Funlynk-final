<?php

namespace App\Services;

use App\Models\Activity;
use App\Models\ActivityInterest;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Collection;

/**
 * Spec 019: "I am interested" on external listings.
 *
 * Interest is the only attendance action a listing accepts. The rules
 * live in ActivityPolicy::expressInterest; this service enforces them
 * and owns the toggle semantics (one row per user; toggling off
 * removes the row).
 */
class ListingInterestService
{
    /**
     * Toggle the user's interest in a listing.
     *
     * @return array{interested: bool, count: int}
     *
     * @throws AuthorizationException when the listing refuses interest
     */
    public function toggle(User $user, Activity $activity): array
    {
        if (! $user->can('expressInterest', $activity)) {
            throw new AuthorizationException('Interest is not available for this listing.');
        }

        $existing = ActivityInterest::query()
            ->where('activity_id', $activity->id)
            ->where('user_id', $user->id)
            ->first();

        if ($existing !== null) {
            $existing->delete();
            $interested = false;
        } else {
            ActivityInterest::query()->firstOrCreate([
                'activity_id' => $activity->id,
                'user_id' => $user->id,
            ]);
            $interested = true;
        }

        return [
            'interested' => $interested,
            'count' => ActivityInterest::query()->where('activity_id', $activity->id)->count(),
        ];
    }

    public function count(Activity $activity): int
    {
        return ActivityInterest::query()->where('activity_id', $activity->id)->count();
    }

    public function isInterested(User $user, Activity $activity): bool
    {
        return ActivityInterest::query()
            ->where('activity_id', $activity->id)
            ->where('user_id', $user->id)
            ->exists();
    }

    /**
     * The interested list as a given viewer may see it (C-15): each
     * interested user is visible only when their own profile privacy
     * allows this viewer to see their profile content. The viewer
     * always sees themselves.
     *
     * @return Collection<int, User>
     */
    public function visibleInterestedUsers(Activity $activity, ?User $viewer): Collection
    {
        return $activity->interestedUsers()
            ->orderByPivot('created_at')
            ->get()
            ->filter(fn (User $interested) => $interested->allowsProfileContentFor($viewer))
            ->values();
    }
}
