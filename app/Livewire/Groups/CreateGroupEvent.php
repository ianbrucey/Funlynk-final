<?php

namespace App\Livewire\Groups;

use App\Models\Group;
use App\Models\Tag;
use App\Services\GroupContentService;
use Livewire\Attributes\On;
use Livewire\Component;

class CreateGroupEvent extends Component
{
    public Group $group;

    public string $title = '';

    public string $description = '';

    public string $locationName = '';

    public $startTime = null;

    public $endTime = null;

    public ?int $maxAttendees = null;

    public array $selectedTags = [];

    public bool $showModal = false;

    public function mount(Group $group)
    {
        $this->group = $group;
    }

    #[On('openCreateGroupEventModal')]
    public function openModal(): void
    {
        $this->reset(['title', 'description', 'locationName', 'startTime', 'endTime', 'maxAttendees', 'selectedTags']);
        $this->showModal = true;
    }

    public function closeModal(): void
    {
        $this->showModal = false;
    }

    protected function rules()
    {
        return [
            'title' => ['required', 'string', 'max:100'],
            'description' => ['required', 'string', 'max:500'],
            'locationName' => ['required', 'string', 'max:255'],
            'startTime' => ['required', 'date', 'after:now'],
            'endTime' => ['required', 'date', 'after:startTime'],
            'maxAttendees' => ['nullable', 'integer', 'min:1'],
            'selectedTags' => ['nullable', 'array'],
        ];
    }

    public function createEvent(GroupContentService $groupContentService)
    {
        $this->validate();

        $groupContentService->createGroupEvent(
            $this->group,
            auth()->user(),
            [
                'title' => $this->title,
                'description' => $this->description,
                'location_name' => $this->locationName,
                'start_time' => $this->startTime,
                'end_time' => $this->endTime,
                'max_attendees' => $this->maxAttendees,
                'tags' => $this->selectedTags,
            ]
        );

        $this->showModal = false;
        $this->dispatch('eventCreated');
        session()->flash('success', 'Event created successfully!');
    }

    public function render()
    {
        $availableTags = Tag::all(); // Assuming a Tag model exists
        return view('livewire.groups.create-group-event', [
            'availableTags' => $availableTags,
        ]);
    }
}
