<?php

namespace App\Livewire\Groups;

use App\Models\Group;
use App\Services\GroupContentService;
use Livewire\Component;

class GroupTimeline extends Component
{
    public Group $group;

    public int $limit = 10;

    public bool $hasMore = true;

    protected int $perPage = 10;

    protected GroupContentService $contentService;

    public function boot(GroupContentService $contentService): void
    {
        $this->contentService = $contentService;
    }

    public function mount(Group $group): void
    {
        abort_if(
            $group->privacy === 'private' && ! $group->memberships()->where('user_id', auth()->id())->exists(),
            403
        );

        $this->group = $group;
    }

    public function loadMore(): void
    {
        if ($this->hasMore) {
            $this->limit += $this->perPage;
        }
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

    public function deleteItem(string $itemId, string $itemType): void
    {
        $user = auth()->user();
        $isAdmin = $this->group->isAdmin($user);

        if ($itemType === 'post') {
            $item = $this->group->posts()->find($itemId);
            $ownerId = $item?->getAttribute('user_id');
        } else {
            $item = $this->group->activities()->find($itemId);
            $ownerId = $item?->getAttribute('host_id');
        }

        if (! $item || ! $user || ($user->id !== $ownerId && ! $isAdmin)) {
            session()->flash('error', 'You cannot delete this item.');

            return;
        }

        $item->delete();
        session()->flash('success', 'Deleted.');
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
        $this->limit = $this->perPage;
        $this->hasMore = true;
    }

    public function render()
    {
        $fetched = $this->contentService->getGroupTimeline($this->group, $this->limit + 1);
        $this->hasMore = $fetched->count() > $this->limit;

        return view('livewire.groups.group-timeline', ['items' => $fetched->take($this->limit)])
            ->layout('layouts.app');
    }
}
