<?php

namespace App\Livewire\Discovery;

use App\Models\Activity;
use App\Models\Post;
use Livewire\Component;
use Livewire\WithPagination;

class NearbyFeed extends Component
{
    use WithPagination;

    public $radius = 10; // km
    public $contentType = 'all'; // all, posts, events
    public $timeFilter = 'all'; // all, today, week, month

    public function mount()
    {
        // Placeholder: location is handled inside FeedService using the auth user
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

    public function reactToPost($postId, $reactionType)
    {
        try {
            \Log::info('reactToPost called', ['postId' => $postId, 'reactionType' => $reactionType, 'userId' => auth()->id()]);

            $result = app(\App\Services\PostService::class)->toggleReaction($postId, $reactionType);

            \Log::info('reactToPost success', ['action' => $result['action']]);

            // Dispatch success event
            $this->dispatch('post-reacted', postId: $postId, reactionType: $reactionType, action: $result['action']);

            // Refresh the feed to show updated reaction counts
            $this->dispatch('$refresh');
        } catch (\Exception $e) {
            \Log::error('reactToPost failed', ['error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
            // Handle error (user not authenticated, invalid reaction type, etc.)
            session()->flash('error', 'Failed to react to post: ' . $e->getMessage());
        }
    }

    public function render()
    {
        $user = auth()->user();

        $items = app(\App\Services\FeedService::class)->getNearbyFeed(
            $user,
            radius: (int) $this->radius,
            contentType: (string) $this->contentType,
            timeFilter: (string) $this->timeFilter,
        );

        return view('livewire.discovery.nearby-feed', [
            'items' => $items,
        ])->layout('layouts.app', ['title' => 'Nearby Feed']);
    }
}
