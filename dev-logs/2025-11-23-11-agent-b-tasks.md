# Agent B Tasks - Services & Business Logic

**Agent**: Agent B (Backend Specialist)  
**Sprint**: E04 Discovery Engine  
**Duration**: Week 1 (Days 3-4) + Week 2 (Optimization)

---

## 🎯 Your Mission

Build the core business logic that powers FunLynk's Posts vs Events architecture: feed algorithms, recommendations, and post-to-event conversion.

**Key Principle**: Posts are ephemeral (24-48h) and spontaneous. Events are structured and persistent. The conversion from Post → Event is the platform's magic.

---

## 📋 Task List

### **Task 1: FeedService** (Priority: P0)

**File**: `app/Services/FeedService.php`

**Purpose**: Generate personalized feeds mixing Posts and Events

**Methods to Implement**:

```php
class FeedService
{
    /**
     * Get nearby feed (geo-proximity)
     * Posts: 5-10km radius
     * Events: 25-50km radius
     */
    public function getNearbyFeed(
        User $user, 
        int $radius = 10,
        string $contentType = 'all', // all, posts, events
        string $timeFilter = 'all'   // all, today, week, month
    ): Collection
    {
        $userLocation = $user->location_coordinates;
        $items = collect();
        
        // Get Posts (5-10km)
        if ($contentType !== 'events') {
            $posts = Post::active()
                ->whereDistance('location_coordinates', $userLocation, '<=', min($radius, 10) * 1000)
                ->when($timeFilter !== 'all', function($q) use ($timeFilter) {
                    // Apply time filter
                })
                ->orderBy('created_at', 'desc')
                ->get()
                ->map(fn($p) => ['type' => 'post', 'data' => $p]);
            
            $items = $items->merge($posts);
        }
        
        // Get Events (25-50km)
        if ($contentType !== 'posts') {
            $events = Activity::published()
                ->whereDistance('location_coordinates', $userLocation, '<=', $radius * 1000)
                ->when($timeFilter !== 'all', function($q) use ($timeFilter) {
                    // Apply time filter
                })
                ->orderBy('start_time', 'asc')
                ->get()
                ->map(fn($e) => ['type' => 'event', 'data' => $e]);
            
            $items = $items->merge($events);
        }
        
        // Sort by temporal relevance (posts first, then events)
        return $items->sortByDesc(function($item) {
            if ($item['type'] === 'post') {
                return $item['data']->created_at->timestamp + 100000; // Boost posts
            }
            return $item['data']->start_time->timestamp;
        });
    }
    
    /**
     * Get personalized "For You" feed
     * Uses recommendation engine for scoring
     */
    public function getForYouFeed(User $user): Collection
    {
        $recommendationEngine = app(RecommendationEngine::class);
        
        // Get candidate posts and events
        $posts = Post::active()
            ->whereDistance('location_coordinates', $user->location_coordinates, '<=', 10000)
            ->get();
        
        $events = Activity::published()
            ->whereDistance('location_coordinates', $user->location_coordinates, '<=', 50000)
            ->get();
        
        // Score and rank
        $scoredItems = collect();
        
        foreach ($posts as $post) {
            $score = $recommendationEngine->scoreContent($user, $post);
            $scoredItems->push([
                'type' => 'post',
                'data' => $post,
                'score' => $score,
                'reason' => $recommendationEngine->getReasonForScore($user, $post)
            ]);
        }
        
        foreach ($events as $event) {
            $score = $recommendationEngine->scoreContent($user, $event);
            $scoredItems->push([
                'type' => 'event',
                'data' => $event,
                'score' => $score,
                'reason' => $recommendationEngine->getReasonForScore($user, $event)
            ]);
        }
        
        return $scoredItems->sortByDesc('score')->take(50);
    }
    
    /**
     * Get map data (posts and events within bounds)
     */
    public function getMapData(
        User $user,
        int $radius = 10,
        string $contentType = 'all'
    ): array
    {
        $userLocation = $user->location_coordinates;
        $markers = [];
        
        // Get Posts
        if ($contentType !== 'events') {
            $posts = Post::active()
                ->whereDistance('location_coordinates', $userLocation, '<=', min($radius, 10) * 1000)
                ->get();
            
            foreach ($posts as $post) {
                $markers[] = [
                    'type' => 'post',
                    'id' => $post->id,
                    'lat' => $post->location_coordinates->latitude,
                    'lng' => $post->location_coordinates->longitude,
                    'title' => $post->title,
                    'timeHint' => $post->time_hint,
                    'reactionCount' => $post->reaction_count,
                    'expiresAt' => $post->expires_at->toIso8601String()
                ];
            }
        }
        
        // Get Events
        if ($contentType !== 'posts') {
            $events = Activity::published()
                ->whereDistance('location_coordinates', $userLocation, '<=', $radius * 1000)
                ->get();
            
            foreach ($events as $event) {
                $markers[] = [
                    'type' => 'event',
                    'id' => $event->id,
                    'lat' => $event->location_coordinates->latitude,
                    'lng' => $event->location_coordinates->longitude,
                    'title' => $event->title,
                    'startTime' => $event->start_time->toIso8601String(),
                    'price' => $event->price,
                    'spotsRemaining' => $event->max_attendees - $event->rsvps()->count(),
                    'convertedFromPost' => $event->originated_from_post_id !== null
                ];
            }
        }
        
        return [
            'markers' => $markers,
            'center' => [
                'lat' => $userLocation->latitude,
                'lng' => $userLocation->longitude
            ]
        ];
    }
}
```

**Testing**:
- Write Pest tests for all methods
- Test geo-proximity calculations
- Test temporal sorting
- Test content type filtering

---

### **Task 2: PostService** (Priority: P0)

**File**: `app/Services/PostService.php`

