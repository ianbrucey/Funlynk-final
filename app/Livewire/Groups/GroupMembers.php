<?php

namespace App\Livewire\Groups;

use App\Models\Group;
use App\Models\GroupMember;
use App\Models\User;
use App\Services\GroupService;
use Livewire\Component;
use Livewire\WithPagination;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

class GroupMembers extends Component
{
    use WithPagination, AuthorizesRequests;

    public $group;
    public $search = '';
    protected $queryString = ['search'];

    protected $listeners = ['memberRemoved' => '$refresh', 'roleChanged' => '$refresh'];

    public function mount(Group $group)
    {
        $this->group = $group;
    }

    public function render()
    {
        try {
            $members = GroupMember::where('group_id', $this->group->id)
                ->when($this->search, function ($query) {
                    $query->whereHas('user', function ($subQuery) {
                        $subQuery->where('name', 'like', '%' . $this->search . '%');
                    });
                })
                ->with('user')
                ->paginate(20);
        } catch (\Exception $e) {
            session()->flash('error', 'Failed to load group members: ' . $e->getMessage());
            $members = collect(); // Return an empty collection on error
        }

        return view('livewire.groups.group-members', [
            'members' => $members,
        ])->layout('layouts.app');
    }

    public function removeMember($memberId)
    {
        $groupMember = GroupMember::findOrFail($memberId);
        $this->authorize('manageMembers', $this->group);

        try {
            app(GroupService::class)->removeMember($this->group, $groupMember->user);
            session()->flash('success', 'Member removed successfully.');
        } catch (\Exception $e) {
            session()->flash('error', 'Failed to remove member: ' . $e->getMessage());
        }
    }

    public function changeRole($memberId, $newRole)
    {
        $groupMember = GroupMember::findOrFail($memberId);
        $this->authorize('manageMembers', $this->group);

        try {
            app(GroupService::class)->updateMemberRole($this->group, $groupMember->user, $newRole);
            session()->flash('success', 'Member role updated successfully.');
        } catch (\Exception $e) {
            session()->flash('error', 'Failed to update member role: ' . $e->getMessage());
        }
    }

    public function inviteMembers()
    {
        $this->authorize('manageMembers', $this->group);
        try {
            // Placeholder for inviting members functionality
            session()->flash('info', 'Invite members functionality coming soon!');
        } catch (\Exception $e) {
            session()->flash('error', 'Failed to initiate invite process: ' . $e->getMessage());
        }
    }
}