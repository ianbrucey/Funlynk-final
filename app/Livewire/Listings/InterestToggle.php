<?php

namespace App\Livewire\Listings;

use App\Models\Activity;
use App\Services\ListingInterestService;
use Illuminate\Auth\Access\AuthorizationException;
use Livewire\Component;

/**
 * Spec 019: the "I am interested" toggle on a listing card or detail
 * page. State arrives from the feed payload (no query on render); the
 * toggle itself goes through ListingInterestService, which enforces
 * the policy rules (published, current, listings enabled).
 */
class InterestToggle extends Component
{
    public string $activityId;

    public bool $interested = false;

    public int $count = 0;

    public function mount(string $activityId, bool $interested = false, int $count = 0): void
    {
        $this->activityId = $activityId;
        $this->interested = $interested;
        $this->count = $count;
    }

    public function toggle(ListingInterestService $service)
    {
        if (! auth()->check()) {
            return redirect()->route('login');
        }

        $activity = Activity::query()->findOrFail($this->activityId);

        try {
            $result = $service->toggle(auth()->user(), $activity);
            $this->interested = $result['interested'];
            $this->count = $result['count'];
        } catch (AuthorizationException) {
            // The listing stopped accepting interest (unpublished,
            // ended, or the feature was switched off). Resync the
            // button with the truth instead of erroring the page.
            $this->interested = $service->isInterested(auth()->user(), $activity);
            $this->count = $service->count($activity);
        }

        return null;
    }

    public function render()
    {
        return view('livewire.listings.interest-toggle');
    }
}