**Purpose**: Manage post lifecycle (create, react, expire)

**Methods to Implement**:

```php
class PostService
{
    public function createPost(array $data): Post
    {
        // Validate location
        // Set expiration (24-48h from now)
        // Create post
        // Dispatch PostCreated event
    }
    
    public function reactToPost(int $postId, string $reactionType): PostReaction
    {
        // Validate reaction type ('im_down' or 'join_me')
        // Create or update reaction
        // Increment post.reaction_count
        // Check conversion eligibility
        // Dispatch PostReacted event
    }
    
    public function checkConversionEligibility(int $postId): array
    {
        $post = Post::findOrFail($postId);
        $reactionCount = $post->reaction_count;
        
        return [
            'eligible' => $reactionCount >= 5,
            'auto_convert' => $reactionCount >= 10,
            'reaction_count' => $reactionCount,
            'threshold_5' => 5,
            'threshold_10' => 10
        ];
    }
    
    public function expirePosts(): int
    {
        // Find posts where expires_at < now
        // Update status to 'expired'
        // Return count of expired posts
    }
    
    public function getPostReactions(int $postId): Collection
    {
        return PostReaction::where('post_id', $postId)
            ->with('user')
            ->orderBy('created_at', 'desc')
            ->get();
    }
}
```

**Job to Create**:
- `app/Jobs/ExpirePostsJob.php` - Runs every hour to expire old posts

---

### **Task 3: ConversionService** (Priority: P0)

**File**: `app/Services/ConversionService.php`

**Purpose**: Handle post-to-event conversion (the platform's core innovation)

**Methods to Implement**:

```php
class ConversionService
{
    public function suggestConversion(int $postId): void
    {
        $post = Post::findOrFail($postId);
        
        // Check if already suggested
        if ($post->conversion_suggested_at) {
            return;
        }
        
        // Mark as suggested
        $post->update(['conversion_suggested_at' => now()]);
        
        // Notify host
        Notification::create([
            'user_id' => $post->user_id,
            'type' => 'post_conversion_suggested',
            'data' => [
                'post_id' => $post->id,
                'reaction_count' => $post->reaction_count,
                'message' => "Your post '{$post->title}' has {$post->reaction_count} reactions! Convert it to an event?"
            ]
        ]);
    }
    
    public function convertPostToEvent(int $postId, array $additionalData = []): Activity
    {
        $post = Post::findOrFail($postId);
        
        // Create event from post
        $event = Activity::create([
            'user_id' => $post->user_id,
            'title' => $post->title,
            'description' => $post->description,
            'location_coordinates' => $post->location_coordinates,
            'location_name' => $post->location_name,
            'start_time' => $additionalData['start_time'] ?? $this->inferStartTime($post),
            'originated_from_post_id' => $post->id,
            'status' => 'published',
            // ... other fields from $additionalData
        ]);
        
        // Record conversion
        PostConversion::create([
            'post_id' => $post->id,
            'activity_id' => $event->id,
            'converted_at' => now(),
            'conversion_type' => $additionalData['auto'] ?? false ? 'auto' : 'manual',
            'reaction_count_at_conversion' => $post->reaction_count
        ]);
        
        // Update post
        $post->update([
            'converted_to_activity_id' => $event->id,
            'status' => 'converted'
        ]);
        
        // Notify all reactors
        $this->notifyReactors($post, $event);
        
        return $event;
    }
    
    public function autoConvert(int $postId): Activity
    {
        return $this->convertPostToEvent($postId, ['auto' => true]);
    }
    
    protected function inferStartTime(Post $post): Carbon
    {
        // Parse time_hint (e.g., "Tonight around 8pm")
        // Return best guess or default to tomorrow
    }
    
    protected function notifyReactors(Post $post, Activity $event): void
    {
        $reactors = $post->reactions()->with('user')->get();
        
        foreach ($reactors as $reaction) {
            Notification::create([
                'user_id' => $reaction->user_id,
                'type' => 'post_converted_to_event',
                'data' => [
                    'post_id' => $post->id,
                    'event_id' => $event->id,
                    'message' => "The post '{$post->title}' you reacted to is now an event!"
                ]
            ]);
        }
    }
}
```

---

### **Task 4: RecommendationEngine** (Priority: P1)

**File**: `app/Services/RecommendationEngine.php`

**Purpose**: Score content for personalized recommendations

**Methods to Implement**:

```php
class RecommendationEngine
{
    public function scoreContent(User $user, $content): float
    {
        $score = 0;
        
        // Location proximity (0-40 points)
        $distance = $this->calculateDistance($user, $content);
        $score += $this->locationScore($distance);
        
        // Interest match (0-30 points)
        $score += $this->interestScore($user, $content);
        
        // Social graph (0-20 points)
        $score += $this->socialScore($user, $content);
        
        // Temporal relevance (0-10 points)
        $score += $this->temporalScore($content);
        
        return $score;
    }
    
    public function getReasonForScore(User $user, $content): string
    {
        // Return human-readable reason
        // e.g., "Based on your interest in Basketball"
    }
}
```

---

## 🔗 Dependencies

**Wait for Agent C** (Days 1-2):
- Post, PostReaction, PostConversion models
- Activity model updated with `originated_from_post_id`

---

## ✅ Definition of Done

- [ ] FeedService generates nearby and personalized feeds
- [ ] PostService handles post creation, reactions, expiration
- [ ] ConversionService suggests and executes post-to-event conversion
- [ ] RecommendationEngine scores content for personalization
- [ ] All services have comprehensive Pest tests
- [ ] ExpirePostsJob runs hourly to expire old posts
- [ ] Notifications sent for conversion suggestions and completions

---

## 🚀 Start Date

**Day 3** (after Agent C completes models)

Good luck! 🔧

