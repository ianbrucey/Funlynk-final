# E04 Discovery Engine - Documentation Guide

## Epic Context
E04 focuses on **Posts** (ephemeral content) and discovery algorithms. This epic builds on E01's completed database foundation, specifically the `posts`, `post_reactions`, `post_conversions`, and `activities` tables.

**CRITICAL**: This epic handles the **Posts vs Events dual model**:
- **Posts**: Ephemeral (24-48h), spontaneous, tight radius (5-10km)
- **Events**: Structured, planned, wider radius (25-50km) - handled by E03

## Completed E01 Foundation

### Database Tables Available
- **posts**: id (uuid), user_id, content, location_name, location_coordinates (geography), expires_at, status, view_count, reaction_count, timestamps
- **post_reactions**: id, post_id, user_id, reaction_type (im_down/join_me/interested), timestamps
- **post_conversions**: id, post_id, activity_id, conversion_trigger, conversion_score, timestamps
- **activities**: id (uuid), originated_from_post_id (nullable), ... (see E03 guide)

### Models Available
- **Post**: `app/Models/Post.php` - includes spatial location, expiration tracking
- **PostReaction**: `app/Models/PostReaction.php` - tracks engagement
- **PostConversion**: `app/Models/PostConversion.php` - tracks post-to-event conversions
- **Activity**: `app/Models/Activity.php` - includes post origin tracking

### Filament Resources Available
- **PostResource**: `app/Filament/Resources/PostResource.php` - basic CRUD
- **PostReactionResource**: `app/Filament/Resources/PostReactionResource.php` - basic CRUD

---

## F01: Discovery Feed Service

### Feature Purpose
Provide location-based discovery feeds for posts and events, including "Nearby" feed, "For You" feed, and map view. Uses PostGIS for spatial queries and temporal decay for post ranking.

### Key Components to Document
1. **Nearby Feed**: PostGIS-powered proximity queries (5-10km for posts, 25-50km for events)
2. **For You Feed**: Personalized feed based on interests and social graph
3. **Map View**: Interactive map showing posts and events
4. **Temporal Decay**: Posts decay quickly (24-48h), events persist
5. **Feed Caching**: Redis caching for performance

### Suggested Tasks (6-7 tasks, 35-45 hours total)
- **T01**: Create DiscoveryFeedService with PostGIS queries (6-7 hours)
- **T02**: Implement temporal decay scoring algorithm (4-5 hours)
- **T03**: Build nearby feed Livewire component (5-6 hours)
- **T04**: Create For You feed with personalization (5-6 hours)
- **T05**: Implement map view with Leaflet/Mapbox (6-7 hours)
- **T06**: Build feed caching strategy with Redis (4-5 hours)
- **T07**: Create feed tests and performance optimization (4-5 hours)

### Integration Points
- Uses `posts` and `activities` tables from E01
- Uses PostGIS for spatial queries
- Integrates with user interests and social graph (E02)
- Uses Redis for caching

### Critical Notes
- **Different radii**: Posts use 5-10km radius, events use 25-50km radius
- **Temporal decay**: Posts expire after 24-48h, events persist until start_time
- Use matanyadaev/laravel-eloquent-spatial for spatial queries
- Example query: `Post::whereDistance('location_coordinates', $point, '<=', 10000)->where('expires_at', '>', now())`
- Cache feed results for 5-10 minutes (Redis)

### Spatial Query Examples
```php
// Nearby posts (5-10km radius)
$nearbyPosts = Post::query()
    ->whereDistance('location_coordinates', $userLocation, '<=', 10000)
    ->where('expires_at', '>', now())
    ->where('status', 'active')
    ->orderBy('created_at', 'desc')
    ->limit(50)
    ->get();

// Nearby events (25-50km radius)
$nearbyEvents = Activity::query()
    ->whereDistance('location_coordinates', $userLocation, '<=', 50000)
    ->where('start_time', '>', now())
    ->where('status', 'published')
    ->orderBy('start_time', 'asc')
    ->limit(50)
    ->get();
```

