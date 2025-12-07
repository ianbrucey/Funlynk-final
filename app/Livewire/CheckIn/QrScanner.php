<?php

namespace App\Livewire\CheckIn;

use App\Models\Activity;
use App\Models\Rsvp;
use App\Services\CheckInService;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class QrScanner extends Component
{
    public Activity $activity;
    public ?Rsvp $lastCheckedIn = null;
    public ?string $errorMessage = null;
    public ?string $successMessage = null;
    public array $stats = [];

    public function mount(Activity $activity): void
    {
        abort_unless(auth()->id() === $activity->host_id, 403, 'Only the host can scan attendees.');

        $this->activity = $activity;
        $this->refreshStats();
    }

    public function processQrCode(string $payload): void
    {
        $this->reset(['errorMessage', 'successMessage', 'lastCheckedIn']);

        try {
            $data = json_decode($payload, true);

            if (!$data || !isset($data['token']) || !isset($data['activity_id'])) {
                $this->errorMessage = 'Invalid QR code format.';
                return;
            }

            if ($data['activity_id'] !== $this->activity->id) {
                $this->errorMessage = 'This ticket is for a different event.';
                return;
            }

            $checkInService = app(CheckInService::class);
            $rsvp = $checkInService->validateQrToken($this->activity->id, $data['token']);

            if (!$rsvp) {
                $this->errorMessage = 'Invalid QR code. Attendee not found.';
                return;
            }

            if ($rsvp->checked_in_at) {
                $this->errorMessage = $rsvp->user->name . ' is already checked in.';
                return;
            }

            $this->lastCheckedIn = $checkInService->performCheckIn($rsvp, 'qr_scan', auth()->user());
            $this->successMessage = $rsvp->user->name . ' checked in successfully!';
            $this->refreshStats();

            $this->dispatch('play-success-sound');
        } catch (\Exception $e) {
            $this->errorMessage = 'Error processing check-in: ' . $e->getMessage();
        }
    }

    public function refreshStats(): void
    {
        $this->stats = app(CheckInService::class)->getActivityCheckInStats($this->activity);
    }

    public function render()
    {
        return view('livewire.check-in.qr-scanner');
    }
}

