<?php

namespace App\Livewire\Activities;

use App\Models\User;
use Illuminate\Support\Collection;
use Livewire\Component;

class InviteFriendsModal extends Component
{
    public ?string $activityId = null;

    public bool $show = false;

    public string $search = '';

    public array $selectedFriends = [];

    public Collection $friends;

    protected $listeners = ['openInviteModal'];

    public function mount(): void
    {
        $this->friends = collect();
    }

    public function openInviteModal(string $activityId): void
    {
        $this->activityId = $activityId;
        $this->show = true;
        $this->loadFriends();
    }

    public function updatedSearch(): void
    {
        $this->loadFriends();
    }

    public function loadFriends(): void
    {
        // Get users that the current user has a mutual follow relationship with
        $this->friends = auth()->user()
            ->mutuals()
            ->when($this->search, fn ($q) => $q->where(function ($query) {
                $query->where('display_name', 'ilike', "%{$this->search}%")
                    ->orWhere('username', 'ilike', "%{$this->search}%");
            }))
            ->limit(20)
            ->get();
    }

    public function toggleFriend(string $friendId): void
    {
        if (in_array($friendId, $this->selectedFriends)) {
            $this->selectedFriends = array_values(array_diff($this->selectedFriends, [$friendId]));
        } else {
            $this->selectedFriends[] = $friendId;
        }
    }

    public function inviteFriends(): void
    {
        if (empty($this->selectedFriends)) {
            session()->flash('error', 'Please select at least one friend to invite.');

            return;
        }

        try {
            \Log::info('Inviting friends to activity', [
                'activity_id' => $this->activityId,
                'friend_ids' => $this->selectedFriends,
            ]);

            $invitations = app(\App\Services\ActivityService::class)->inviteFriendsToActivity(
                $this->activityId,
                $this->selectedFriends,
                auth()->user()
            );

            \Log::info('Invitations sent successfully', ['count' => count($invitations)]);

            session()->flash('success', count($invitations).' friend(s) invited!');
            $this->reset(['show', 'selectedFriends', 'search', 'activityId']);
            $this->dispatch('invitations-sent');
        } catch (\Exception $e) {
            \Log::error('Failed to send invitations', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            session()->flash('error', 'Failed to send invitations: '.$e->getMessage());
            $this->show = false;
        }
    }

    public function closeModal(): void
    {
        $this->reset(['show', 'selectedFriends', 'search', 'activityId']);
    }

    public function render()
    {
        return view('livewire.activities.invite-friends-modal');
    }
}
