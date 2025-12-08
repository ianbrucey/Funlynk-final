<?php

namespace App\Livewire\Activities;

use App\Models\Activity;
use App\Models\ActivityRefundWindow;
use App\Models\Rsvp;
use App\Models\RsvpChangeResponse;
use App\Services\ActivityService;
use App\Services\RefundWindowService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Component;

class ActivityDetail extends Component
{
    use AuthorizesRequests;

    public Activity $activity;
    public ?Rsvp $userRsvp = null;
    public $isHost = false;
    public $spotsRemaining = null;

    // Refund window state
    public ?ActivityRefundWindow $activeRefundWindow = null;
    public ?RsvpChangeResponse $pendingChangeResponse = null;
    public bool $showRefundModal = false;

    protected ActivityService $activityService;
    protected RefundWindowService $refundWindowService;

    public function boot(ActivityService $activityService, RefundWindowService $refundWindowService)
    {
        $this->activityService = $activityService;
        $this->refundWindowService = $refundWindowService;
    }

    public function mount(Activity $activity)
    {
        $this->activity = $activity->load(['host', 'tags']);

        // Check authorization
        if (!$this->activity->is_public) {
            $this->authorize('view', $this->activity);
        }

        $this->isHost = auth()->id() === $this->activity->host_id;
        $this->spotsRemaining = $this->activityService->getAvailableSpots($this->activity);

        // Load user's RSVP if they have one
        if (auth()->check()) {
            $this->userRsvp = Rsvp::where('activity_id', $this->activity->id)
                ->where('user_id', auth()->id())
                ->first();

            // Check for active refund window and pending response
            $this->loadRefundWindowState();
        }
    }

    protected function loadRefundWindowState(): void
    {
        if (!$this->userRsvp || !$this->userRsvp->is_paid) {
            return;
        }

        $this->activeRefundWindow = $this->activity->activeRefundWindow;

        if ($this->activeRefundWindow) {
            $this->pendingChangeResponse = RsvpChangeResponse::where('rsvp_id', $this->userRsvp->id)
                ->where('refund_window_id', $this->activeRefundWindow->id)
                ->first();
        }
    }

    public function deleteActivity()
    {
        $this->authorize('delete', $this->activity);

        if ($this->activityService->canDelete($this->activity, auth()->user())) {
            $this->activity->delete();
            session()->flash('success', 'Activity deleted successfully.');
            return redirect()->route('activities.index'); // Assuming index route exists
        } else {
            session()->flash('error', 'Cannot delete activity. It may have attendees or be completed.');
        }
    }

    /**
     * Accept the changes and keep the RSVP
     */
    public function acceptChanges()
    {
        if (!$this->userRsvp || !$this->activeRefundWindow || !$this->pendingChangeResponse) {
            session()->flash('error', 'No pending changes to accept.');
            return;
        }

        try {
            $this->refundWindowService->processResponse(
                $this->userRsvp,
                $this->activeRefundWindow,
                RsvpChangeResponse::RESPONSE_ACCEPTED
            );

            session()->flash('success', 'You have accepted the changes. Your RSVP is confirmed.');
            $this->loadRefundWindowState();
        } catch (\Exception $e) {
            session()->flash('error', $e->getMessage());
        }
    }

    /**
     * Request a refund due to changes
     */
    public function requestRefund()
    {
        if (!$this->userRsvp || !$this->activeRefundWindow || !$this->pendingChangeResponse) {
            session()->flash('error', 'No pending changes to respond to.');
            return;
        }

        try {
            $this->refundWindowService->processResponse(
                $this->userRsvp,
                $this->activeRefundWindow,
                RsvpChangeResponse::RESPONSE_REFUNDED
            );

            session()->flash('success', 'Your refund has been processed. You will receive your money back within 5-10 business days.');
            $this->showRefundModal = false;
            $this->loadRefundWindowState();

            // Refresh the RSVP
            $this->userRsvp = $this->userRsvp->fresh();
        } catch (\Exception $e) {
            session()->flash('error', $e->getMessage());
        }
    }

    public function openRefundModal()
    {
        $this->showRefundModal = true;
    }

    public function closeRefundModal()
    {
        $this->showRefundModal = false;
    }

    public function render()
    {
        return view('livewire.activities.activity-detail')
            ->layout('layouts.app');
    }
}
