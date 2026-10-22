<?php

namespace App\Livewire\Groups;

use App\Livewire\Concerns\ResolvesTypedLocation;
use App\Models\Group;
use App\Services\GroupContentService;
use App\Services\ImageProcessor;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\WithFileUploads;
use MatanYadaev\EloquentSpatial\Objects\Point;

class CreateGroupEvent extends Component
{
    use ResolvesTypedLocation;
    use WithFileUploads;

    public Group $group;

    public string $title = '';

    public string $description = '';

    public string $locationName = '';

    public ?float $latitude = null;

    public ?float $longitude = null;

    public $startDate = null;

    public $startTimeHour = '12';

    public $startTimeMinute = '00';

    public $startTimePeriod = 'PM';

    public string $duration = '60'; // minutes

    public ?int $maxAttendees = null;

    public ?TemporaryUploadedFile $coverImage = null;

    public bool $showModal = false;

    public function mount(Group $group)
    {
        $this->group = $group;
    }

    #[On('openCreateGroupEventModal')]
    public function openModal(): void
    {
        $this->reset(['title', 'description', 'locationName', 'latitude', 'longitude', 'startDate', 'startTimeHour', 'startTimeMinute', 'startTimePeriod', 'duration', 'maxAttendees', 'coverImage']);
        $this->startTimeHour = '12';
        $this->startTimeMinute = '00';
        $this->startTimePeriod = 'PM';
        $this->duration = '60';
        $this->showModal = true;
    }

    public function updatedCoverImage(): void
    {
        $this->validateOnly('coverImage');
    }

    public function setLocationData($name, $lat, $lng): void
    {
        $this->locationName = $name;
        $this->latitude = $lat ? (float) $lat : null;
        $this->longitude = $lng ? (float) $lng : null;

        // Log for debugging
        \Log::info('Group Event Location Data Set', [
            'name' => $this->locationName,
            'lat' => $this->latitude,
            'lng' => $this->longitude,
        ]);
    }

    public function closeModal(): void
    {
        $this->showModal = false;
    }

    protected function rules()
    {
        return [
            'title' => ['required', 'string', 'max:100'],
            'description' => ['nullable', 'string', 'max:500'],
            'locationName' => ['required', 'string', 'max:255'],
            'startDate' => ['required', 'date', 'after_or_equal:today'],
            'startTimeHour' => ['required', 'in:1,2,3,4,5,6,7,8,9,10,11,12'],
            'startTimeMinute' => ['required', 'in:00,15,30,45'],
            'startTimePeriod' => ['required', 'in:AM,PM'],
            'duration' => ['required', 'in:30,60,90,120,180,240'],
            'maxAttendees' => ['nullable', 'integer', 'min:1'],
            'coverImage' => ['nullable', 'image', 'max:2048'],
        ];
    }

    public function createEvent(GroupContentService $groupContentService)
    {
        // Check permission
        if (! $this->group->canCreateEvent(auth()->user())) {
            session()->flash('error', 'You do not have permission to create events in this group.');

            return;
        }

        $this->resolveTypedLocation();
        $this->validate();

        // Build start time from components
        $hour = (int) $this->startTimeHour;
        if ($this->startTimePeriod === 'PM' && $hour !== 12) {
            $hour += 12;
        } elseif ($this->startTimePeriod === 'AM' && $hour === 12) {
            $hour = 0;
        }

        $startTime = \Carbon\Carbon::parse($this->startDate)
            ->setHour($hour)
            ->setMinute((int) $this->startTimeMinute)
            ->setSecond(0);

        $endTime = $startTime->copy()->addMinutes((int) $this->duration);

        // Create location point if coordinates are provided
        $locationPoint = null;
        if ($this->latitude && $this->longitude) {
            $locationPoint = new Point((float) $this->latitude, (float) $this->longitude, 4326);
        }

        $coverImagePath = $this->coverImage
            ? app(ImageProcessor::class)->store($this->coverImage, 'activities', 'cover', 's3')
            : null;

        $groupContentService->createGroupEvent(
            $this->group,
            auth()->user(),
            [
                'title' => $this->title,
                'description' => $this->description,
                'location_name' => $this->locationName,
                'location_coordinates' => $locationPoint,
                'start_time' => $startTime,
                'end_time' => $endTime,
                'max_attendees' => $this->maxAttendees,
                'images' => $coverImagePath ? [$coverImagePath] : [],
                'tags' => [],
            ]
        );

        $this->showModal = false;
        $this->dispatch('eventCreated');
        $this->dispatch('close-create-group-event-modal');
        session()->flash('success', 'Event created successfully!');
    }

    public function render()
    {
        return view('livewire.groups.create-group-event');
    }
}
