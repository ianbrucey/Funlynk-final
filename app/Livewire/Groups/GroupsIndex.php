<?php

namespace App\Livewire\Groups;

use App\Models\Group;
use App\Models\Tag;
use App\Services\GroupService;
use Illuminate\Contracts\Pagination\Paginator;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Livewire\WithPagination;

class GroupsIndex extends Component
{
    use WithPagination;

    public string $search = '';

    public string $privacyFilter = 'public'; // 'public', 'private', 'all'

    public array $selectedTags = [];



    public array $tags = []; // All available tags

    public array $userGroups = []; // Groups the current user is a member of

    protected GroupService $groupService;

    protected $queryString = [
        'search' => ['except' => ''],
        'privacyFilter' => ['except' => 'public'],
        'selectedTags' => ['except' => []],
    ];

    public function boot(GroupService $groupService): void
    {
        $this->groupService = $groupService;
    }

    public function mount(): void
    {
        try {
            $this->tags = Tag::all()->map(fn ($tag) => ['id' => $tag->id, 'name' => $tag->name])->toArray();
        } catch (\Exception $e) {
            session()->flash('error', 'Failed to load tags: ' . $e->getMessage());
            $this->tags = [];
        }
        $this->loadUserGroups();
    }

    public function loadUserGroups(): void
    {
        if (Auth::check()) {
            try {
                $this->userGroups = Auth::user()->groups()->get()->toArray();
            } catch (\Exception $e) {
                session()->flash('error', 'Failed to load user groups: ' . $e->getMessage());
                $this->userGroups = [];
            }
        }
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedPrivacyFilter(): void
    {
        $this->resetPage();
    }

    public function toggleTag(string $tagId): void
    {
        if (in_array($tagId, $this->selectedTags)) {
            $this->selectedTags = array_diff($this->selectedTags, [$tagId]);
        } else {
            $this->selectedTags[] = $tagId;
        }
        $this->resetPage();
    }

    public function joinGroup(string $groupId): void
    {
        if (! Auth::check()) {
            return; // Or redirect to login
        }

        $group = Group::findOrFail($groupId);
        try {
            $this->groupService->addMember($group, Auth::user());
            session()->flash('success', 'Successfully joined the group!');
        } catch (\Exception $e) {
            session()->flash('error', 'Failed to join group: ' . $e->getMessage());
        }
        $this->loadUserGroups();
    }

    public function leaveGroup(string $groupId): void
    {
        if (! Auth::check()) {
            return; // Or redirect to login
        }

        $group = Group::findOrFail($groupId);
        try {
            $this->groupService->removeMember($group, Auth::user());
            session()->flash('success', 'Successfully left the group!');
        } catch (\Exception $e) {
            session()->flash('error', 'Failed to leave group: ' . $e->getMessage());
        }
        $this->loadUserGroups();
    }

    public function render()
    {
        $query = Group::query();

        // Apply search filter
        if ($this->search) {
            $query->where('name', 'ilike', '%'.$this->search.'%')
                ->orWhere('description', 'ilike', '%'.$this->search.'%');
        }

        // Apply privacy filter
        if ($this->privacyFilter !== 'all') {
            $query->where('privacy', $this->privacyFilter);
        }

        // Apply tag filter
        if (! empty($this->selectedTags)) {
            $query->whereHas('tags', function ($q) {
                $q->whereIn('tags.id', $this->selectedTags);
            });
        }

        $groups = $query->withCount('members')->paginate(12);

        return view('livewire.groups.groups-index', [
            'groups' => $groups,
        ])
            ->layout('layouts.app');
    }
}
