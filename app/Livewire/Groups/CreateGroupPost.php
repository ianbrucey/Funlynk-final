<?php

namespace App\Livewire\Groups;

use App\Models\Group;
use App\Services\GroupContentService;
use Livewire\Attributes\On;
use Livewire\Component;

class CreateGroupPost extends Component
{
    public Group $group;

    public string $content = '';

    public ?string $locationName = null;

    public bool $showLocation = false;

    public string $expiresIn = '24'; // hours

    public bool $showModal = false;

    public function mount(Group $group)
    {
        $this->group = $group;
    }

    #[On('openCreateGroupPostModal')]
    public function openModal(): void
    {
        $this->reset(['content', 'locationName', 'showLocation', 'expiresIn']);
        $this->expiresIn = '24';
        $this->showModal = true;
    }

    public function closeModal(): void
    {
        $this->showModal = false;
    }

    public function toggleLocation(): void
    {
        $this->showLocation = !$this->showLocation;
        if (!$this->showLocation) {
            $this->locationName = null;
        }
    }

    protected function rules()
    {
        return [
            'content' => ['required', 'string', 'max:500'],
            'locationName' => ['nullable', 'string', 'max:255'],
            'expiresIn' => ['required', 'in:24,48,168'], // 24h, 48h, 1 week
        ];
    }

    public function createPost(GroupContentService $groupContentService)
    {
        // Check permission
        if (!$this->group->canCreatePost(auth()->user())) {
            session()->flash('error', 'You do not have permission to create posts in this group.');
            return;
        }

        $this->validate();

        // Calculate expiration time
        $expiresAt = now()->addHours((int) $this->expiresIn);

        $groupContentService->createGroupPost(
            $this->group,
            auth()->user(),
            [
                'title' => \Illuminate\Support\Str::limit($this->content, 50), // Auto-generate title from content
                'description' => $this->content,
                'location_name' => $this->locationName,
                'expires_at' => $expiresAt,
                'tags' => [],
            ]
        );

        $this->showModal = false;
        $this->dispatch('postCreated');
        session()->flash('success', 'Post created successfully!');
    }

    public function render()
    {
        return view('livewire.groups.create-group-post');
    }
}
