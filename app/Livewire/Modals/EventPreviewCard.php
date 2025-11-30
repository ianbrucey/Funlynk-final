<?php

namespace App\Livewire\Modals;

use Carbon\Carbon;
use Livewire\Component;

class EventPreviewCard extends Component
{
    public string $title = '';

    public string $description = '';

    public string $location = '';

    public string $startTime = '';

    public string $endTime = '';

    public int $maxAttendees = 0;

    public float $price = 0;

    public int $interestedCount = 0;

    public array $tags = [];

    public ?string $imagePreview = null;

    public function render()
    {
        $startDate = $this->startTime ? Carbon::parse($this->startTime) : now();
        $endDate = $this->endTime ? Carbon::parse($this->endTime) : now()->addHours(2);

        return view('livewire.modals.event-preview-card', [
            'startDate' => $startDate,
            'endDate' => $endDate,
        ]);
    }
}
