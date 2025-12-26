<?php

namespace App\Livewire;

use App\Models\Follow;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class FollowButton extends Component
{
    public string $userId;
    public bool $isFollowing = false;

    public function mount(string $userId, bool $isFollowing = false): void
    {
        $this->userId = $userId;
        $this->isFollowing = $isFollowing;
    }

    public function toggle(): void
    {
        if (!Auth::check()) {
            $this->redirect(route('login'));
            return;
        }

        if (Auth::id() === $this->userId) {
            return;
        }

        if ($this->isFollowing) {
            $this->unfollow();
        } else {
            $this->follow();
        }
    }

    protected function follow(): void
    {
        Follow::firstOrCreate([
            'follower_id' => Auth::id(),
            'following_id' => $this->userId,
        ]);

        User::where('id', $this->userId)->increment('follower_count');
        Auth::user()->increment('following_count');

        $this->isFollowing = true;
        $this->dispatch('user-followed', userId: $this->userId);
    }

    protected function unfollow(): void
    {
        Follow::where('follower_id', Auth::id())
            ->where('following_id', $this->userId)
            ->delete();

        User::where('id', $this->userId)->decrement('follower_count');
        Auth::user()->decrement('following_count');

        $this->isFollowing = false;
        $this->dispatch('user-unfollowed', userId: $this->userId);
    }

    public function render()
    {
        return view('livewire.follow-button');
    }
}
