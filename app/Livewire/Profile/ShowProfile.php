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

    public bool $canViewContent = true;

    public function mount($username = null)
    {
        // If no username provided, show current user's profile
        if ($username) {
            $this->user = User::where('username', $username)->firstOrFail();
        } else {
            $this->user = auth()->user();
        }

        $this->checkFollowStatus();
        $this->canViewContent = $this->user->allowsProfileContentFor(Auth::user());
        $this->loadStats();
    }

    public function loadStats()
    {
        $this->followersCount = $this->user->followers()->count();
        $this->followingCount = $this->user->following()->count();

        if (! $this->canViewContent) {
            return;
        }

        $this->postsCount = $this->postsQuery()->count();
        $this->hostedActivitiesCount = $this->hostedQuery()->count();
        $this->attendedActivitiesCount = $this->attendingQuery()->count();

        // Count interested posts (posts with "I'm down" reactions)
        $interested = Post::whereHas('reactions', function ($q) {
            $q->where('user_id', $this->user->id)
                ->where('reaction_type', 'im_down');
        });
        $this->interestedPostsCount = ($this->isOwner() ? $interested : $interested->visibleOnProfile())->count();
    }

    protected function isOwner(): bool
    {
        return Auth::id() === $this->user->id;
    }

    /**
     * @return \Illuminate\Database\Eloquent\Builder<Post>
     */
    protected function postsQuery(): \Illuminate\Database\Eloquent\Builder
    {
        $query = Post::query()->where('user_id', $this->user->id);

        return $this->isOwner() ? $query : $query->visibleOnProfile();
    }

    /**
     * @return \Illuminate\Database\Eloquent\Builder<Activity>
     */
    protected function hostedQuery(): \Illuminate\Database\Eloquent\Builder
    {
        $query = Activity::query()->where('host_id', $this->user->id);

        return $this->isOwner() ? $query : $this->publicActivities($query);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Builder<Activity>
     */
    protected function attendingQuery(): \Illuminate\Database\Eloquent\Builder
    {
        $query = Activity::query()->whereHas('rsvps', function ($q) {
            $q->where('user_id', $this->user->id)->where('status', 'attending');
        });

        return $this->isOwner() ? $query : $this->publicActivities($query);
    }

    /**
     * @param  \Illuminate\Database\Eloquent\Builder<Activity>  $query
     * @return \Illuminate\Database\Eloquent\Builder<Activity>
     */
    protected function publicActivities(\Illuminate\Database\Eloquent\Builder $query): \Illuminate\Database\Eloquent\Builder
    {
        return $query->discoverable()->whereIn('status', ['published', 'active', 'completed']);
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
            $this->canViewContent = $this->user->allowsProfileContentFor(Auth::user());
            $this->loadStats();
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
            $this->canViewContent = $this->user->allowsProfileContentFor(Auth::user());
            $this->loadStats();
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

        switch ($this->canViewContent ? $this->activeTab : null) {
            case 'posts':
                $data['posts'] = $this->postsQuery()
                    ->latest()
                    ->paginate(10);
                break;
            case 'hosted':
                $data['activities'] = $this->hostedQuery()
                    ->latest()
                    ->paginate(10);
                break;
            case 'attending':
                $data['activities'] = $this->attendingQuery()
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
