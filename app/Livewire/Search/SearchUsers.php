<?php

namespace App\Livewire\Search;

use App\Models\Follow;
use App\Models\User;
use App\Services\MeilisearchUserSearchService;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Url;
use Livewire\Component;

class SearchUsers extends Component
{
    #[Url(as: 'q')]
    public string $query = '';

    #[Url(as: 'interests')]
    public array $selectedInterests = [];

    #[Url(as: 'distance')]
    public ?int $distance = null;

    public array $popularInterests = [];

    public array $followingIds = [];

    public string $customInterestInput = '';

    // Infinite scroll properties
    public $page = 1;
    public $perPage = 15; // Initial load: 15 users
    public $hasMore = true;
    public $users = [];
    public $totalUsers = 0;

    public function mount()
    {
        $service = app(MeilisearchUserSearchService::class);
        $this->popularInterests = $service->getPopularInterests(15);

        // Set default distance if user has location
        if (is_null($this->distance) && Auth::user()->location_coordinates) {
            $this->distance = config('search.default_radius', 25);
        }

        $this->loadFollowingIds();

        // Load initial users
        $this->loadUsers();
    }

    public function updatedQuery()
    {
        $this->resetSearch();
    }

    public function updatedSelectedInterests()
    {
        $this->resetSearch();
    }

    public function updatedDistance()
    {
        $this->resetSearch();
    }

    protected function resetSearch()
    {
        $this->page = 1;
        $this->users = [];
        $this->hasMore = true;
        $this->loadUsers();
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
        $this->resetSearch();
    }

    public function addCustomInterest()
    {
        $interest = trim($this->customInterestInput);

        if (empty($interest)) {
            return;
        }

        // Capitalize first letter of each word
        $interest = ucwords(strtolower($interest));

        // Check if already selected
        if (!in_array($interest, $this->selectedInterests)) {
            $this->selectedInterests[] = $interest;
            $this->resetSearch();
        }

        // Clear input
        $this->customInterestInput = '';
    }

    public function removeInterest(string $interest)
    {
        $this->selectedInterests = array_values(
            array_filter($this->selectedInterests, fn ($i) => $i !== $interest)
        );
        $this->resetSearch();
    }

    public function clearFilters()
    {
        $this->query = '';
        $this->selectedInterests = [];
        $this->customInterestInput = '';
        $this->distance = Auth::user()->location_coordinates
            ? config('search.default_radius', 25)
            : null;
        $this->resetSearch();
    }

    public function loadMore()
    {
        // Cap at 200 users total
        if (count($this->users) >= 200) {
            $this->hasMore = false;
            return;
        }

        $this->page++;
        $this->perPage = 20; // Subsequent loads: 20 users
        $this->loadUsers(true);
    }

    protected function loadUsers($append = false)
    {
        $service = app(MeilisearchUserSearchService::class);

        $result = $service->search(
            $this->query,
            $this->selectedInterests,
            $this->distance,
            Auth::user(),
            $this->page,
            $this->perPage
        );

        if ($append) {
            // Append new users to existing users
            $this->users = array_merge($this->users, $result['users']->toArray());
        } else {
            // Replace users (for initial load or filter changes)
            $this->users = $result['users']->toArray();
        }

        $this->hasMore = $result['hasMore'];
        $this->totalUsers = $result['total'];
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

            // Reload current page to update follower counts
            $this->loadUsers();
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

            // Reload current page to update follower counts
            $this->loadUsers();
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
        return view('livewire.search.search-users')->layout('layouts.app', [
            'title' => 'Find People',
        ]);
    }
}
