<?php

namespace App\Services;

use App\Models\Activity;
use App\Models\EventInterest;
use App\Models\GuestBookmark;
use App\Models\User;
use Illuminate\Support\Str;

class GuestEngagementService
{
    /**
     * Record guest interest in an event
     *
     * @param  Activity  $activity
     * @param  string  $email
     * @param  array  $metadata  ['source', 'utm_campaign', 'utm_source', 'utm_medium']
     * @return EventInterest
     */
    public function recordInterest(Activity $activity, string $email, array $metadata = []): EventInterest
    {
        return EventInterest::updateOrCreate(
            [
                'activity_id' => $activity->id,
                'email' => $email,
            ],
            [
                'source' => $metadata['source'] ?? null,
                'utm_campaign' => $metadata['utm_campaign'] ?? null,
                'utm_source' => $metadata['utm_source'] ?? null,
                'utm_medium' => $metadata['utm_medium'] ?? null,
            ]
        );
    }

    /**
     * Create or retrieve guest bookmark
     *
     * @param  Activity  $activity
     * @param  string|null  $guestToken
     * @param  string|null  $source
     * @return array  ['bookmark' => GuestBookmark, 'token' => string]
     */
    public function createBookmark(Activity $activity, ?string $guestToken = null, ?string $source = null): array
    {
        if (! $guestToken) {
            $guestToken = $this->generateGuestToken();
        }

        $bookmark = GuestBookmark::firstOrCreate(
            [
                'activity_id' => $activity->id,
                'guest_token' => $guestToken,
            ],
            [
                'source' => $source,
            ]
        );

        return [
            'bookmark' => $bookmark,
            'token' => $guestToken,
        ];
    }

    /**
     * Get all bookmarks for a guest token
     *
     * @param  string  $guestToken
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getGuestBookmarks(string $guestToken)
    {
        return GuestBookmark::with('activity')
            ->where('guest_token', $guestToken)
            ->whereHas('activity', function ($query) {
                $query->where('start_time', '>', now());
            })
            ->latest()
            ->get();
    }

    /**
     * Migrate guest data to user account after signup
     *
     * @param  User  $user
     * @param  string  $email
     * @param  string|null  $guestToken
     * @return array  ['interests' => int, 'bookmarks' => int]
     */
    public function migrateGuestData(User $user, string $email, ?string $guestToken = null): array
    {
        $interestsUpdated = EventInterest::where('email', $email)
            ->whereNull('user_id')
            ->update(['user_id' => $user->id]);

        $bookmarksUpdated = 0;
        if ($guestToken) {
            $bookmarksUpdated = GuestBookmark::where('guest_token', $guestToken)
                ->whereNull('user_id')
                ->update(['user_id' => $user->id]);
        }

        return [
            'interests' => $interestsUpdated,
            'bookmarks' => $bookmarksUpdated,
        ];
    }

    /**
     * Mark interest as converted to RSVP
     *
     * @param  EventInterest  $interest
     * @param  string  $rsvpId
     * @return EventInterest
     */
    public function convertInterestToRsvp(EventInterest $interest, string $rsvpId): EventInterest
    {
        $interest->update([
            'converted_to_rsvp_at' => now(),
            'rsvp_id' => $rsvpId,
        ]);

        return $interest;
    }

    /**
     * Generate unique guest token for cookie
     *
     * @return string
     */
    protected function generateGuestToken(): string
    {
        return Str::random(64);
    }

    /**
     * Check if email has already expressed interest
     *
     * @param  Activity  $activity
     * @param  string  $email
     * @return bool
     */
    public function hasExpressedInterest(Activity $activity, string $email): bool
    {
        return EventInterest::where('activity_id', $activity->id)
            ->where('email', $email)
            ->exists();
    }
}

