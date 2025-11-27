<?php

namespace App\Livewire\Search;

use App\Contracts\SearchServiceInterface;
use App\Services\PostService;
use Livewire\Component;

class SearchPage extends Component
{
    public string $query = '';

    public string $contentType = 'all'; // all, posts, events

    public ?int $radius = null; // Optional geo filter

    public bool $useGeoFilter = false;

    public function mount()
    {
        // Get query from URL if present
        $this->query = request()->query('q', '');
    }

    public function updatedQuery()
    {
        // Trigger search when query changes (debounced in Alpine)
    }

    public function reactToPost($postId, $reactionType)
    {
        try {
            app(PostService::class)->reactToPost($postId, $reactionType);

            // Dispatch success event
            $this->dispatch('post-reacted', postId: $postId, reactionType: $reactionType);

            // Refresh the results
            $this->dispatch('$refresh');
        } catch (\Exception $e) {
            session()->flash('error', 'Failed to react to post: '.$e->getMessage());
        }
    }

    public function render()
    {
        $items = collect();

        // Only search if query is not empty
        if (! empty(trim($this->query))) {
            $user = auth()->user();

            $items = app(SearchServiceInterface::class)->search(
                query: $this->query,
                user: $user,
                radius: $this->useGeoFilter ? $this->radius : null,
                contentType: $this->contentType
            );
        }

        return view('livewire.search.search-page', [
            'items' => $items,
        ])->layout('layouts.app', ['title' => 'Search']);
    }
}
