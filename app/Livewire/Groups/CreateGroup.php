<?php

namespace App\Livewire\Groups;

use App\Models\Tag;
use App\Services\GroupService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Livewire\Component;

class CreateGroup extends Component
{
    public string $name = '';

    public string $description = '';

    public string $privacy = 'public'; // Default to public

    public array $availableTags = [];

    public array $selectedTags = [];

    public string $location = ''; // Placeholder for location, geocoding to be integrated later

    protected GroupService $groupService;

    public function boot(GroupService $groupService)
    {
        $this->groupService = $groupService;
    }

    public function mount()
    {
        $this->availableTags = Tag::all()->pluck('name', 'id')->toArray();
    }

    protected function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:100'],
            'description' => ['required', 'string', 'max:500'],
            'privacy' => ['required', 'string', Rule::in(['public', 'private'])],
            'selectedTags' => ['nullable', 'array'],
            'selectedTags.*' => ['exists:tags,id'],
            'location' => ['nullable', 'string', 'max:255'],
        ];
    }

    protected function messages(): array
    {
        return [
            'name.required' => 'A group name is required.',
            'name.max' => 'The group name cannot exceed 100 characters.',
            'description.required' => 'A description is required for the group.',
            'description.max' => 'The description cannot exceed 500 characters.',
            'privacy.required' => 'Group privacy setting is required.',
            'privacy.in' => 'Invalid privacy setting.',
            'selectedTags.*.exists' => 'One or more selected tags are invalid.',
        ];
    }

    public function updated($propertyName)
    {
        $this->validateOnly($propertyName);
    }

    public function createGroup()
    {
        $this->validate();

        try {
            $group = $this->groupService->createGroup(Auth::user(), [
                'name' => $this->name,
                'description' => $this->description,
                'privacy' => $this->privacy,
                'tags' => $this->selectedTags,
                // 'location' => $this->location, // Will be added when geocoding is integrated
            ]);

            session()->flash('message', 'Group created successfully!');

            return redirect()->to('/groups/'.$group->slug); // Redirect to group detail page
        } catch (\Exception $e) {
            session()->flash('error', 'Failed to create group: '.$e->getMessage());
        }
    }

    public function addTag($tagId)
    {
        if (! in_array($tagId, $this->selectedTags)) {
            $this->selectedTags[] = $tagId;
        }
    }

    public function removeTag($tagId)
    {
        $this->selectedTags = array_diff($this->selectedTags, [$tagId]);
    }

    public function render()
    {
        return view('livewire.groups.create-group')
            ->layout('layouts.app');
    }
}
