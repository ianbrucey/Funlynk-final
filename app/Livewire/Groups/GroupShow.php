<?php

namespace App\Livewire\Groups;

use App\Models\Group;
use App\Services\GroupChatService;
use App\Services\GroupService;
use Livewire\Component;

class GroupShow extends Component
{
    public Group $group;

    public string $activeTab = 'timeline';

    public bool $isMember = false;

    public bool $isAdmin = false;

    public bool $hasPendingRequest = false;

    public $membersCount;

    public $admins;

    public $tags;

    public $conversationId = null;

    public int $pendingRequestsCount = 0;

    protected $listeners = ['groupUpdated' => '$refresh'];

    public function mount(Group $group): void
    {
        try {
            $this->group = $group->load(['creator', 'members', 'tags', 'posts', 'activities', 'conversation']);
            $this->isMember = $this->group->members->contains(auth()->user());
            $this->isAdmin = $this->group->creator->is(auth()->user()) || $this->group->memberships()->where('user_id', auth()->id())->where('role', 'admin')->exists();
            $this->membersCount = $this->group->members->count();
            $this->admins = $this->group->memberships()->where('role', 'admin')->with('user')->get()->pluck('user');
            $this->tags = $this->group->tags;

            // Check for pending join request
            if (auth()->check() && ! $this->isMember) {
                $this->hasPendingRequest = $this->group->joinRequests()
                    ->where('user_id', auth()->id())
                    ->where('status', 'pending')
                    ->exists();
            }

            // Get pending requests count for admins
            if ($this->isAdmin) {
                $this->pendingRequestsCount = $this->group->joinRequests()
                    ->where('status', 'pending')
                    ->count();
            }

            // Get or create group conversation for chat tab
            if ($this->isMember && auth()->check()) {
                $groupChatService = app(GroupChatService::class);
                $conversation = $groupChatService->getOrCreateGroupChat($this->group);
                $this->conversationId = $conversation->id;
            }
        } catch (\Exception $e) {
            session()->flash('error', 'Failed to load group details: ' . $e->getMessage());
            $this->group = $group;
            $this->isMember = false;
            $this->isAdmin = false;
            $this->membersCount = 0;
            $this->admins = collect();
            $this->tags = collect();
            $this->conversationId = null;
            $this->hasPendingRequest = false;
            $this->pendingRequestsCount = 0;
        }
    }

    public function joinGroup(): void
    {
        if (! auth()->check()) {
            session()->flash('error', 'Please log in to join this group.');

            return;
        }

        if ($this->isMember) {
            session()->flash('error', 'You are already a member.');

            return;
        }

        $groupService = app(GroupService::class);

        // For private groups, create a join request instead
        if ($this->group->privacy === 'private') {
            try {
                $groupService->createJoinRequest($this->group, auth()->user());
                $this->hasPendingRequest = true;
                session()->flash('success', 'Join request sent! An admin will review your request.');
            } catch (\Exception $e) {
                session()->flash('error', 'Failed to send join request: ' . $e->getMessage());
            }

            return;
        }

        // For public groups, join directly
        try {
            $groupService->addMember($this->group, auth()->user());
            $this->isMember = true;
            $this->membersCount++;

            // Get or create group conversation for chat access
            $groupChatService = app(GroupChatService::class);
            $conversation = $groupChatService->getOrCreateGroupChat($this->group);
            $this->conversationId = $conversation->id;

            session()->flash('success', 'Successfully joined the group!');
        } catch (\Exception $e) {
            session()->flash('error', 'Failed to join group: ' . $e->getMessage());
        }
    }

    public function cancelJoinRequest(): void
    {
        if (! auth()->check() || ! $this->hasPendingRequest) {
            return;
        }

        $request = $this->group->joinRequests()
            ->where('user_id', auth()->id())
            ->where('status', 'pending')
            ->first();

        if ($request) {
            $request->delete();
            $this->hasPendingRequest = false;
            session()->flash('success', 'Join request cancelled.');
        }
    }

    public function leaveGroup(): void
    {
        if (auth()->check() && $this->isMember) {
            try {
                app(\App\Services\GroupService::class)->removeMember($this->group, auth()->user());
                $this->isMember = false;
                $this->membersCount--;
                $this->conversationId = null; // Clear chat access
                session()->flash('success', 'Successfully left the group!');
            } catch (\Exception $e) {
                session()->flash('error', 'Failed to leave group: ' . $e->getMessage());
            }
        } else {
            session()->flash('error', 'You are not a member or not logged in.');
        }
    }

    public function editGroup(): void
    {
        if ($this->isAdmin) {
            session()->flash('success', 'Redirecting to group edit page.');
            // Example: return redirect()->route('groups.edit', $this->group);
        } else {
            session()->flash('error', 'You do not have permission to edit this group.');
        }
    }

    public function deleteGroup(): void
    {
        if ($this->isAdmin) {
            try {
                app(\App\Services\GroupService::class)->deleteGroup($this->group);
                session()->flash('success', 'Group deleted successfully.');
                // Example: return redirect()->route('groups.index');
            } catch (\Exception $e) {
                session()->flash('error', 'Failed to delete group: ' . $e->getMessage());
            }
        } else {
            session()->flash('error', 'You do not have permission to delete this group.');
        }
    }

    public function switchTab(string $tab): void
    {
        $this->activeTab = $tab;
    }

    public function render()
    {
        return view('livewire.groups.group-show')
            ->layout('layouts.app');
    }
}
