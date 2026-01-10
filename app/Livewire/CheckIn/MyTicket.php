<?php

namespace App\Livewire\CheckIn;

use App\Models\Activity;
use App\Models\Rsvp;
use App\Services\CheckInService;
use Livewire\Attributes\Layout;
use Livewire\Component;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

#[Layout('layouts.app')]
class MyTicket extends Component
{
    public Activity $activity;
    public ?Rsvp $rsvp = null;
    public ?string $qrCodeSvg = null;

    public function mount(Activity $activity): void
    {
        $this->activity = $activity;
        $this->rsvp = auth()->user()->rsvps()->where('activity_id', $activity->id)->first();

        if (!$this->rsvp) {
            session()->flash('error', 'You do not have an RSVP for this activity.');
            $this->redirect(route('events.show', $activity));
            return;
        }

        // Generate check-in credentials if missing
        if (!$this->rsvp->qr_token) {
            app(CheckInService::class)->generateCheckInCredentials($this->rsvp);
            $this->rsvp->refresh();
        }

        $qrPayload = app(CheckInService::class)->buildQrPayload($this->rsvp);
        $this->qrCodeSvg = QrCode::size(200)->generate(json_encode($qrPayload));
    }

    public function render()
    {
        return view('livewire.check-in.my-ticket');
    }
}