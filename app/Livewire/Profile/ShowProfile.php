<?php

namespace App\Livewire\Profile;

use App\Models\Activity;
use App\Models\Follow;
use App\Models\Post;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Livewire\WithPagination;

class ShowProfile extends Component
{
    use WithPagination;

    public User $user;

    public string $activeTab = 'posts';

    public bool $isFollowing = false;

    public int $followersCount = 0;

    public int $followingCount = 0;

    public int $postsCount = 0;

    public int $hostedActivitiesCount = 0;

    public int $attendedActivitiesCount = 0;

    public int $interestedPostsCount = 0;

    public function mount($username = null)
    {
        // If no username provided, show current user's profile
        if ($username) {
            $this->user = User::where('username', $username)->firstOrFail();
        } else {
            $this->user = auth()->user();
        }

        $this->loadStats();
        $this->checkFollowStatus();
    }

    public function loadStats()
    {
        $this->followersCount = $this->user->followers()->count();
        $this->followingCount = $this->user->following()->count();
        $this->postsCount = $this->user->posts()->count();
        $this->hostedActivitiesCount = $this->user->activitiesHosted()->count();
        $this->attendedActivitiesCount = $this->user->rsvps()
            ->where('status', 'attending')
            ->count();

        // Count interested posts (posts with "I'm down" reactions)
        $this->interestedPostsCount = Post::whereHas('reactions', function ($q) {
            $q->where('user_id', $this->user->id)
                ->where('reaction_type', 'im_down');
        })->count();
    }

    public function checkFollowStatus()
    {
        if (Auth::check() && Auth::id() !== $this->user->id) {
            $this->isFollowing = Follow::where('follower_id', Auth::id())
                ->where('following_id', $this->user->id)
                ->exists();
        }
    }

    public function follow()
    {
        if (! Auth::check()) {
            return redirect()->route('login');
        }

        if (Auth::id() === $this->user->id) {
            return;
        }

        if (! $this->isFollowing) {
            $follow = Follow::firstOrCreate([
                'follower_id' => Auth::id(),
                'following_id' => $this->user->id,
            ]);

            if ($follow->wasRecentlyCreated) {
                $this->user->increment('follower_count');
                Auth::user()->increment('following_count');
                $this->followersCount++;
                $this->dispatch('user-followed', userId: $this->user->id);
            }

            $this->isFollowing = true;
        }
    }

    public function unfollow()
    {
        if (! Auth::check() || Auth::id() === $this->user->id) {
            return;
        }

        if ($this->isFollowing) {
            $deleted = Follow::where('follower_id', Auth::id())
                ->where('following_id', $this->user->id)
                ->delete();

            if ($deleted > 0) {
                $this->user->decrement('follower_count');
                Auth::user()->decrement('following_count');
                $this->followersCount--;
                $this->dispatch('user-unfollowed', userId: $this->user->id);
            }

            $this->isFollowing = false;
        }
    }

    public function startConversation()
    {
        if (! Auth::check()) {
            return redirect()->route('login');
        }

        if (Auth::id() === $this->user->id) {
            return;
        }

        try {
            $chatService = app(\App\Services\ChatService::class);
            $conversation = $chatService->createDirectMessageConversation(Auth::user(), $this->user);

            // Redirect to the conversation
            return redirect()->route('messages.show', ['conversation' => $conversation->id]);
        } catch (\Exception $e) {
            session()->flash('error', 'Unable to start conversation: '.$e->getMessage());

            return redirect()->route('messages.index');
        }
    }

    public function switchTab($tab)
    {
        if (! in_array($tab, ['posts', 'hosted', 'attending', 'interested'], true)) {
            return;
        }

        $this->activeTab = $tab;
        $this->resetPage();
    }

    public function render()
    {
        $data = [];

        switch ($this->activeTab) {
            case 'posts':
                $data['posts'] = $this->user->posts()
                    ->latest()
                    ->paginate(10);
                break;
            case 'hosted':
                $data['activities'] = $this->user->activitiesHosted()
                    ->latest()
                    ->paginate(10);
                break;
            case 'attending':
                $data['activities'] = Activity::whereHas('rsvps', function ($query) {
                    $query->where('user_id', $this->user->id)
                        ->where('status', 'attending');
                })
                    ->latest()
                    ->paginate(10);
                break;
            case 'interested':
                // Interested tab is handled by the InterestedTab component
                $data['showInterestedTab'] = true;
                break;
        }

        return view('livewire.profile.show-profile', $data)
            ->layout('layouts.app', ['title' => $this->user->display_name ?? $this->user->name]);
    }
}
