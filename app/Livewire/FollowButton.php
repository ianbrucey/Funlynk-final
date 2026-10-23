<?php

namespace App\Livewire;

use App\Livewire\Concerns\HandlesHostFollow;
use Livewire\Component;

class FollowButton extends Component
{
    use HandlesHostFollow;

    public string $userId;

    public bool $isFollowing = false;

    public string $name = '';

    public function mount(string $userId, bool $isFollowing = false, string $name = ''): void
    {
        $this->userId = $userId;
        $this->isFollowing = $isFollowing;
        $this->name = $name;
    }

    protected function afterHostFollowChange(string $hostId, bool $following): void
    {
        $this->isFollowing = $following;
        $this->dispatch($following ? 'user-followed' : 'user-unfollowed', userId: $hostId);
    }

    public function render()
    {
        return view('livewire.follow-button');
    }
}
