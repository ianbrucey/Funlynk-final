<?php

namespace App\Services;

use App\Models\Activity;
use App\Models\Rsvp;
use App\Models\User;
use Exception;
use Illuminate\Support\Str;

class CheckInService
{
    /**
     * Allowed characters for check-in codes, excluding ambiguous ones.
     */
    private const CODE_CHARS = 'ABCDEFGHJKMNPQRTUVWXY2346789';

    /**
     * Generate unique check-in credentials (code and QR token) for an RSVP.
     *
     * @param  Rsvp  $rsvp  The RSVP to generate credentials for.
     *
     * @throws Exception If a unique code cannot be generated after multiple attempts.
     */
    public function generateCheckInCredentials(Rsvp $rsvp): void
    {
        $code = $this->generateUniqueCode($rsvp->activity_id);
        $qrToken = Str::uuid();

        $rsvp->update([
            'check_in_code' => $code,
            'qr_token' => $qrToken,
        ]);
    }

    /**
     * Validate a QR token against an activity and return the associated RSVP.
     *
     * @param  string  $activityId  The UUID of the activity.
     * @param  string  $qrToken  The QR token to validate.
     * @return Rsvp|null The matching RSVP model with user relationship loaded, or null if not found.
     */
    public function validateQrToken(string $activityId, string $qrToken): ?Rsvp
    {
        return Rsvp::where('activity_id', $activityId)
            ->where('qr_token', $qrToken)
            ->with('user')
            ->first();
    }

    /**
     * Validate a check-in code against an activity and return the associated RSVP.
     *
     * @param  string  $activityId  The UUID of the activity.
     * @param  string  $code  The check-in code to validate (case-insensitive).
     * @return Rsvp|null The matching RSVP model with user relationship loaded, or null if not found.
     */
    public function validateCheckInCode(string $activityId, string $code): ?Rsvp
    {
        return Rsvp::where('activity_id', $activityId)
            ->whereRaw('LOWER(check_in_code) = ?', [strtolower($code)])
            ->with('user')
            ->first();
    }

    /**
     * Perform the check-in for a given RSVP.
     *
     * @param  Rsvp  $rsvp  The RSVP to check in.
     * @param  string  $method  The method of check-in (e.g., 'qr', 'code', 'manual').
     * @param  User|null  $checkedInBy  The user who performed the check-in, if applicable.
     * @return Rsvp The updated RSVP model.
     *
     * @throws Exception If the RSVP is already checked in.
     */
    public function performCheckIn(Rsvp $rsvp, string $method, ?User $checkedInBy = null): Rsvp
    {
        // Refresh to get latest data and avoid race conditions
        $rsvp->refresh();

        if ($rsvp->checked_in_at !== null) {
            $userName = $rsvp->user->name ?? 'This attendee';
            throw new Exception($userName.' is already checked in.');
        }

        $rsvp->update([
            'checked_in_at' => now(),
            'check_in_method' => $method,
            'attended' => true,
            'checked_in_by' => $checkedInBy?->id,
        ]);

        return $rsvp->fresh();
    }

    /**
     * Get check-in statistics for a given activity.
     *
     * @param  Activity  $activity  The activity to get statistics for.
     * @return array An array containing total_rsvps, checked_in_count, pending_count, and check_in_percentage.
     */
    public function getActivityCheckInStats(Activity $activity): array
    {
        $totalRsvps = $activity->rsvps()->count();
        $checkedInCount = $activity->rsvps()->where('attended', true)->count();
        $pendingCount = $totalRsvps - $checkedInCount;
        $checkInPercentage = $totalRsvps > 0 ? ($checkedInCount / $totalRsvps) * 100 : 0;

        return [
            'total_rsvps' => $totalRsvps,
            'checked_in_count' => $checkedInCount,
            'pending_count' => $pendingCount,
            'check_in_percentage' => round($checkInPercentage, 2),
        ];
    }

    /**
     * Generate a unique 6-character alphanumeric code for an activity.
     *
     * @param  string  $activityId  The UUID of the activity to ensure code uniqueness within.
     * @return string The unique 6-character code.
     *
     * @throws Exception If a unique code cannot be generated after multiple attempts.
     */
    private function generateUniqueCode(string $activityId): string
    {
        $maxAttempts = 10;
        $chars = self::CODE_CHARS;
        $charsLength = strlen($chars);

        for ($i = 0; $i < $maxAttempts; $i++) {
            $code = '';
            for ($j = 0; $j < 6; $j++) {
                $code .= $chars[random_int(0, $charsLength - 1)];
            }

            $exists = Rsvp::where('activity_id', $activityId)
                ->where('check_in_code', $code)
                ->exists();

            if (! $exists) {
                return $code;
            }
        }

        throw new Exception('Unable to generate a unique check-in code for the activity after multiple attempts.');
    }

    /**
     * Build QR code data payload for encoding.
     *
     * @param  Rsvp  $rsvp  The RSVP to build payload for.
     * @return array The payload array with activity_id, rsvp_id, and token.
     */
    public function buildQrPayload(Rsvp $rsvp): array
    {
        return [
            'activity_id' => $rsvp->activity_id,
            'rsvp_id' => $rsvp->id,
            'token' => $rsvp->qr_token,
        ];
    }
}
