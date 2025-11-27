# Infinite Scroll Implementation

**Date**: 2025-11-27  
**Status**: ✅ Complete  
**Epic**: E04 Discovery Engine  
**Feature**: F01 Feed System

## Overview

Implemented infinite scroll pagination for the NearbyFeed component to improve performance and user experience, especially on mobile devices.

## Changes Made

### 1. FeedService Updates (`app/Services/FeedService.php`)

**Modified Methods:**
- `getNearbyFeed()` - Now returns array with pagination metadata
- `queryNearbyPosts()` - Added pagination parameters (page, perPage)
- `queryNearbyEvents()` - Added pagination parameters (page, perPage)
- `getFallbackNearbyFeed()` - Added pagination support
- `getMapViewData()` - Updated to use new query method signatures

**New Return Structure:**
```php
[
    'items' => Collection,      // The actual posts/events
    'hasMore' => bool,          // Whether more items exist
    'total' => int,             // Total matching items
    'page' => int,              // Current page number
]
```

**Pagination Strategy:**
- Initial load: 30 items (15 posts + 15 events)
- Subsequent loads: 20 items per scroll
- Maximum cap: 200 items total
- Uses Meilisearch `offset` and `limit` parameters
- Preserves geo-sorting across pages

### 2. NearbyFeed Component Updates (`app/Livewire/Discovery/NearbyFeed.php`)

**Removed:**
- `WithPagination` trait (was unused)

**Added Properties:**
```php
public $page = 1;
public $perPage = 30;        // Initial load
public $hasMore = true;
public $items = [];
public $totalItems = 0;
```

**New Methods:**
- `loadMore()` - Loads next page of items
- `loadItems($append)` - Fetches items from service
- `resetFeed()` - Resets pagination when filters change

**Behavior:**
- Initial load: Fetches 30 items on mount
- Filter changes: Resets to page 1, clears items
- Scroll trigger: Loads 20 more items (appends to existing)
- Cap: Stops at 200 items with message to refine filters

### 3. Blade View Updates (`resources/views/livewire/discovery/nearby-feed.blade.php`)

**Added:**
- Load More button with auto-trigger on scroll
- IntersectionObserver for automatic loading when button is visible
- Loading spinner during fetch
- Item counter (showing X of Y items)
- Max items reached message (at 200 items)
- Loading opacity effect on feed container

**User Experience:**
- Button appears when more items available
- Automatically triggers when scrolled into view
- Manual click option for user control
- Clear feedback during loading
- Helpful message when limit reached

## How It Works

### Happy Path (No Filters)

```
User opens feed
    ↓
Load page 1 (30 items: ~15 posts + ~15 events)
    ↓
Meilisearch queries:
  - Posts: limit=15, offset=0, geo filter, sort by distance
  - Events: limit=15, offset=0, geo filter, sort by distance
    ↓
Merge and sort by temporal relevance (posts boosted)
    ↓
Render 30 items
    ↓
User scrolls to bottom
    ↓
IntersectionObserver triggers loadMore()
    ↓
Load page 2 (20 items: ~10 posts + ~10 events)
    ↓
Meilisearch queries:
  - Posts: limit=10, offset=15
  - Events: limit=10, offset=15
    ↓
Append to existing items (now 50 total)
    ↓
Continue until 200 items or no more results
```

### With Search/Filters

```
User types "basketball" in search
    ↓
resetFeed() called
    ↓
Clear items array, reset page to 1
    ↓
Load page 1 with search query
    ↓
Meilisearch full-text search + geo filter
    ↓
Returns matching items
    ↓
User can scroll to load more matching items
```

## Testing

### Test Data Generated
- Created 100 test posts around Lawrenceville, GA (33.9507556, -83.9875616)
- Posts spread within ~10km radius
- Various activities, time hints, and descriptions
- All indexed in Meilisearch

### Test Command
```bash
php artisan posts:generate-test 100 --post-id=019ac2ff-679a-7022-abe0-9702a53dc5eb
php artisan scout:import "App\Models\Post"
```

## Benefits

✅ **Performance**: Only loads 30 items initially (vs 200 before)  
✅ **Bandwidth**: Reduces initial payload by ~85%  
✅ **UX**: Smooth infinite scroll on mobile  
✅ **Scalability**: Can handle thousands of posts without performance issues  
✅ **Flexibility**: Easy to adjust perPage values  
✅ **Consistency**: Geo-sorting preserved across pages via Meilisearch  

## Future Enhancements

- [ ] Add pull-to-refresh for new posts
- [ ] Show "New posts available" banner when new content appears
- [ ] Implement cursor-based pagination for better real-time handling
- [ ] Add skeleton loaders during initial load
- [ ] Cache pages client-side for back navigation

