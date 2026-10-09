<?php

namespace App\Livewire\CheckIn;

use App\Models\Activity;
use App\Models\Rsvp;
use App\Services\CheckInService;
use Exception;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Str;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class HostAttendeeManager extends Component
{
    public Activity $activity;

    public Collection $rsvps;

    public array $stats;

    public string $search = '';

    public string $manualCode = '';

    public ?string $errorMessage = null;

    public ?string $successMessage = null;

    protected CheckInService $checkInService;

    protected $queryString = ['search'];

    public function boot(CheckInService $checkInService)
    {
        $this->checkInService = $checkInService;
    }

    public function mount(Activity $activity)
    {
        $this->activity = $activity;
        abort_unless(auth()->id() === $this->activity->host_id, 403);

        $this->refreshList();
    }

    public function checkInByCode()
    {
        $this->resetMessages();
        $this->manualCode = trim($this->manualCode);

        if (empty($this->manualCode)) {
            $this->errorMessage = 'Check-in code cannot be empty.';

            return;
        }

        try {
            $rsvp = $this->checkInService->validateCheckInCode($this->activity->id, $this->manualCode);

            if (! $rsvp) {
                $this->errorMessage = 'Invalid check-in code.';

                return;
            }

            $this->checkInService->performCheckIn($rsvp, 'code', auth()->user());
            $this->successMessage = 'Attendee '.$rsvp->user->name.' checked in successfully!';
            $this->manualCode = '';
            $this->refreshList();
        } catch (Exception $e) {
            $this->errorMessage = $e->getMessage();
        }
    }

    public function manualCheckIn(string $rsvpId)
    {
        $this->resetMessages();

        try {
            $rsvp = Rsvp::findOrFail($rsvpId);
            abort_unless($rsvp->activity_id === $this->activity->id, 403);

            $this->checkInService->performCheckIn($rsvp, 'host_manual', auth()->user());
            $this->successMessage = 'Attendee '.$rsvp->user->name.' manually checked in successfully!';
            $this->refreshList();
        } catch (Exception $e) {
            $this->errorMessage = $e->getMessage();
        }
    }

    public function refreshList()
    {
        $this->rsvps = $this->activity->rsvps()->with('user')->get();
        $this->stats = $this->checkInService->getActivityCheckInStats($this->activity);
    }

    public function getFilteredRsvpsProperty(): Collection
    {
        if (empty($this->search)) {
            return $this->rsvps;
        }

        return $this->rsvps->filter(function ($rsvp) {
            return Str::contains(strtolower($rsvp->user->name), strtolower($this->search)) ||
                   Str::contains(strtolower($rsvp->user->email), strtolower($this->search));
        });
    }

    protected function resetMessages()
    {
        $this->errorMessage = null;
        $this->successMessage = null;
    }

    public function render()
    {
        return view('livewire.check-in.host-attendee-manager');
    }
}
