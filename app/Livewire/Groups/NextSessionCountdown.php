<?php

namespace App\Livewire\Groups;

use App\Models\Activity;
use App\Models\Group;
use Livewire\Component;

class NextSessionCountdown extends Component
{
    public Group $group;
    public ?Activity $nextSession = null;
    public bool $hasRsvped = false;
    public int $rsvpCount = 0;

    public function mount(Group $group)
    {
        $this->group = $group;
        $this->loadNextSession();
    }

    public function loadNextSession(): void
    {
        $this->nextSession = $this->group->activities()
            ->where('start_time', '>', now())
            ->where('status', '!=', 'cancelled')
            ->orderBy('start_time', 'asc')
            ->with(['host', 'rsvps'])
            ->first();

        if ($this->nextSession) {
            $this->rsvpCount = $this->nextSession->rsvps->where('status', 'going')->count();

            if (auth()->check()) {
                $this->hasRsvped = $this->nextSession->rsvps
                    ->where('user_id', auth()->id())
                    ->where('status', 'going')
                    ->isNotEmpty();
            }
        }
    }

    public function refreshSession(): void
    {
        $this->loadNextSession();
    }

    public function render()
    {
        return view('livewire.groups.next-session-countdown');
    }
}
