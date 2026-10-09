<?php

namespace App\Livewire\Groups;

use App\Models\Activity;
use App\Models\Group;
use App\Services\GroupService;
use Livewire\Component;

class PublicGroupLanding extends Component
{
    public Group $group;

    public ?Activity $nextActivity = null;

    public $admins;

    public $tags;

    public function mount(Group $group)
    {
        // Redirect authenticated members to full workspace
        if (auth()->check() && auth()->user()->isMemberOf($group)) {
            $this->redirect(route('groups.show', $group), navigate: true);

            return;
        }

        $this->group = $group->loadCount('members');

        $this->nextActivity = $group->activities()
            ->with(['rsvps.user', 'host'])
            ->where('start_time', '>', now())
            ->orderBy('start_time')
            ->first();

        $this->admins = $group->memberships()
            ->where('role', 'admin')
            ->with('user')
            ->get()
            ->pluck('user');

        $this->tags = $group->tags;
    }

    public function joinGroup(GroupService $groupService)
    {
        if (! auth()->check()) {
            // Open the auth modal for unauthenticated users
            $this->dispatch('openGroupAuthModal', groupId: $this->group->id);

            return;
        }

        try {
            if ($this->group->privacy === 'private') {
                $groupService->createJoinRequest($this->group, auth()->user());
                session()->flash('success', 'Join request sent!');
            } else {
                $groupService->addMember($this->group, auth()->user());
                session()->flash('success', 'Welcome to the group!');
            }

            return $this->redirect(route('groups.show', $this->group), navigate: true);
        } catch (\Exception $e) {
            session()->flash('error', $e->getMessage());
        }
    }

    public function render()
    {
        return view('livewire.groups.public-group-landing')
            ->layout('layouts.app');
    }
}
