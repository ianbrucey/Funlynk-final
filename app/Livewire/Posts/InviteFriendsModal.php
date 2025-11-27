<?php

namespace App\Livewire\Posts;

use App\Models\User;
use Illuminate\Support\Collection;
use Livewire\Component;

class InviteFriendsModal extends Component
{
    public ?string $postId = null;

    public bool $show = false;

    public string $search = '';

    public array $selectedFriends = [];

    public Collection $friends;

    protected $listeners = ['openInviteModal'];

    public function mount(): void
    {
        $this->friends = collect();
    }

    public function openInviteModal(string $postId): void
    {
        $this->postId = $postId;
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
            $invitations = app(\App\Services\PostService::class)->inviteFriendsToPost(
                $this->postId,
                $this->selectedFriends,
                auth()->user()
            );

            session()->flash('success', count($invitations).' friend(s) invited!');
            $this->reset(['show', 'selectedFriends', 'search', 'postId']);
        } catch (\Exception $e) {
            session()->flash('error', 'Failed to send invitations: '.$e->getMessage());
        }
    }

    public function closeModal(): void
    {
        $this->reset(['show', 'selectedFriends', 'search', 'postId']);
    }

    public function render()
    {
        return view('livewire.posts.invite-friends-modal');
    }
}
