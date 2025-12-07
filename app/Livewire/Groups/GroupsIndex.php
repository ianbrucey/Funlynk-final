<?php

namespace App\Livewire\Groups;

use App\Models\Group;
use App\Models\Tag;
use App\Services\GroupService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

class GroupsIndex extends Component
{
    use WithPagination;

    #[Url(except: 'discover')]
    public string $tab = 'discover'; // 'my-groups' or 'discover'

    #[Url(except: '')]
    public string $search = '';

    #[Url(except: 'all')]
    public string $privacyFilter = 'all'; // 'public', 'private', 'all'

    #[Url(except: [])]
    public array $selectedTags = [];

    protected GroupService $groupService;

    public function boot(GroupService $groupService): void
    {
        $this->groupService = $groupService;
    }

    public function setTab(string $tab): void
    {
        $this->tab = $tab;
        $this->resetPage();
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
            $this->selectedTags = array_values(array_diff($this->selectedTags, [$tagId]));
        } else {
            $this->selectedTags[] = $tagId;
        }
        $this->resetPage();
    }

    /**
     * Get user's group IDs for efficient membership checking (O(1) lookup)
     */
    #[Computed]
    public function userGroupIds(): array
    {
        if (!Auth::check()) {
            return [];
        }

        return Auth::user()->groups()->pluck('groups.id')->toArray();
    }

    /**
     * Get count of user's groups for tab badge
     */
    #[Computed]
    public function myGroupsCount(): int
    {
        if (!Auth::check()) {
            return 0;
        }

        return Auth::user()->groups()->count();
    }

    /**
     * Get popular tags (top 20 by usage count)
     */
    #[Computed]
    public function popularTags(): Collection
    {
        return Tag::select('tags.id', 'tags.name')
            ->join('group_tag', 'tags.id', '=', 'group_tag.tag_id')
            ->groupBy('tags.id', 'tags.name')
            ->orderByRaw('COUNT(*) DESC')
            ->limit(20)
            ->get();
    }

    /**
     * Get paginated groups for My Groups tab
     */
    #[Computed]
    public function myGroups(): LengthAwarePaginator
    {
        if (!Auth::check()) {
            return new \Illuminate\Pagination\LengthAwarePaginator([], 0, 12);
        }

        $query = Auth::user()->groups()
            ->withCount('members')
            ->with('tags');

        // Apply search filter
        if ($this->search) {
            $query->where(function ($q) {
                $q->where('groups.name', 'ilike', '%' . $this->search . '%')
                    ->orWhere('groups.description', 'ilike', '%' . $this->search . '%');
            });
        }

        // Apply privacy filter
        if ($this->privacyFilter !== 'all') {
            $query->where('groups.privacy', $this->privacyFilter);
        }

        // Apply tag filter
        if (!empty($this->selectedTags)) {
            $query->whereHas('tags', function ($q) {
                $q->whereIn('tags.id', $this->selectedTags);
            });
        }

        return $query->orderBy('groups.name')->paginate(12);
    }

    /**
     * Get paginated groups for Discover tab (excludes user's groups)
     */
    #[Computed]
    public function discoverGroups(): LengthAwarePaginator
    {
        $query = Group::query()
            ->withCount('members')
            ->with('tags');

        // Exclude groups user is already a member of
        if (Auth::check() && !empty($this->userGroupIds)) {
            $query->whereNotIn('id', $this->userGroupIds);
        }

        // Apply search filter
        if ($this->search) {
            $query->where(function ($q) {
                $q->where('name', 'ilike', '%' . $this->search . '%')
                    ->orWhere('description', 'ilike', '%' . $this->search . '%');
            });
        }

        // Apply privacy filter
        if ($this->privacyFilter !== 'all') {
            $query->where('privacy', $this->privacyFilter);
        }

        // Apply tag filter
        if (!empty($this->selectedTags)) {
            $query->whereHas('tags', function ($q) {
                $q->whereIn('tags.id', $this->selectedTags);
            });
        }

        return $query->orderByDesc('members_count')->paginate(12);
    }

    public function joinGroup(string $groupId): void
    {
        if (!Auth::check()) {
            return;
        }

        $group = Group::findOrFail($groupId);

        // For private groups, create a join request
        if ($group->privacy === 'private') {
            try {
                $this->groupService->createJoinRequest($group, Auth::user());
                session()->flash('success', 'Join request sent! An admin will review your request.');
            } catch (\Exception $e) {
                session()->flash('error', 'Failed to send join request: ' . $e->getMessage());
            }

            return;
        }

        // For public groups, join directly
        try {
            $this->groupService->addMember($group, Auth::user());
            session()->flash('success', 'Successfully joined the group!');
            // Clear computed property cache
            unset($this->userGroupIds);
            unset($this->myGroupsCount);
        } catch (\Exception $e) {
            session()->flash('error', 'Failed to join group: ' . $e->getMessage());
        }
    }

    public function leaveGroup(string $groupId): void
    {
        if (!Auth::check()) {
            return;
        }

        $group = Group::findOrFail($groupId);
        try {
            $this->groupService->removeMember($group, Auth::user());
            session()->flash('success', 'Successfully left the group!');
            // Clear computed property cache
            unset($this->userGroupIds);
            unset($this->myGroupsCount);
        } catch (\Exception $e) {
            session()->flash('error', 'Failed to leave group: ' . $e->getMessage());
        }
    }

    public function render()
    {
        return view('livewire.groups.groups-index')
            ->layout('layouts.app');
    }
}
