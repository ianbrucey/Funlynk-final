<?php

namespace App\Livewire\Groups;

use App\Models\Group;
use Livewire\Component;

class GroupShow extends Component
{
    public Group $group;

    public string $activeTab = 'timeline';

    public bool $isMember = false;

    public bool $isAdmin = false;

    public $membersCount;

    public $admins;

    public $tags;

    protected $listeners = ['groupUpdated' => '$refresh'];

    public function mount(Group $group): void
    {
        try {
            $this->group = $group->load(['creator', 'members', 'tags', 'posts', 'activities']);
            $this->isMember = $this->group->members->contains(auth()->user());
            $this->isAdmin = $this->group->creator->is(auth()->user()) || $this->group->members()->where('user_id', auth()->id())->where('role', 'admin')->exists();
            $this->membersCount = $this->group->members->count();
            $this->admins = $this->group->members()->where('role', 'admin')->get();
            $this->tags = $this->group->tags;
        } catch (\Exception $e) {
            session()->flash('error', 'Failed to load group details: ' . $e->getMessage());
            // Optionally, redirect or set default values
            $this->group = $group; // Keep the basic group object
            $this->isMember = false;
            $this->isAdmin = false;
            $this->membersCount = 0;
            $this->admins = collect();
            $this->tags = collect();
        }
    }

    public function joinGroup(): void
    {
        if (auth()->check() && ! $this->isMember) {
            try {
                app(\App\Services\GroupService::class)->addMember($this->group, auth()->user());
                $this->isMember = true;
                $this->membersCount++;
                session()->flash('success', 'Successfully joined the group!');
            } catch (\Exception $e) {
                session()->flash('error', 'Failed to join group: ' . $e->getMessage());
            }
        } else {
            session()->flash('error', 'You are already a member or not logged in.');
        }
    }

    public function leaveGroup(): void
    {
        if (auth()->check() && $this->isMember) {
            try {
                app(\App\Services\GroupService::class)->removeMember($this->group, auth()->user());
                $this->isMember = false;
                $this->membersCount--;
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
