<?php

namespace App\Livewire\Discovery;

use App\Models\Activity;
use App\Models\Post;
use Livewire\Component;
use Livewire\WithPagination;

class ForYouFeed extends Component
{
    use WithPagination;

    public function reactToPost($postId, $reactionType)
    {
        try {
            app(\App\Services\PostService::class)->reactToPost($postId, $reactionType);

            // Dispatch success event
            $this->dispatch('post-reacted', postId: $postId, reactionType: $reactionType);

            // Refresh the feed to show updated reaction counts
            $this->dispatch('$refresh');
        } catch (\Exception $e) {
            // Handle error (user not authenticated, invalid reaction type, etc.)
            session()->flash('error', 'Failed to react to post: ' . $e->getMessage());
        }
    }

    public function render()
    {
        $user = auth()->user();

        $items = app(\App\Services\FeedService::class)->getForYouFeed($user);

        return view('livewire.discovery.for-you-feed', [
            'items' => $items,
        ])->layout('layouts.app', ['title' => 'For You']);
    }
}