---

## F02: Recommendation Engine

### Feature Purpose
Provide intelligent recommendations for posts and events based on multi-factor scoring: recency, location proximity, interest match, and social boost. Handles cold start for new users.

### Key Components to Document
1. **Multi-Factor Scoring**: Combine recency, proximity, interests, social signals
2. **Temporal Intelligence**: Posts decay quickly, events persist
3. **Interest Matching**: JSON field queries for user interests
4. **Social Boost**: Boost content from followed users
5. **Cold Start Handling**: Recommendations for new users without history

### Suggested Tasks (6-7 tasks, 30-40 hours total)
- **T01**: Create RecommendationService with scoring algorithm (6-7 hours)
- **T02**: Implement interest matching logic (4-5 hours)
- **T03**: Build social boost calculation (3-4 hours)
- **T04**: Create cold start handling (4-5 hours)
- **T05**: Implement recommendation caching (3-4 hours)
- **T06**: Build recommendation analytics (4-5 hours)
- **T07**: Create recommendation tests and optimization (4-5 hours)

### Integration Points
- Uses `posts`, `activities`, `post_reactions` tables
- Integrates with user interests (E02)
- Uses `follows` table for social boost
- Uses Redis for caching

### Critical Notes
- **Scoring formula**: `score = (recency_score × 0.3) + (proximity_score × 0.3) + (interest_score × 0.2) + (social_score × 0.2)`
- **Recency score**: Posts decay exponentially (24-48h), events decay linearly until start_time
- **Proximity score**: Inverse distance (closer = higher score)
- **Interest score**: Jaccard similarity between user interests and post/event tags
- **Social score**: Boost if creator is followed, or if followed users reacted

### Scoring Algorithm Example
```php
public function calculateScore(Post|Activity $item, User $user): float
{
    $recencyScore = $this->calculateRecencyScore($item);
    $proximityScore = $this->calculateProximityScore($item, $user);
    $interestScore = $this->calculateInterestScore($item, $user);
    $socialScore = $this->calculateSocialScore($item, $user);
    
    return ($recencyScore * 0.3) + 
           ($proximityScore * 0.3) + 
           ($interestScore * 0.2) + 
           ($socialScore * 0.2);
}
```

---

## F03: Social Resonance & Post Evolution

### Feature Purpose
Track social engagement on posts and trigger post-to-event conversion when posts gain traction. This is the core of the "spontaneous → structured" evolution.

### Key Components to Document
1. **Reaction Tracking**: "I'm down", "Join me", "Interested" reactions
2. **Conversion Detection**: Monitor reaction thresholds for conversion
3. **Post-to-Event Conversion**: Trigger E03 to create activity from post
4. **Conversion Analytics**: Track conversion rates and patterns
5. **Social Interaction UI**: Livewire components for reactions

### Suggested Tasks (6-7 tasks, 30-40 hours total)
- **T01**: Create SocialResonanceService for reaction tracking (4-5 hours)
- **T02**: Implement conversion detection logic (5-6 hours)
- **T03**: Build post-to-event conversion trigger (4-5 hours)
- **T04**: Create reaction Livewire components (5-6 hours)
- **T05**: Implement conversion analytics (4-5 hours)
- **T06**: Build conversion notifications (3-4 hours)
- **T07**: Create social resonance tests (3-4 hours)

### Integration Points
- Uses `post_reactions` and `post_conversions` tables
- **E03 Integration**: Calls E03's ActivityConversionService to create activity
- Uses E01 notifications for conversion alerts
- Tracks conversion in `post_conversions` table

### Critical Notes
- **Conversion thresholds**: 
  - 5+ "I'm down" reactions within 2 hours → suggest conversion
  - 10+ "I'm down" reactions within 4 hours → auto-convert (with user approval)
