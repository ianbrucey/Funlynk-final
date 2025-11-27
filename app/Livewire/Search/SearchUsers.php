<?php

namespace App\Livewire\Search;

use App\Models\Follow;
use App\Models\User;
use App\Services\MeilisearchUserSearchService;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

class SearchUsers extends Component
{
    use WithPagination;

    #[Url(as: 'q')]
    public string $query = '';

    #[Url(as: 'interests')]
    public array $selectedInterests = [];

    #[Url(as: 'distance')]
    public ?int $distance = null;

    public array $popularInterests = [];

    public array $followingIds = [];

    public function mount()
    {
        $service = app(MeilisearchUserSearchService::class);
        $this->popularInterests = $service->getPopularInterests(15);

        // Set default distance if user has location
        if (is_null($this->distance) && Auth::user()->location_coordinates) {
            $this->distance = config('search.default_radius', 25);
        }

        $this->loadFollowingIds();
    }

    public function updatedQuery()
    {
        $this->resetPage();
    }

    public function updatedSelectedInterests()
    {
        $this->resetPage();
    }

    public function updatedDistance()
    {
        $this->resetPage();
    }

    public function toggleInterest(string $interest)
    {
        if (in_array($interest, $this->selectedInterests)) {
            $this->selectedInterests = array_values(
                array_filter($this->selectedInterests, fn ($i) => $i !== $interest)
            );
        } else {
            $this->selectedInterests[] = $interest;
        }
        $this->resetPage();
    }

    public function clearFilters()
    {
        $this->query = '';
        $this->selectedInterests = [];
        $this->distance = Auth::user()->location_coordinates
            ? config('search.default_radius', 25)
            : null;
        $this->resetPage();
    }

    public function follow(string $userId)
    {
        if (! Auth::check()) {
            return redirect()->route('login');
        }

        if (Auth::id() === $userId) {
            return;
        }

        if (! in_array($userId, $this->followingIds)) {
            Follow::create([
                'follower_id' => Auth::id(),
                'following_id' => $userId,
            ]);

            // Update counts
            User::where('id', $userId)->increment('follower_count');
            Auth::user()->increment('following_count');

            $this->followingIds[] = $userId;

            $this->dispatch('user-followed', userId: $userId);
        }
    }

    public function unfollow(string $userId)
    {
        if (! Auth::check() || Auth::id() === $userId) {
            return;
        }

        if (in_array($userId, $this->followingIds)) {
            Follow::where('follower_id', Auth::id())
                ->where('following_id', $userId)
                ->delete();

            // Update counts
            User::where('id', $userId)->decrement('follower_count');
            Auth::user()->decrement('following_count');

            $this->followingIds = array_values(
                array_filter($this->followingIds, fn ($id) => $id !== $userId)
            );

            $this->dispatch('user-unfollowed', userId: $userId);
        }
    }

    protected function loadFollowingIds()
    {
        if (Auth::check()) {
            $this->followingIds = Follow::where('follower_id', Auth::id())
                ->pluck('following_id')
                ->toArray();
        }
    }

    public function render()
    {
        $service = app(MeilisearchUserSearchService::class);

        $results = $service->search(
            $this->query,
            $this->selectedInterests,
            $this->distance,
            Auth::user()
        );

        return view('livewire.search.search-users', [
            'results' => $results,
        ])->layout('layouts.app', [
            'title' => 'Find People',
        ]);
    }
}
