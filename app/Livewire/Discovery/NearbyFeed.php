<?php

namespace App\Livewire\Discovery;

use Illuminate\Support\Facades\Log;
use Livewire\Attributes\On;
use Livewire\Attributes\Url;
use Livewire\Component;

class NearbyFeed extends Component
{
    #[Url(as: 'q')]
    public string $searchQuery = '';

    public $radius = 30; // km

    public $contentType = 'all'; // all, posts, events

    public $timeFilter = 'all'; // all, today, week, month

    // Infinite scroll properties
    public $page = 1;

    public $perPage = 30; // Initial load: 30 items

    public $hasMore = true;

    public $items = [];

    public $totalItems = 0;

    public function mount()
    {
        // Get query from URL if present
        $this->searchQuery = request()->query('q', '');

        // Load initial items
        $this->loadItems();
    }

    /**
     * Button-triggered search - called when user clicks Search button or presses Enter
     */
    public function search(): void
    {
        $this->resetFeed();
    }

    public function updatedRadius()
    {
        $this->resetFeed();
    }

    public function updatedContentType()
    {
        $this->resetFeed();
    }

    public function updatedTimeFilter()
    {
        $this->resetFeed();
    }

    /**
     * Clear search and reload default results
     */
    public function clearSearch(): void
    {
        $this->searchQuery = '';
        $this->resetFeed();
    }

    protected function resetFeed()
    {
        $this->page = 1;
        $this->items = [];
        $this->hasMore = true;
        $this->loadItems();
    }

    public function loadMore()
    {
        // Cap at 200 items total
        if (count($this->items) >= 200) {
            $this->hasMore = false;

            return;
        }

        $this->page++;
        $this->perPage = 20; // Subsequent loads: 20 items
        $this->loadItems(true);
    }

    protected function loadItems($append = false)
    {
        /** @var \App\Models\User $user */
        $user = auth()->user();

        $result = app(\App\Services\FeedService::class)->getNearbyFeed(
            user: $user,
            radius: (int) $this->radius,
            contentType: (string) $this->contentType,
            timeFilter: (string) $this->timeFilter,
            searchQuery: $this->searchQuery,
            page: $this->page,
            perPage: $this->perPage,
        );

        if ($append) {
            // Append new items to existing items
            $this->items = array_merge($this->items, $result['items']->all());
        } else {
            // Replace items (for initial load or filter changes)
            $this->items = $result['items']->all();
        }

        $this->hasMore = $result['hasMore'];
        $this->totalItems = $result['total'];
    }

    /**
     * Handle reaction from Alpine.js button (dispatched via Livewire.dispatch)
     */
    #[On('react-to-post')]
    public function handleReactToPost(string $postId, string $reactionType): void
    {
        $this->reactToPost($postId, $reactionType);
    }

    public function reactToPost($postId, $reactionType)
    {
        try {
            Log::info('reactToPost called', ['postId' => $postId, 'reactionType' => $reactionType, 'userId' => auth()->id()]);

            $result = app(\App\Services\PostService::class)->toggleReaction($postId, $reactionType);

            Log::info('reactToPost success', ['action' => $result['action']]);

            // Dispatch success event
            $this->dispatch('post-reacted', postId: $postId, reactionType: $reactionType, action: $result['action']);

            // Note: We don't update items array here to avoid re-render issues
            // The Alpine.js component handles optimistic UI updates
        } catch (\Exception $e) {
            Log::error('reactToPost failed', ['error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
            // Handle error (user not authenticated, invalid reaction type, etc.)
            session()->flash('error', 'Failed to react to post: '.$e->getMessage());
        }
    }

    protected function updatePostInItems($postId)
    {
        // Find and update the specific post in the items array
        foreach ($this->items as $index => $item) {
            if ($item['type'] === 'post' && $item['data']->id === $postId) {
                // Refresh just this post from the database
                $post = \App\Models\Post::with(['user', 'reactions', 'tags'])->find($postId);
                if ($post) {
                    $this->items[$index]['data'] = $post;
                }
                break;
            }
        }
    }

    public function openConversionModal(string $postId)
    {
        $this->dispatch('open-conversion-modal', postId: $postId);
    }

    public function dismissBanner(string $postId)
    {
        session()->put("conversion_banner_dismissed_{$postId}", true);

        $this->dispatch('banner-dismissed');
    }

    public function render()
    {
        return view('livewire.discovery.nearby-feed')->layout('layouts.app', ['title' => 'Home']);
    }
}
