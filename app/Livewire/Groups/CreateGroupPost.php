<?php

namespace App\Livewire\Groups;

use Livewire\Component;
use App\Models\Group;
use App\Models\Tag;
use App\Services\GroupContentService;
use Illuminate\Validation\Rule;

class CreateGroupPost extends Component
{
    public Group $group;
    public string $title = '';
    public string $description = '';
    public array $selectedTags = [];
    public ?string $locationName = null;
    public $expiresAt = null;

    protected $listeners = ['openCreateGroupPostModal' => 'mount'];

    public function mount(Group $group)
    {
        $this->group = $group;
        $this->reset(['title', 'description', 'selectedTags', 'locationName', 'expiresAt']);
    }

    protected function rules()
    {
        return [
            'title' => ['required', 'string', 'max:100'],
            'description' => ['required', 'string', 'max:500'],
            'selectedTags' => ['nullable', 'array'],
            'locationName' => ['nullable', 'string', 'max:255'],
            'expiresAt' => ['required', 'date', 'after:now'],
        ];
    }

    public function createPost(GroupContentService $groupContentService)
    {
        $this->validate();

        $groupContentService->createGroupPost(
            $this->group,
            auth()->user(),
            [
                'title' => $this->title,
                'description' => $this->description,
                'location_name' => $this->locationName,
                'expires_at' => $this->expiresAt,
                'tags' => $this->selectedTags,
            ]
        );

        $this->dispatch('postCreated');
        $this->dispatch('closeModal', 'create-group-post-modal');
    }

    public function render()
    {
        $availableTags = Tag::all(); // Assuming a Tag model exists
        return view('livewire.groups.create-group-post', [
            'availableTags' => $availableTags,
        ]);
    }
}
