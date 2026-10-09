<?php

namespace App\Livewire\Groups;

use App\Models\Group;
use App\Services\GroupContentService;
use Livewire\Component;

class GroupTimeline extends Component
{
    public Group $group;

    /** @var array<int, \App\Models\Post|\App\Models\Activity> */
    public array $items = [];

    public int $page = 1;

    public bool $hasMore = true;

    protected int $perPage = 10;

    protected GroupContentService $contentService;

    public function boot(GroupContentService $contentService): void
    {
        $this->contentService = $contentService;
    }

    public function mount(Group $group): void
    {
        $this->group = $group;
        $this->items = [];
        $this->loadMore();
    }

    public function loadMore(): void
    {
        if (! $this->hasMore) {
            return;
        }

        $newItems = $this->contentService->getGroupTimeline($this->group, $this->page, $this->perPage);

        $this->items = array_merge($this->items, $newItems->all());
        $this->page++;
        $this->hasMore = ($newItems->count() === $this->perPage);
    }

    public function createPost(): void
    {
        // Dispatch event to open the create post modal
        $this->dispatch('openCreateGroupPostModal');
    }

    public function createEvent(): void
    {
        // Dispatch event to open the create event modal
        $this->dispatch('openCreateGroupEventModal');
    }

    public function likeItem(string $itemId, string $itemType): void
    {
        // Placeholder for liking/reacting to an item
        // In a real app, this would interact with a service
        session()->flash('message', "Liked {$itemType} {$itemId}!");
    }

    public function deleteItem(string $itemId, string $itemType): void
    {
        // Placeholder for deleting an item
        // In a real app, this would interact with a service and check authorization
        session()->flash('message', "Deleted {$itemType} {$itemId}!");
        $this->items = array_filter($this->items, fn ($item) => ! ($item->id === $itemId && $item->type === $itemType));
        $this->items = array_values($this->items); // Re-index array
    }

    public function pinPost(string $postId): void
    {
        // Check if user is admin
        if (! $this->group->isAdmin(auth()->user())) {
            session()->flash('error', 'Only admins can pin posts.');

            return;
        }

        // Check max 3 pinned posts limit
        $pinnedCount = $this->group->posts()->where('is_pinned', true)->count();
        if ($pinnedCount >= 3) {
            session()->flash('error', 'Maximum 3 posts can be pinned. Unpin one first.');

            return;
        }

        $post = $this->group->posts()->find($postId);
        if ($post) {
            $post->update([
                'is_pinned' => true,
                'pinned_at' => now(),
                'pinned_by' => auth()->id(),
            ]);
            $this->dispatch('postPinned');
            session()->flash('success', 'Post pinned successfully!');
        }
    }

    public function unpinPost(string $postId): void
    {
        // Check if user is admin
        if (! $this->group->isAdmin(auth()->user())) {
            session()->flash('error', 'Only admins can unpin posts.');

            return;
        }

        $post = $this->group->posts()->find($postId);
        if ($post) {
            $post->update([
                'is_pinned' => false,
                'pinned_at' => null,
                'pinned_by' => null,
            ]);
            $this->dispatch('postUnpinned');
            session()->flash('success', 'Post unpinned.');
        }
    }

    public function getListeners(): array
    {
        return [
            // WebSocket listeners (for real-time updates from other users)
            "echo-private:group.{$this->group->id},GroupPostCreated" => 'refreshTimeline',
            "echo-private:group.{$this->group->id},GroupEventCreated" => 'refreshTimeline',
            // Direct Livewire event listeners (for immediate refresh after own actions)
            'postCreated' => 'refreshTimeline',
            'eventCreated' => 'refreshTimeline',
            'postPinned' => 'refreshTimeline',
            'postUnpinned' => 'refreshTimeline',
        ];
    }

    public function refreshTimeline(): void
    {
        // Reload timeline from scratch
        $this->reset(['page', 'hasMore']);
        $this->items = [];
        $this->page = 1;
        $this->hasMore = true;
        $this->loadMore();
    }

    public function render()
    {
        return view('livewire.groups.group-timeline')
            ->layout('layouts.app');
    }
}
