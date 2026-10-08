<?php

namespace App\Services;

use App\Models\Activity;
use App\Models\ActivityEditLog;
use App\Models\ActivityRefundWindow;
use App\Models\Rsvp;
use App\Models\RsvpChangeResponse;
use App\Models\Transaction;
use Illuminate\Support\Facades\DB;

class RefundWindowService
{
    public function __construct(
        private PaymentService $paymentService,
    ) {}

    /**
     * Create a new refund window for significant changes
     */
    public function create(
        Activity $activity,
        ActivityEditLog $triggerLog,
        array $changesSummary
    ): ActivityRefundWindow {
        return DB::transaction(function () use ($activity, $triggerLog, $changesSummary) {
            // Cancel any existing active window (we're replacing it)
            $this->cancelActiveWindow($activity);

            // Create new window
            $window = ActivityRefundWindow::create([
                'activity_id' => $activity->id,
                'triggered_at' => now(),
                'expires_at' => now()->addHours(ActivityRefundWindow::WINDOW_HOURS),
                'trigger_edit_log_id' => $triggerLog->id,
                'changes_summary' => $changesSummary,
                'status' => ActivityRefundWindow::STATUS_ACTIVE,
            ]);

            // Create pending response records for all paid RSVPs
            $paidRsvps = $activity->rsvps()->where('is_paid', true)->get();

            foreach ($paidRsvps as $rsvp) {
                RsvpChangeResponse::create([
                    'rsvp_id' => $rsvp->id,
                    'refund_window_id' => $window->id,
                    'response' => null,
                    'notified_at' => now(),
                ]);
            }

            return $window;
        });
    }

    /**
     * Cancel existing active window (when new changes come in or changes are reverted)
     */
    public function cancelActiveWindow(Activity $activity): void
    {
        ActivityRefundWindow::where('activity_id', $activity->id)
            ->where('status', ActivityRefundWindow::STATUS_ACTIVE)
            ->update(['status' => ActivityRefundWindow::STATUS_CANCELLED]);
    }

    /**
     * Get active refund window for an activity
     */
    public function getActive(Activity $activity): ?ActivityRefundWindow
    {
        return ActivityRefundWindow::where('activity_id', $activity->id)
            ->active()
            ->first();
    }

    /**
     * Process attendee response (accept or refund)
     */
    public function processResponse(
        Rsvp $rsvp,
        ActivityRefundWindow $window,
        string $responseType
    ): RsvpChangeResponse {
        $response = RsvpChangeResponse::where('rsvp_id', $rsvp->id)
            ->where('refund_window_id', $window->id)
            ->firstOrFail();

        if (! $response->isPending()) {
            throw new \Exception('Response has already been recorded.');
        }

        if (! $window->isActive()) {
            throw new \Exception('Refund window has expired.');
        }

        if ($responseType === RsvpChangeResponse::RESPONSE_ACCEPTED) {
            $response->markAsAccepted();
        } elseif ($responseType === RsvpChangeResponse::RESPONSE_REFUNDED) {
            $this->processRefund($rsvp, $response);
        }

        return $response->fresh();
    }

    /**
     * Process a refund for an RSVP
     */
    protected function processRefund(Rsvp $rsvp, RsvpChangeResponse $response): void
    {
        DB::transaction(function () use ($rsvp, $response) {
            // Find the transaction for this RSVP
            $transaction = Transaction::where('rsvp_id', $rsvp->id)
                ->where('status', 'succeeded')
                ->first();

            if ($transaction) {
                // Process refund through Stripe
                $this->paymentService->processRefund($transaction);

                $response->markAsRefunded($transaction->id);
            } else {
                // No transaction found - just mark as refunded
                $response->markAsRefunded();
            }

            // Cancel the RSVP
            $rsvp->update([
                'status' => 'cancelled',
                'payment_status' => 'refunded',
            ]);
        });
    }

    /**
     * Expire windows that have passed their expiration time
     * Called by scheduled job
     */
    public function expireWindows(): int
    {
        $expiredCount = ActivityRefundWindow::where('status', ActivityRefundWindow::STATUS_ACTIVE)
            ->where('expires_at', '<=', now())
            ->update(['status' => ActivityRefundWindow::STATUS_EXPIRED]);

        return $expiredCount;
    }

    /**
     * Check if RSVP has pending response for any active window
     */
    public function hasPendingResponse(Rsvp $rsvp): bool
    {
        return $rsvp->hasPendingChangeResponse();
    }

    /**
     * Get pending response for RSVP's activity's active window
     */
    public function getPendingResponse(Rsvp $rsvp): ?RsvpChangeResponse
    {
        return $rsvp->pendingChangeResponse;
    }
}
