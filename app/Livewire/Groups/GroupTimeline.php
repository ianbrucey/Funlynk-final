<?php

namespace App\Livewire\Groups;

use App\Models\Group;
use App\Services\GroupContentService;
use Illuminate\Support\Collection;
use Livewire\Component;

class GroupTimeline extends Component
{
    public Group $group;

    public Collection $items;

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
        $this->items = collect();
        $this->loadMore();
    }

    public function loadMore(): void
    {
        if (! $this->hasMore) {
            return;
        }

        $newItems = $this->contentService->getGroupTimeline($this->group, $this->page, $this->perPage);

        $this->items = $this->items->concat($newItems);
        $this->page++;
        $this->hasMore = ($newItems->count() === $this->perPage);
    }

    public function createPost(): void
    {
        // Placeholder for creating a new post
        $this->dispatch('open-create-post-modal');
    }

    public function createEvent(): void
    {
        // Placeholder for creating a new event
        $this->dispatch('open-create-event-modal');
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
        $this->items = $this->items->reject(fn ($item) => $item->id === $itemId && $item->type === $itemType);
    }

    public function getListeners(): array
    {
        return [
            "echo-private:group.{$this->group->id},GroupPostCreated" => 'onPostCreated',
            "echo-private:group.{$this->group->id},GroupEventCreated" => 'onEventCreated',
        ];
    }

    public function onPostCreated(): void
    {
        // Reload timeline or prepend new post
        $this->reset(['page', 'hasMore']);
        $this->items = collect();
        $this->loadMore();
    }

    public function onEventCreated(): void
    {
        // Reload timeline or prepend new event
        $this->reset(['page', 'hasMore']);
        $this->items = collect();
        $this->loadMore();
    }

    public function render()
    {
        return view('livewire.groups.group-timeline')
            ->layout('layouts.app');
    }
}
