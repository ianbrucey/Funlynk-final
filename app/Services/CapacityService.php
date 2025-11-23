<?php

namespace App\Services;

use App\Models\Activity;
use App\Models\Rsvp;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class CapacityService
{
    /**
     * Check if a user can RSVP to an activity.
     *
     * @param Activity $activity
     * @param User $user
     * @return array ['allowed' => bool, 'reason' => string|null, 'status' => string]
     */
    public function canRsvp(Activity $activity, User $user): array
    {
        // Check if user already RSVPed
        $existingRsvp = Rsvp::where('activity_id', $activity->id)
            ->where('user_id', $user->id)
            ->first();

        if ($existingRsvp) {
            return [
                'allowed' => false,
                'reason' => 'You have already RSVPed to this activity.',
                'status' => $existingRsvp->status,
            ];
        }

        // Check if activity is full
        if ($activity->max_attendees && $activity->current_attendees >= $activity->max_attendees) {
            return [
                'allowed' => true,
                'reason' => 'Activity is full. You will be added to the waitlist.',
                'status' => 'waitlist',
            ];
        }

        return [
            'allowed' => true,
            'reason' => null,
            'status' => 'attending',
        ];
    }

    /**
     * Reserve a spot for a user (create RSVP).
     *
     * @param Activity $activity
     * @param User $user
     * @param array $data Additional RSVP data (e.g., payment info)
     * @return Rsvp
     */
    public function reserve(Activity $activity, User $user, array $data = []): Rsvp
    {
        return DB::transaction(function () use ($activity, $user, $data) {
            // Lock activity row for update to prevent race conditions
            $activity = Activity::where('id', $activity->id)->lockForUpdate()->first();

            $canRsvp = $this->canRsvp($activity, $user);
            
            if (!$canRsvp['allowed']) {
                throw new \Exception($canRsvp['reason']);
            }

            $status = $canRsvp['status'];

            // Create RSVP
            $rsvp = Rsvp::create(array_merge([
                'activity_id' => $activity->id,
                'user_id' => $user->id,
                'status' => $status,
                'attended' => false,
            ], $data));

            // Update attendee count if attending
            if ($status === 'attending') {
                $activity->increment('current_attendees');
            }

            return $rsvp;
        });
    }

    /**
     * Cancel an RSVP and potentially promote from waitlist.
     *
     * @param Rsvp $rsvp
     * @return bool
     */
    public function cancelRsvp(Rsvp $rsvp): bool
    {
        return DB::transaction(function () use ($rsvp) {
            $activity = Activity::where('id', $rsvp->activity_id)->lockForUpdate()->first();

            if ($rsvp->status === 'attending') {
                $activity->decrement('current_attendees');
                $rsvp->update(['status' => 'declined']);
                
                // Try to promote from waitlist
                $this->promoteFromWaitlist($activity);
            } else {
                $rsvp->update(['status' => 'declined']);
            }

            return true;
        });
    }

    /**
     * Promote the next user from the waitlist.
     *
     * @param Activity $activity
     * @return Rsvp|null
     */
    public function promoteFromWaitlist(Activity $activity): ?Rsvp
    {
        // Check if there is space
        if ($activity->max_attendees && $activity->current_attendees >= $activity->max_attendees) {
            return null;
        }

        // Get next waitlisted user (FIFO)
        $nextRsvp = Rsvp::where('activity_id', $activity->id)
            ->where('status', 'waitlist')
            ->orderBy('created_at', 'asc')
            ->first();

        if ($nextRsvp) {
            $nextRsvp->update(['status' => 'attending']);
            $activity->increment('current_attendees');
            
            // TODO: Notify user they have been promoted
            
            return $nextRsvp;
        }

        return null;
    }
}
