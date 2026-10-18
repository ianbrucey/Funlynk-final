<?php

namespace App\Livewire\Discovery;

use App\Services\FeedService;
use App\Services\PostService;
use Illuminate\Support\Facades\Log;
use Livewire\Attributes\Lazy;
use Livewire\Attributes\On;
use Livewire\Attributes\Url;
use Livewire\Component;

#[Lazy]
class NearbyFeed extends Component
{
    #[Url(as: 'q')]
    public string $searchQuery = '';

    public int $eventRadiusKm = 50; // km, 10-50, clamped server-side; posts are a fixed 10km

    public int $eventRadiusMiles = 31; // display/input unit; km stays the stored contract

    public $contentType = 'all'; // all, posts, events

    public $timeFilter = 'all'; // all, today, week, month

    // Cursor-based pagination — opaque, server-generated, never decoded client-side.
    public ?string $cursor = null;

    public $perPage = 30; // Initial load: 30 items

    public $hasMore = true;

    public $items = [];

    public function mount()
    {
        // Get query from URL if present
        $this->searchQuery = request()->query('q', '');

        // Load initial items
        $this->loadItems();
    }

    /**
     * Skeleton placeholder rendered immediately while #[Lazy] defers the real
     * mount()/render() cycle (and its feed query) to Livewire's follow-up
     * request. Root element must match the component view's root (<div>).
     */
    public function placeholder(): \Illuminate\Contracts\View\View
    {
        // The real render() call below sets the page layout, but for a
        // #[Lazy] component the placeholder is what actually renders first
        // on the initial HTTP response — so the layout has to be set here
        // too, or Livewire falls back to its own default page-component
        // layout (`components.layouts.app`), which this app doesn't have.
        return view('livewire.discovery.nearby-feed-skeleton')->layout('layouts.app', ['title' => 'Home']);
    }

    /**
     * Button-triggered search - called when user clicks Search button or presses Enter
     */
    public function search(): void
    {
        $this->resetFeed();
    }

    public function updatedEventRadiusKm(): void
    {
        $this->eventRadiusKm = max(FeedService::EVENT_RADIUS_MIN_KM, min(FeedService::EVENT_RADIUS_MAX_KM, (int) $this->eventRadiusKm));
        $this->resetFeed();
    }

    public function updatedEventRadiusMiles(): void
    {
        $miles = max(6, min(31, (int) $this->eventRadiusMiles));
        $this->eventRadiusMiles = $miles;
        $this->eventRadiusKm = (int) round($miles * 1.609344);
        $this->updatedEventRadiusKm();
    }

    public function getFiltersActiveProperty(): bool
    {
        return $this->timeFilter !== 'all' || $this->eventRadiusMiles !== 31;
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
        $this->cursor = null;
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

        $this->perPage = 20; // Subsequent loads: 20 items
        $this->loadItems(true);
    }

    protected function loadItems($append = false)
    {
        /** @var \App\Models\User $user */
        $user = auth()->user();

        $result = app(FeedService::class)->getNearbyFeed(
            user: $user,
            cursor: $this->cursor,
            contentType: (string) $this->contentType,
            timeFilter: (string) $this->timeFilter,
            searchQuery: $this->searchQuery,
            eventRadiusKm: (int) $this->eventRadiusKm,
            perPage: $this->perPage,
        );

        // Items are plain FeedItem arrays — never Eloquent models — so no
        // extra hydration/DB access happens on append or re-render.
        $this->items = $append ? array_merge($this->items, $result['items']) : $result['items'];

        $this->cursor = $result['nextCursor'];
        $this->hasMore = $result['hasMore'];
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

            $result = app(PostService::class)->toggleReaction($postId, $reactionType);

            Log::info('reactToPost success', ['action' => $result['action']]);

            $this->updatePostInItems($postId, $result['action']);

            // Dispatch success event
            $this->dispatch('post-reacted', postId: $postId, reactionType: $reactionType, action: $result['action']);
        } catch (\Exception $e) {
            Log::error('reactToPost failed', ['error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
            // Handle error (user not authenticated, invalid reaction type, etc.)
            session()->flash('error', 'Failed to react to post: '.$e->getMessage());
        }
    }

    /**
     * Mutate the single affected item in $items — no model reload, no extra
     * query. $items holds plain FeedItem arrays (never Eloquent models).
     */
    protected function updatePostInItems(string $postId, string $action): void
    {
        foreach ($this->items as $index => $item) {
            if ($item['type'] === 'post' && $item['id'] === $postId) {
                $this->items[$index]['viewer_reacted'] = $action === 'added';
                $this->items[$index]['reaction_count'] = max(0, $item['reaction_count'] + ($action === 'added' ? 1 : -1));
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
