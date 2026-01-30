<?php

namespace App\Livewire\Groups;

use App\Models\Group;
use App\Services\GroupService;
use Livewire\Attributes\Layout;
use Livewire\Attributes\On;
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

    // Content permissions
    public string $postPermission = 'everyone';

    public string $eventPermission = 'admins';

    // Dynamic tags (type-and-enter pattern)
    public array $tags = [];

    public string $newTag = '';

    public $avatarImage = null;

    public $coverImage = null;

    public bool $confirmingDeletion = false;

    public function mount(Group $group): void
    {
        // Use policy for authorization (cleaner and more consistent)
        $this->authorize('update', $group);

        // Load recurring schedules relationship for the settings page
        $this->group = $group->load('recurringSchedules');
        $this->name = $group->name;
        $this->description = $group->description ?? '';
        $this->privacy = $group->privacy;
        $this->postPermission = $group->post_permission ?? 'everyone';
        $this->eventPermission = $group->event_permission ?? 'admins';
        $this->tags = $group->tags->pluck('name')->toArray();
    }

    protected function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:100'],
            'description' => ['nullable', 'string', 'max:500'],
            'privacy' => ['required', 'in:public,private'],
            'postPermission' => ['required', 'in:everyone,admins'],
            'eventPermission' => ['required', 'in:everyone,admins'],
            'tags' => ['nullable', 'array', 'max:10'],
            'tags.*' => ['string', 'max:50'],
            'avatarImage' => ['nullable', 'image', 'max:2048'],
            'coverImage' => ['nullable', 'image', 'max:4096'],
        ];
    }

    public function addTag()
    {
        if (empty($this->newTag)) {
            return;
        }

        if (count($this->tags) >= 10) {
            $this->addError('tags', 'Maximum 10 tags allowed.');

            return;
        }

        $tag = trim($this->newTag);
        if (! in_array($tag, $this->tags)) {
            $this->tags[] = $tag;
        }

        $this->reset('newTag');
    }

    public function removeTag($index)
    {
        unset($this->tags[$index]);
        $this->tags = array_values($this->tags);
    }

    public function updateGroup(GroupService $groupService): void
    {
        $this->validate();

        $data = [
            'name' => $this->name,
            'description' => $this->description,
            'privacy' => $this->privacy,
            'post_permission' => $this->postPermission,
            'event_permission' => $this->eventPermission,
            'tags' => $this->tags,
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

    #[On('schedule-created')]
    public function refreshSchedules(): void
    {
        // Reload the group with fresh recurring schedules
        $this->group->load('recurringSchedules');
    }

    public function render()
    {
        return view('livewire.groups.group-settings');
    }
}
