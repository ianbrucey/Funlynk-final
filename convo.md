
   FeedService is now built per the spec, integrated into all three Discovery Livewire components, and covered by Pest tests. I
   also implemented RecommendationEngine because FeedService depends on it.
   Below is what’s in place and how Agent A can rely on it.

   ────────────────────────────────────────────────────────

1. New services

   1.1 App\Services\FeedService
   Implements the exact API from your task doc:

   app/Services/FeedService.php (EXCERPT)
   class FeedService
   {
       public function getNearbyFeed(
           User $user,
           int $radius = 10,
           string $contentType = 'all',
           string $timeFilter = 'all',
       ): Collection { /* ... */ }
       public function getForYouFeed(User $user): Collection
       { /* ... */ }
       public function getMapData(
           User $user,
           int $radius = 10,
           string $contentType = 'all',
       ): array { /* ... */ }
   }

   Key behavior:

    • Spatial logic (PostGIS via Eloquent Spatial)
        • Posts: Post::active()->whereDistance('location_coordinates',$userLocation, '<=', min($radius, 10) * 1000)
           • Posts are capped at 10 km even if a larger radius is passed.
        • Events: Activity::where('status', 'published')->where('start_time','>', now())->whereDistance('location_coordinates',
          $userLocation, '<=', $radius * 1000)
        • Both models use HasSpatial and Point casts.

    • Time filters (`$timeFilter`)
     For both posts (created_at) and events (start_time):
        • today → whereDate(..., today)
        • week → >= now()->subWeek()
        • month → >= now()->subMonth()
        • all → no extra constraint.

    • getNearbyFeed(User, radius, contentType, timeFilter)
        • Mixes posts (type => 'post') and events (type => 'event') into a single Collection of:
           • ['type' => 'post'|'event', 'data' => <Post|Activity>]
        • Sorting: posts are boosted:
           • Posts: created_at->timestamp + 100000
           • Events: start_time->timestamp
        • If user has no location_coordinates, falls back to a recency-based feed without geo filter but still honors contentType
          and timeFilter.
     • getForYouFeed(User)
        • Uses the new RecommendationEngine (see below).
        • Candidate sets (if location_coordinates present):
           • Posts: within 10 km
           • Events: within 50 km
        • Returns a Collection of up to 50 items shaped as:
           • ['type' => 'post'|'event', 'data' => <Post|Activity>, 'score' => float, 'reason' => string]
        • If user has no location, falls back to non-spatial recent content (limits 50 each).
     • getMapData(User, radius, contentType)
        • Returns:

   app/Services/FeedService.php (EXCERPT)
       return [
           'markers' => [
               [
                   'type' => 'post',
                   'id' => $post->id,
                   'lat' => $post->location_coordinates->latitude,
                   'lng' => $post->location_coordinates->longitude,
                   'title' => $post->title,
                   'timeHint' => $post->time_hint,
                   'reactionCount' => $post->reaction_count,
                   'expiresAt' => optional($post->expires_at)->toIso8601String(),
               ],
               // or: 'type' => 'event', 'startTime', 'priceCents', 'spotsRemaining', 'convertedFromPost'
           ],
           'center' => [
               'lat' => $userLocation->latitude,
               'lng' => $userLocation->longitude,
           ],
       ];

    • Uses same distance caps as getNearbyFeed.
        • If the user has no location, uses a San Francisco fallback point as in your docs.

   ───────────────────────────────────────────────────────────────────

   1.2 App\Services\RecommendationEngine

   Implements scoring and “why you’re seeing this” text per your spec.
   app/Services/RecommendationEngine.php (EXCERPT)
   class RecommendationEngine
   {
       public function scoreContent(User $user, Post|Activity $content): float
       {
           return $this->locationScore($user, $content)   // 0–40
                + $this->interestScore($user, $content)   // 0–30
                + $this->socialScore($user, $content)     // 0–20 (placeholder)
                + $this->temporalScore($content);         // 0–10
       }
       public function getReasonForScore(User $user, Post|Activity $content): string
       { /* ... interest / location / temporal reasons ... */ }
   }

   Details:

    • Location (0–40 points)
        • Uses lat/lng from location_coordinates and rough km buckets (≤2, ≤5, ≤10, ≤25, ≤50, >50).
     • Interest (0–30 points)
        • Works with User::interests and $content->tags, both treated as flattened arrays:
           • collect(...)->flatten()->filter()->all() before array_intersect to avoid “array to string conversion” issues.
        • 1/2/3+ tag matches = 15/22/30 points; otherwise small base (5 or 10).
     • Social (0–20 points)
        • Currently a 10-point placeholder, ready for follow/friend/RSVP graph integration.
     • Temporal (0–10 points)
        • Posts: decaying by hours since created_at (fresh posts get more).
        • Activities: based on hours until start_time (soon/upcoming events score higher; past = 0).
     • Reasons:
        • “Based on your interest in X” if any tag/interests overlap.
        • “Popular near you” if both have coordinates.
        • “Happening soon in your area” (post) or “Upcoming event that matches your vibe” (event).
        • Fallback: “Recommended for you”.
   FeedService just consumes scoreContent + getReasonForScore.

   ───────────────────────────────────────────────────────────

