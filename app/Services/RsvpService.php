<?php

namespace App\Services;

use App\Models\Activity;
use App\Models\Rsvp;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class RsvpService
{
    public function __construct(
        protected CapacityService $capacityService
    ) {}

    /**
     * Create an RSVP for a user to an activity.
     *
     * @param Activity $activity
     * @param User $user
     * @param array $data
     * @return Rsvp
     */
    public function createRsvp(Activity $activity, User $user, array $data = []): Rsvp
    {
        // Validate activity is accepting RSVPs
        if (!in_array($activity->status, ['published', 'active'])) {
            throw new \Exception('This activity is not accepting RSVPs.');
        }

        // Use capacity service to reserve spot
        return $this->capacityService->reserve($activity, $user, $data);
    }

    /**
     * Update an existing RSVP.
     *
     * @param Rsvp $rsvp
     * @param array $data
     * @return Rsvp
     */
    public function updateRsvp(Rsvp $rsvp, array $data): Rsvp
    {
        return DB::transaction(function () use ($rsvp, $data) {
            $activity = Activity::where('id', $rsvp->activity_id)->lockForUpdate()->first();
            
            $oldStatus = $rsvp->status;
            $newStatus = $data['status'] ?? $oldStatus;

            // Handle status changes that affect capacity
            if ($oldStatus !== $newStatus) {
                if ($oldStatus === 'attending' && $newStatus !== 'attending') {
                    $activity->decrement('current_attendees');
                    $this->capacityService->promoteFromWaitlist($activity);
                } elseif ($oldStatus !== 'attending' && $newStatus === 'attending') {
                    if ($activity->max_attendees && $activity->current_attendees >= $activity->max_attendees) {
                        throw new \Exception('Activity is full. Cannot change status to attending.');
                    }
                    $activity->increment('current_attendees');
                }
            }

            $rsvp->update($data);
            return $rsvp->fresh();
        });
    }

    /**
     * Cancel an RSVP.
     *
     * @param Rsvp $rsvp
     * @return bool
     */
    public function cancelRsvp(Rsvp $rsvp): bool
    {
        return $this->capacityService->cancelRsvp($rsvp);
    }

    /**
     * Mark a user as attended.
     *
     * @param Rsvp $rsvp
     * @return Rsvp
     */
    public function markAttended(Rsvp $rsvp): Rsvp
    {
        $rsvp->update(['attended' => true]);
        return $rsvp;
    }

    /**
     * Get all RSVPs for an activity.
     *
     * @param Activity $activity
     * @param string|null $status
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getActivityRsvps(Activity $activity, ?string $status = null)
    {
        $query = Rsvp::where('activity_id', $activity->id)
            ->with('user');

        if ($status) {
            $query->where('status', $status);
        }

        return $query->orderBy('created_at', 'asc')->get();
    }

    /**
     * Get all RSVPs for a user.
     *
     * @param User $user
     * @param string|null $status
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getUserRsvps(User $user, ?string $status = null)
    {
        $query = Rsvp::where('user_id', $user->id)
            ->with('activity');

        if ($status) {
            $query->where('status', $status);
        }

        return $query->orderBy('created_at', 'desc')->get();
    }

    /**
     * Get waitlist count for an activity.
     *
     * @param Activity $activity
     * @return int
     */
    public function getWaitlistCount(Activity $activity): int
    {
        return Rsvp::where('activity_id', $activity->id)
            ->where('status', 'waitlist')
            ->count();
    }

    /**
     * Get attendance statistics for an activity.
     *
     * @param Activity $activity
     * @return array
     */
    public function getAttendanceStats(Activity $activity): array
    {
        $rsvps = Rsvp::where('activity_id', $activity->id)->get();

        return [
            'total_rsvps' => $rsvps->count(),
            'attending' => $rsvps->where('status', 'attending')->count(),
            'maybe' => $rsvps->where('status', 'maybe')->count(),
            'declined' => $rsvps->where('status', 'declined')->count(),
            'waitlist' => $rsvps->where('status', 'waitlist')->count(),
            'attended' => $rsvps->where('attended', true)->count(),
            'attendance_rate' => $rsvps->where('status', 'attending')->count() > 0
                ? round(($rsvps->where('attended', true)->count() / $rsvps->where('status', 'attending')->count()) * 100, 2)
                : 0,
        ];
    }
}
