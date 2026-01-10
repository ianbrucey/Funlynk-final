<?php

namespace App\Livewire\Tickets;

use App\Services\CheckInService;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

#[Layout('layouts.app')]
class MyTickets extends Component
{
    public string $activeTab = 'upcoming';

    public function switchTab(string $tab): void
    {
        $this->activeTab = $tab;
    }

    #[Computed]
    public function upcomingTickets(): Collection
    {
        return $this->getTickets('upcoming');
    }

    #[Computed]
    public function pastTickets(): Collection
    {
        return $this->getTickets('past');
    }

    protected function getTickets(string $type): Collection
    {
        $checkInService = app(CheckInService::class);
        $now = now();

        $rsvps = auth()->user()
            ->rsvps()
            ->where('status', 'attending')
            ->with(['activity' => function ($query) {
                $query->select('id', 'title', 'start_time', 'location_name', 'status', 'host_id');
            }])
            ->get();

        // Generate check-in credentials if missing
        foreach ($rsvps as $rsvp) {
            if (!$rsvp->qr_token) {
                $checkInService->generateCheckInCredentials($rsvp);
                $rsvp->refresh();
            }
        }

        $filtered = $type === 'upcoming'
            ? $rsvps->filter(fn($rsvp) => $rsvp->activity && $rsvp->activity->start_time >= $now)->sortBy('activity.start_time')
            : $rsvps->filter(fn($rsvp) => $rsvp->activity && $rsvp->activity->start_time < $now)->sortByDesc('activity.start_time');

        return $filtered->map(function ($rsvp) use ($checkInService) {
            $qrPayload = $checkInService->buildQrPayload($rsvp);
            return (object) [
                'rsvp' => $rsvp,
                'activity' => $rsvp->activity,
                'qr_code_svg' => QrCode::size(150)->generate(json_encode($qrPayload)),
            ];
        })->values();
    }

    public function getPaginatedTickets(): array
    {
        $allTickets = $this->activeTab === 'upcoming' ? $this->upcomingTickets : $this->pastTickets;
        $perPage = 5;
        $page = request()->get('page', 1);
        $offset = ($page - 1) * $perPage;

        $paginatedTickets = $allTickets->slice($offset, $perPage)->values();
        $totalPages = ceil($allTickets->count() / $perPage);

        return [
            'tickets' => $paginatedTickets,
            'currentPage' => $page,
            'totalPages' => $totalPages,
            'totalCount' => $allTickets->count(),
        ];
    }

    public function render()
    {
        return view('livewire.tickets.my-tickets');
    }
}

