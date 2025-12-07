<?php

namespace App\Livewire\Groups;

use App\Models\Group;
use App\Models\Tag;
use App\Services\GroupService;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithFileUploads;

#[Layout('layouts.app')]
class GroupSettings extends Component
{
    use WithFileUploads;

    public Group $group;

    public string $name = '';

    public string $description = '';

    public string $privacy = 'public';

    public array $selectedTags = [];

    public $avatarImage = null;

    public $coverImage = null;

    public bool $confirmingDeletion = false;

    public function mount(Group $group): void
    {
        // Use policy for authorization (cleaner and more consistent)
        $this->authorize('update', $group);

        $this->group = $group;
        $this->name = $group->name;
        $this->description = $group->description ?? '';
        $this->privacy = $group->privacy;
        $this->selectedTags = $group->tags->pluck('id')->toArray();
    }

    protected function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:100'],
            'description' => ['nullable', 'string', 'max:500'],
            'privacy' => ['required', 'in:public,private'],
            'selectedTags' => ['nullable', 'array'],
            'avatarImage' => ['nullable', 'image', 'max:2048'],
            'coverImage' => ['nullable', 'image', 'max:4096'],
        ];
    }

    public function updateGroup(GroupService $groupService): void
    {
        $this->validate();

        $data = [
            'name' => $this->name,
            'description' => $this->description,
            'privacy' => $this->privacy,
            'tags' => $this->selectedTags,
        ];

        // Handle avatar upload
        if ($this->avatarImage) {
            $avatarPath = $this->avatarImage->store('groups/avatars', 'public');
            $data['avatar_url'] = '/storage/' . $avatarPath;
        }

        // Handle cover image upload
        if ($this->coverImage) {
            $coverPath = $this->coverImage->store('groups/covers', 'public');
            $data['cover_image_url'] = '/storage/' . $coverPath;
        }

        $groupService->updateGroup($this->group, $data);

        $this->group->refresh();
        session()->flash('success', 'Group settings updated successfully!');
    }

    public function confirmDeletion(): void
    {
        $this->confirmingDeletion = true;
    }

    public function cancelDeletion(): void
    {
        $this->confirmingDeletion = false;
    }

    public function deleteGroup(GroupService $groupService): void
    {
        $groupService->deleteGroup($this->group);
        session()->flash('success', 'Group deleted successfully.');

        $this->redirect(route('groups.index'));
    }

    public function render()
    {
        $availableTags = Tag::all();

        return view('livewire.groups.group-settings', [
            'availableTags' => $availableTags,
        ]);
    }
}
