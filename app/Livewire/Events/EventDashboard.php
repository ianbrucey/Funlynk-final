<?php

namespace App\Livewire\Events;

use App\Models\Activity;
use App\Services\CheckInService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class EventDashboard extends Component
{
    public string $filter = 'upcoming';
    public string $search = '';

    protected $queryString = ['filter', 'search'];

    protected CheckInService $checkInService;

    public function boot(CheckInService $checkInService): void
    {
        $this->checkInService = $checkInService;
    }

    public function getEventsProperty(): Collection
    {
        $query = Activity::where('host_id', Auth::id())
            ->with(['rsvps', 'tags']);

        // Apply search filter
        if ($this->search) {
            $query->where(function ($q) {
                $q->where('title', 'ilike', '%' . $this->search . '%')
                  ->orWhere('description', 'ilike', '%' . $this->search . '%')
                  ->orWhere('location_name', 'ilike', '%' . $this->search . '%');
            });
        }

        // Apply status filter
        match ($this->filter) {
            'upcoming' => $query->where('start_time', '>', now())->orderBy('start_time', 'asc'),
            'past' => $query->where('start_time', '<=', now())->orderBy('start_time', 'desc'),
            'draft' => $query->where('status', 'draft')->orderBy('updated_at', 'desc'),
            default => $query->orderBy('start_time', 'desc'),
        };

        return $query->get();
    }

    public function getStatsProperty(): array
    {
        $userId = Auth::id();

        $upcoming = Activity::where('host_id', $userId)
            ->where('start_time', '>', now())
            ->count();

        $totalAttendees = Activity::where('host_id', $userId)
            ->withCount('rsvps')
            ->get()
            ->sum('rsvps_count');

        $drafts = Activity::where('host_id', $userId)
            ->where('status', 'draft')
            ->count();

        $past = Activity::where('host_id', $userId)
            ->where('start_time', '<=', now())
            ->count();

        return [
            'upcoming' => $upcoming,
            'total_attendees' => $totalAttendees,
            'drafts' => $drafts,
            'past' => $past,
        ];
    }

    public function getEventStats(Activity $activity): array
    {
        return $this->checkInService->getActivityCheckInStats($activity);
    }

    public function setFilter(string $filter): void
    {
        $this->filter = $filter;
    }

    public function render()
    {
        return view('livewire.events.event-dashboard', [
            'events' => $this->events,
            'stats' => $this->stats,
        ]);
    }
}
