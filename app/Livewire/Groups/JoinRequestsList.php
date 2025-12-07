<?php

namespace App\Livewire\Groups;

use App\Models\Group;
use App\Models\GroupJoinRequest;
use App\Services\GroupService;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithPagination;

class JoinRequestsList extends Component
{
    use WithPagination;

    public Group $group;

    public string $search = '';

    public string $statusFilter = 'pending';

    public function mount(Group $group): void
    {
        // Check if user is admin
        if (! $group->memberships()->where('user_id', auth()->id())->where('role', 'admin')->exists()) {
            abort(403, 'Only group admins can manage join requests.');
        }

        $this->group = $group;
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedStatusFilter(): void
    {
        $this->resetPage();
    }

    public function approveRequest(string $requestId, GroupService $groupService): void
    {
        $request = GroupJoinRequest::findOrFail($requestId);

        if ($request->group_id !== $this->group->id) {
            session()->flash('error', 'Invalid request.');

            return;
        }

        $groupService->approveJoinRequest($request, auth()->user());
        session()->flash('success', 'Join request approved. User is now a member.');
    }

    public function denyRequest(string $requestId, GroupService $groupService): void
    {
        $request = GroupJoinRequest::findOrFail($requestId);

        if ($request->group_id !== $this->group->id) {
            session()->flash('error', 'Invalid request.');

            return;
        }

        $groupService->denyJoinRequest($request, auth()->user());
        session()->flash('success', 'Join request denied.');
    }

    #[On('joinRequestReceived')]
    public function refreshList(): void
    {
        // This will trigger a re-render
    }

    public function render()
    {
        $query = $this->group->joinRequests()
            ->with('user')
            ->when($this->statusFilter !== 'all', function ($q) {
                $q->where('status', $this->statusFilter);
            })
            ->when($this->search, function ($q) {
                $q->whereHas('user', function ($userQuery) {
                    $userQuery->where('name', 'like', '%' . $this->search . '%')
                        ->orWhere('username', 'like', '%' . $this->search . '%');
                });
            })
            ->orderBy('created_at', 'desc');

        return view('livewire.groups.join-requests-list', [
            'requests' => $query->paginate(10),
            'pendingCount' => $this->group->joinRequests()->where('status', 'pending')->count(),
        ]);
    }
}