- **Conversion flow**:
  1. E04 detects high engagement on post
  2. E04 calls `ActivityConversionService::createFromPost($post)`
  3. E03 creates activity with `originated_from_post_id = $post->id`
  4. E04 records conversion in `post_conversions` table
  5. E04 notifies post creator to complete activity details

### Post-to-Event Conversion Flow
```php
// E04: Detect conversion trigger
if ($post->reactions()->where('reaction_type', 'im_down')->count() >= 5) {
    // E04: Call E03's conversion service
    $activity = app(ActivityConversionService::class)->createFromPost($post);
    
    // E04: Record conversion
    PostConversion::create([
        'post_id' => $post->id,
        'activity_id' => $activity->id,
        'conversion_trigger' => 'high_engagement',
        'conversion_score' => $this->calculateConversionScore($post),
    ]);
    
    // E04: Notify creator
    $post->user->notify(new PostConvertedToEventNotification($post, $activity));
}
```

---

## Common Patterns for E04

### Service Classes
```bash
php artisan make:class Services/DiscoveryFeedService --no-interaction
php artisan make:class Services/NearbyFeedService --no-interaction
php artisan make:class Services/ForYouFeedService --no-interaction
php artisan make:class Services/RecommendationService --no-interaction
php artisan make:class Services/ScoringService --no-interaction
php artisan make:class Services/SocialResonanceService --no-interaction
php artisan make:class Services/ConversionDetectionService --no-interaction
```

### Livewire Components
```bash
php artisan make:livewire Discovery/NearbyFeed --no-interaction
php artisan make:livewire Discovery/ForYouFeed --no-interaction
php artisan make:livewire Discovery/MapView --no-interaction
php artisan make:livewire Post/PostReactions --no-interaction
php artisan make:livewire Post/ConversionPrompt --no-interaction
```

### Jobs (for async processing)
```bash
php artisan make:job ProcessTemporalDecay --no-interaction
php artisan make:job UpdateRecommendations --no-interaction
php artisan make:job DetectPostConversions --no-interaction
php artisan make:job CacheFeedResults --no-interaction
```

### Commands (for scheduled tasks)
```bash
php artisan make:command ExpireOldPosts --no-interaction
php artisan make:command UpdateRecommendationCache --no-interaction
php artisan make:command DetectConversionOpportunities --no-interaction
```

### Tests
```bash
php artisan make:test --pest Feature/DiscoveryFeedTest --no-interaction
php artisan make:test --pest Feature/RecommendationEngineTest --no-interaction
php artisan make:test --pest Feature/SocialResonanceTest --no-interaction
php artisan make:test --pest Feature/PostConversionTest --no-interaction
php artisan make:test --pest Feature/TemporalDecayTest --no-interaction
```

---

## Key Packages for E04

- **matanyadaev/laravel-eloquent-spatial**: PostGIS integration for spatial queries
- **predis/predis**: Redis client for caching
- **leaflet or mapbox**: Map visualization (frontend)
- **laravel/horizon** (optional): Queue monitoring for async jobs

---

## Testing Checklist for E04

### Discovery Feed
- [ ] Nearby feed returns posts within 5-10km
- [ ] Nearby feed returns events within 25-50km
- [ ] For You feed personalizes based on interests
- [ ] Map view displays posts and events correctly
- [ ] Temporal decay removes expired posts
- [ ] Feed caching improves performance

### Recommendation Engine
- [ ] Multi-factor scoring calculates correctly
- [ ] Interest matching works with JSON fields
- [ ] Social boost increases scores for followed users
- [ ] Cold start provides recommendations for new users
- [ ] Recommendation caching works correctly
- [ ] Recommendations update periodically

### Social Resonance & Conversion
- [ ] Reactions are tracked correctly
- [ ] Conversion detection triggers at thresholds
- [ ] Post-to-event conversion creates activity
- [ ] Conversion is recorded in post_conversions table
- [ ] Conversion notifications are sent
- [ ] Conversion analytics track patterns

