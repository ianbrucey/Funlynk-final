<?php

namespace App\Livewire\Discovery;

use Illuminate\Support\Facades\Log;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

class NearbyFeed extends Component
{
    use WithPagination;

    #[Url(as: 'q')]
    public string $searchQuery = '';

    public $radius = 10; // km
    public $contentType = 'all'; // all, posts, events
    public $timeFilter = 'all'; // all, today, week, month

    public function mount()
    {
        // Get query from URL if present
        $this->searchQuery = request()->query('q', '');
    }

    public function updatedSearchQuery()
    {
        $this->resetPage();
    }

    public function updatedRadius()
    {
        $this->resetPage();
    }

    public function updatedContentType()
    {
        $this->resetPage();
    }

    public function updatedTimeFilter()
    {
        $this->resetPage();
    }

    public function clearSearch()
    {
        $this->searchQuery = '';
        $this->resetPage();
    }

    public function reactToPost($postId, $reactionType)
    {
        try {
            Log::info('reactToPost called', ['postId' => $postId, 'reactionType' => $reactionType, 'userId' => auth()->id()]);

            $result = app(\App\Services\PostService::class)->toggleReaction($postId, $reactionType);

            Log::info('reactToPost success', ['action' => $result['action']]);

            // Dispatch success event
            $this->dispatch('post-reacted', postId: $postId, reactionType: $reactionType, action: $result['action']);

            // Refresh the feed to show updated reaction counts
            $this->dispatch('$refresh');
        } catch (\Exception $e) {
            Log::error('reactToPost failed', ['error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
            // Handle error (user not authenticated, invalid reaction type, etc.)
            session()->flash('error', 'Failed to react to post: '.$e->getMessage());
        }
    }

    public function render()
    {
        /** @var \App\Models\User $user */
        $user = auth()->user();

        $items = app(\App\Services\FeedService::class)->getNearbyFeed(
            user: $user,
            radius: (int) $this->radius,
            contentType: (string) $this->contentType,
            timeFilter: (string) $this->timeFilter,
            searchQuery: $this->searchQuery,
        );

        return view('livewire.discovery.nearby-feed', [
            'items' => $items,
            'searchQuery' => $this->searchQuery,
        ])->layout('layouts.app', ['title' => 'Home']);
    }
}
