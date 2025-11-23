<?php

namespace App\Livewire\Activities;

use App\Models\Activity;
use App\Models\Rsvp;
use App\Services\RsvpService;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class RsvpButton extends Component
{
    public Activity $activity;
    public ?Rsvp $userRsvp = null;
    public bool $loading = false;

    public function mount(Activity $activity)
    {
        $this->activity = $activity;
        $this->loadUserRsvp();
    }

    public function loadUserRsvp()
    {
        if (Auth::check()) {
            $this->userRsvp = Rsvp::where('activity_id', $this->activity->id)
                ->where('user_id', Auth::id())
                ->first();
        }
    }

    public function toggleRsvp()
    {
        if (!Auth::check()) {
            return redirect()->route('login');
        }

        // If activity is paid and user doesn't have RSVP, redirect to checkout
        if ($this->activity->is_paid && !$this->userRsvp) {
            return redirect()->route('activities.checkout', $this->activity);
        }

        $this->loading = true;

        try {
            $rsvpService = app(RsvpService::class);

            if ($this->userRsvp) {
                // Cancel existing RSVP
                $rsvpService->cancelRsvp($this->userRsvp);
                $this->userRsvp = null;
                session()->flash('success', 'RSVP cancelled successfully.');
            } else {
                // Create new RSVP (free activities only)
                $this->userRsvp = $rsvpService->createRsvp($this->activity, Auth::user());
                
                if ($this->userRsvp->status === 'waitlist') {
                    session()->flash('success', 'Added to waitlist. You will be notified if a spot opens up.');
                } else {
                    session()->flash('success', 'RSVP confirmed!');
                }
            }

            // Refresh activity to get updated counts
            $this->activity->refresh();
            
            // Dispatch event to update parent components
            $this->dispatch('rsvp-updated');
            
        } catch (\Exception $e) {
            session()->flash('error', $e->getMessage());
        } finally {
            $this->loading = false;
        }
    }

    public function render()
    {
        return view('livewire.activities.rsvp-button');
    }
}
