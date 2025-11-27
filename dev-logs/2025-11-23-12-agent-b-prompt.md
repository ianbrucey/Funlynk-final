# Agent B - Next Tasks Prompt

**Date**: 2025-11-23  
**Agent**: Agent B (Backend Specialist)  
**Sprint**: E04 Discovery Engine - Services Implementation  
**Status**: PostService Complete, 4 Services Remaining

---

## 🎯 Your Mission

Agent A has completed all UI components for the Discovery Engine. Your PostService is working great and already integrated! Now you need to build the remaining 4 services to power the feeds with real data.

**Current Status**: Agent A's UI is using placeholder data. Once you build these services, the entire Discovery Engine will be fully functional.

---

## 📋 Your Task List (Priority Order)

### **Priority 1: FeedService** ⚡ CRITICAL

**File**: `app/Services/FeedService.php`

**Why Critical**: All 3 UI components (Nearby Feed, For You Feed, Map View) are waiting for this service.

**What to Build**:

```php
class FeedService
{
    /**
     * Get nearby feed with geo-proximity filtering
     * Posts: 5-10km radius
     * Events: 25-50km radius
     */
    public function getNearbyFeed(
        User $user, 
        int $radius = 10,
        string $contentType = 'all', // all, posts, events
        string $timeFilter = 'all'   // all, today, week, month
    ): Collection;
    
    /**
     * Get personalized "For You" feed
     * Uses RecommendationEngine for scoring
     */
    public function getForYouFeed(User $user): Collection;
    
    /**
     * Get map markers data
     * Returns array with 'markers' and 'center'
     */
    public function getMapData(
        User $user,
        int $radius = 10,
        string $contentType = 'all'
    ): array;
}
```

**Integration Points**:
- `app/Livewire/Discovery/NearbyFeed.php` line 29-67 (replace placeholder queries)
- `app/Livewire/Discovery/ForYouFeed.php` line 22-68 (replace placeholder queries)
- `app/Livewire/Discovery/MapView.php` line 20-80 (replace placeholder queries)

**Reference**: See `dev-logs/2025-11-23-11-agent-b-tasks.md` lines 19-186 for detailed implementation guide.

---

### **Priority 2: ConversionService** 🔄 HIGH

**File**: `app/Services/ConversionService.php`

**Why Important**: This is FunLynk's core innovation - Posts evolving into Events.

**What to Build**:

```php
class ConversionService
{
    /**
     * Suggest conversion to host (5+ reactions)
     */
    public function suggestConversion(string $postId): void;
    
    /**
     * Convert post to event (manual or auto)
     */
    public function convertPostToEvent(string $postId, array $additionalData = []): Activity;
    
    /**
     * Auto-convert at 10+ reactions
     */
    public function autoConvert(string $postId): Activity;
    
    /**
     * Notify all reactors about conversion
     */
    protected function notifyReactors(Post $post, Activity $event): void;
}
```

**Integration Points**:
- Listen to `PostReacted` event (dispatched by your PostService)
- Check conversion eligibility
- Create notifications for host and reactors

**Reference**: See `dev-logs/2025-11-23-11-agent-b-tasks.md` lines 261-362 for detailed implementation guide.

---

### **Priority 3: RecommendationEngine** 🎯 MEDIUM

**File**: `app/Services/RecommendationEngine.php`

**Why Important**: Powers personalized "For You" feed with smart recommendations.

**What to Build**:

```php
class RecommendationEngine
{
    /**
     * Score content for personalization
     * Returns 0-100 score based on:
     * - Location proximity (0-40 points)
     * - Interest match (0-30 points)
     * - Social graph (0-20 points)
     * - Temporal relevance (0-10 points)
     */
    public function scoreContent(User $user, $content): float;
    
    /**
     * Generate human-readable reason
     * e.g., "Based on your interest in Basketball"
     */
    public function getReasonForScore(User $user, $content): string;
}
```

**Integration Points**:
- Used by `FeedService::getForYouFeed()`
- Provides "Why you're seeing this" text for UI

**Reference**: See `dev-logs/2025-11-23-11-agent-b-tasks.md` lines 366-403 for detailed implementation guide.

---

### **Priority 4: ExpirePostsJob** 🧹 LOW

**File**: `app/Jobs/ExpirePostsJob.php`

**Why Important**: Cleanup task to expire old posts automatically.

**What to Build**:

```php
class ExpirePostsJob implements ShouldQueue
{
    public function handle(): void
    {
        $count = app(PostService::class)->expirePosts();
        Log::info("Expired {$count} posts");
    }
}
```

**Schedule**: Add to `app/Console/Kernel.php`:
```php
$schedule->job(new ExpirePostsJob)->hourly();
```

---

## 📚 Key Resources

### Documentation:
- **Your Task List**: `dev-logs/2025-11-23-11-agent-b-tasks.md` (detailed specs)
- **Integration Status**: `dev-logs/2025-11-23-12-integration-status.md` (what's done, what's needed)
- **Agent A's Work**: `dev-logs/2025-11-23-12-agent-a-complete.md` (UI components ready)

### Models Available (Agent C completed):
- `app/Models/Post.php` - With `active()`, `nearUser()` scopes
- `app/Models/Activity.php` - With `convertedFromPost()` scope
- `app/Models/PostReaction.php` - With `validReactionTypes()`
- `app/Models/PostConversion.php` - For tracking conversions

### Your Completed Work:
- `app/Services/PostService.php` - Already integrated and working!

---

## ✅ Definition of Done

- [ ] FeedService created with 3 methods
- [ ] ConversionService created with conversion logic
- [ ] RecommendationEngine created with scoring algorithm
- [ ] ExpirePostsJob created and scheduled
- [ ] All services have Pest tests
- [ ] Agent A's Livewire components updated to use your services
- [ ] End-to-end testing: Create post → React → See in feed → Convert to event

---

## 🚀 Getting Started

1. **Start with FeedService** - It unblocks all 3 UI components
2. **Use PostGIS queries** - All models have `location_coordinates` with spatial support
3. **Reference your PostService** - You already did this perfectly, follow the same pattern
4. **Write tests as you go** - Use Pest v4
5. **Check Agent A's components** - See how they're calling your services

**Good luck! The UI is ready and waiting for your services.** 🔧

