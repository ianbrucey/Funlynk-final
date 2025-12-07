<?php

namespace App\Livewire\Activities;

use App\Models\Activity;
use App\Models\Rsvp;
use App\Services\ActivityService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Component;

class ActivityDetail extends Component
{
    use AuthorizesRequests;

    public Activity $activity;
    public ?Rsvp $userRsvp = null;
    public $isHost = false;
    public $spotsRemaining = null;

    protected ActivityService $activityService;

    public function boot(ActivityService $activityService)
    {
        $this->activityService = $activityService;
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

    public function render()
    {
        return view('livewire.activities.activity-detail')
            ->layout('layouts.app');
    }
}