2. Model updates for spatial queries
   To make whereDistance work for events:

    • Post was already using HasSpatial and Point cast.
     • Activity now also uses HasSpatial:

   app/Models/Activity.php (EXCERPT)
   use MatanYadaev\EloquentSpatial\Traits\HasSpatial;
   class Activity extends Model
   {
       use HasFactory;
       use HasUuids;
       use HasSpatial;
       protected function casts(): array
       {
           return [
               'location_coordinates' => Point::class,
               // ...
           ];
       }
   }

   This fixed the earlier SQL error and lets whereDistance generate the correct ST_DISTANCE(...) expressions.

   ───────────────────────────────────────────────────────────────────────────────────────

3. Livewire integration (Agent A UI now uses your services)

   All three Discovery components now call FeedService instead of doing their own queries.
   3.1 NearbyFeed

   app/Livewire/Discovery/NearbyFeed.php (EXCERPT)
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

    • items is the mixed posts/events collection from FeedService (with type + data).

   3.2 ForYouFeed
   app/Livewire/Discovery/ForYouFeed.php (EXCERPT)
   public function render()
   {
       $user = auth()->user();
       $items = app(\App\Services\FeedService::class)->getForYouFeed($user);
       return view('livewire.discovery.for-you-feed', [
           'items' => $items,
       ])->layout('layouts.app', ['title' => 'For You']);
   }

    • The UI now gets the scored and reason-annotated items emitted by RecommendationEngine via FeedService.
   3.3 MapView

   app/Livewire/Discovery/MapView.php (EXCERPT)
   public function getMapData()
   {
       $user = auth()->user();
       $data = app(\App\Services\FeedService::class)->getMapData(
           $user,
           radius: (int) $this->radius,
           contentType: (string) $this->contentType,
       );
       return $data['markers'];
   }

    • Blade continues to receive an array of markers; center is computed but currently only used inside the service (ready if you
       later want to pass it through).

   ──────────────────────────────────────

4. Tests

   New feature tests:

    • tests/Feature/FeedServiceTest.php

   They cover:

    1. Nearby feed mixing posts/events
        • User at SF Point(37.7749, -122.4194, 4326).
        • One nearby post, one far post, one nearby event.
        • getNearbyFeed returns both nearby items with expected types.

    2. For You feed with RecommendationEngine
        • User with interests => ['sports'].
        • Post tagged ['sports'] + nearby event.
        • getForYouFeed returns a non-empty collection of items containing:
           • type, data, score, reason keys.

    3. Map data
        • Nearby post and event created with Point coordinates.
        • getMapData returns:
           • ['markers' => [...], 'center' => ['lat' => float, 'lng' => float]]
           • Markers include both post and event IDs and types.

   All three tests pass:

    • php artisan test --filter=FeedServiceTest --testdox
     → 3 tests, 19 assertions, all passing.

   (Existing PostServiceTest still passes as before.)

   ──────────────────────────────────────────────────
   5. What’s unblocked and what’s next

   Unblocked now:

    • Agent A’s:
        • Nearby Feed
        • For You Feed
        • Map View

   …are all backed by real data with:

    • PostGIS-based proximity.
     • 24–48h post lifetime already enforced by PostService + ExpirePostsJob.
     • Personalized scoring + “why you’re seeing this”.

   Next services (per your list):

    2. ConversionService (HIGH) – listen to PostReacted, check thresholds, create Activity via existing ActivityService, write
        PostConversion, and notify host + reactors.
     3. Finalize RecommendationEngine (it’s already usable; we can iterate scoring weights/graph integration).
     4. ExpirePostsJob – you already have a DI-based job; we can align it exactly to the log spec (log message + schedule wiring).
   If you’d like, I can move straight on to ConversionService next, hooking it into PostReacted and adding Pest tests around the
   5+/10+ thresholds and notifications.
