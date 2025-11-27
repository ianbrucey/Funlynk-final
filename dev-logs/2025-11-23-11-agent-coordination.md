# Agent Coordination Plan - E04 Discovery Engine Implementation

**Date**: 2025-11-23 11:00 AM  
**Sprint**: E04 Discovery Engine - Posts vs Events Architecture  
**Duration**: 2-3 weeks  
**Strategy**: Parallel implementation across 3 agents

---

## 🎯 Sprint Goal

Implement FunLynk's **core differentiator**: The Posts vs Events dual content model with discovery feeds, map view, and post-to-event conversion.

**Success Criteria**:
- ✅ Users can create ephemeral Posts (24-48h lifespan)
- ✅ Users can discover Posts and Events via Nearby Feed, For You Feed, and Map View
- ✅ Posts with 5+ reactions suggest conversion to Event
- ✅ Posts with 10+ reactions auto-convert to Event
- ✅ Full user journey: Create Post → Get reactions → Convert to Event → Accept RSVPs → Get paid

---

## 👥 Agent Assignments

### **Agent A (UI/UX Specialist)** - Discovery Feeds & Map View

**Primary Focus**: User-facing discovery interfaces

**Tasks**:
1. **Nearby Feed UI** (`/feed/nearby`)
   - Livewire component: `app/Livewire/Discovery/NearbyFeed.php`
   - View: `resources/views/livewire/discovery/nearby-feed.blade.php`
   - Card components for Posts vs Events (different styling)
   - Infinite scroll with Livewire pagination
   - Real-time updates (posts expire, new posts appear)

2. **For You Feed UI** (`/feed/for-you`)
   - Livewire component: `app/Livewire/Discovery/ForYouFeed.php`
   - View: `resources/views/livewire/discovery/for-you-feed.blade.php`
   - Personalized recommendations
   - "Why you're seeing this" explanations

3. **Map View UI** (`/map`)
   - Livewire component: `app/Livewire/Discovery/MapView.php`
   - View: `resources/views/livewire/discovery/map-view.blade.php`
   - Google Maps integration with custom markers
   - Different pins for Posts (ephemeral) vs Events (structured)
   - Click pin → Show preview card → Navigate to detail

4. **Post Card Component**
   - Reusable component for displaying Posts in feeds
   - "I'm down" and "Join me" reaction buttons
   - Expiration countdown timer
   - Conversion badge (if originated from post)

**Dependencies**:
- Agent B's FeedService and PostService
- Agent C's Post model and reactions table

**Deliverables**:
- 3 Livewire components with galaxy-themed views
- Post card component with reactions
- Map view with custom markers
- Mobile-responsive (edge-to-edge on mobile, rounded on desktop)

---

### **Agent B (Backend Specialist)** - Services & Business Logic

**Primary Focus**: Feed algorithms, recommendations, conversion logic

**Tasks**:
1. **FeedService** (`app/Services/FeedService.php`)
   - `getNearbyFeed($user, $radius)` - Geo-proximity feed (5-10km posts, 25-50km events)
   - `getForYouFeed($user)` - Personalized feed with scoring algorithm
   - `getMapData($user, $bounds)` - Posts/Events within map bounds
   - Temporal decay for posts (newer = higher rank)
   - Mix posts and events in feeds

2. **PostService** (`app/Services/PostService.php`)
   - `createPost($data)` - Create ephemeral post
   - `reactToPost($postId, $reactionType)` - "I'm down" / "Join me"
   - `checkConversionEligibility($postId)` - 5+ reactions?
   - `expirePosts()` - Auto-expire posts after 24-48h
   - `getPostReactions($postId)` - Get all reactions

3. **ConversionService** (`app/Services/ConversionService.php`)
   - `suggestConversion($postId)` - Notify host at 5+ reactions
   - `convertPostToEvent($postId, $additionalData)` - Create Event from Post
   - `autoConvert($postId)` - Auto-convert at 10+ reactions
   - `trackConversion($postId, $eventId)` - Record in post_conversions table

4. **RecommendationEngine** (`app/Services/RecommendationEngine.php`)
   - `scoreContent($user, $content)` - Scoring algorithm
   - `getPersonalizedRecommendations($user)` - ML-ready recommendations
   - Factors: location, interests, social graph, past RSVPs

**Dependencies**:
- Agent C's database tables and models
- Existing ActivityService and PaymentService

**Deliverables**:
- 4 service classes with comprehensive business logic
- Feed algorithms with temporal decay
- Conversion detection and automation
- Recommendation scoring system

---

### **Agent C (Database/Infrastructure Specialist)** - Schema & Models

**Primary Focus**: Posts table, reactions, conversions, models

**Tasks**:
1. **Posts Migration** (`database/migrations/YYYY_MM_DD_create_posts_table.php`)
   - Already exists from E01, verify schema:
   - `id`, `user_id`, `title`, `description`, `location_coordinates` (geography)
   - `location_name`, `time_hint`, `expires_at`, `status`
   - `reaction_count`, `conversion_suggested_at`, `converted_to_activity_id`
   - Indexes: `location_coordinates` (GIST), `expires_at`, `user_id`

2. **Post Reactions Migration** (`database/migrations/YYYY_MM_DD_create_post_reactions_table.php`)
   - Already exists from E01, verify schema:
   - `id`, `post_id`, `user_id`, `reaction_type` (enum: 'im_down', 'join_me')
   - `created_at`
   - Unique constraint: `(post_id, user_id)`

3. **Post Conversions Migration** (`database/migrations/YYYY_MM_DD_create_post_conversions_table.php`)
   - Already exists from E01, verify schema:
   - `id`, `post_id`, `activity_id`, `converted_at`, `conversion_type` (enum: 'manual', 'auto')
   - `reaction_count_at_conversion`

4. **Post Model** (`app/Models/Post.php`)
   - Relationships: `user()`, `reactions()`, `convertedActivity()`
   - Scopes: `active()`, `expired()`, `nearUser($lat, $lng, $radius)`
   - Casts: `location_coordinates` (Point), `expires_at` (datetime)
   - Methods: `isExpired()`, `canConvert()`, `getReactionCount()`

5. **PostReaction Model** (`app/Models/PostReaction.php`)
   - Relationships: `post()`, `user()`
   - Validation: Only 'im_down' or 'join_me'

6. **PostConversion Model** (`app/Models/PostConversion.php`)
   - Relationships: `post()`, `activity()`
   - Track conversion metrics

7. **Update Activity Model** (`app/Models/Activity.php`)
   - Add relationship: `originatedFromPost()`
   - Add scope: `convertedFromPost()`

**Dependencies**:
- E01 Core Infrastructure (PostGIS setup)
- Existing User and Activity models

**Deliverables**:
- 3 database migrations (verify/update existing)
- 3 new models with relationships and scopes
- Updated Activity model with post relationship
- Factories and seeders for testing

---

## 📋 Implementation Order

### **Week 1: Foundation**
**All Agents Start Together**

**Day 1-2: Agent C** - Database & Models
- ✅ Verify/update Posts, PostReactions, PostConversions migrations
- ✅ Create Post, PostReaction, PostConversion models
- ✅ Update Activity model with post relationship
- ✅ Create factories and seeders
- ✅ Run migrations and seed test data

**Day 3-4: Agent B** - Core Services
- ✅ Build PostService (create, react, expire)
- ✅ Build FeedService (nearby, for you, map data)
- ✅ Build ConversionService (suggest, convert, auto-convert)
- ✅ Write Pest tests for all services

**Day 5-7: Agent A** - UI Components
- ✅ Build Nearby Feed UI
- ✅ Build Post Card component with reactions
- ✅ Build Map View with custom markers
- ✅ Test on mobile and desktop

### **Week 2: Integration & Polish**

**Day 8-10: All Agents** - Integration
- Agent A: Connect UI to Agent B's services
- Agent B: Optimize feed algorithms and conversion logic
- Agent C: Add indexes, optimize queries, performance testing

**Day 11-12: All Agents** - Testing & Refinement
- End-to-end testing: Create Post → React → Convert → RSVP → Pay
- Mobile responsiveness testing
- Performance optimization (feed load < 1s)

**Day 13-14: All Agents** - Documentation & Handoff
- Update dev logs
- Write user documentation
- Create demo video/screenshots

---

## 🔗 Integration Points

### Agent A ↔ Agent B
- Agent A calls Agent B's FeedService methods
- Agent A calls Agent B's PostService for reactions
- Agent A displays conversion suggestions from Agent B

### Agent B ↔ Agent C
- Agent B uses Agent C's Post, PostReaction models
- Agent B queries Agent C's database tables
- Agent B triggers Agent C's model events

### Agent A ↔ Agent C
- Agent A uses Agent C's models for Livewire properties
- Agent A displays Agent C's model data in views

---

## 📚 Key Documentation

**Read Before Starting**:
1. `context-engine/epics/E04_Discovery_Engine/epic-overview.md`
2. `context-engine/epics/E04_Discovery_Engine/posts-vs-events-architecture.md`
3. `context-engine/tasks/E04_Discovery_Engine/README.md`
4. `context-engine/domain-contexts/ui-design-standards.md` (Agent A)
5. `context-engine/domain-contexts/database-context.md` (Agent C)

---

## ✅ Definition of Done

**Sprint Complete When**:
- [ ] Users can create Posts with location and time hints
- [ ] Nearby Feed shows Posts (5-10km) and Events (25-50km)
- [ ] For You Feed shows personalized recommendations
- [ ] Map View displays Posts and Events with custom markers
- [ ] Users can react to Posts ("I'm down", "Join me")
- [ ] Posts with 5+ reactions show conversion suggestion to host
- [ ] Posts with 10+ reactions auto-convert to Event
- [ ] Converted Events have `originated_from_post_id` set
- [ ] Posts expire after 24-48h automatically
- [ ] All features work on mobile (edge-to-edge) and desktop (rounded cards)
- [ ] Feed loads in < 1 second
- [ ] All Pest tests pass
- [ ] Galaxy theme applied consistently

---

## 🚀 Let's Ship It!

**Next Steps**:
1. Each agent reads their assigned tasks
2. Agent C starts migrations/models (Day 1-2)
3. Agent B starts services once models ready (Day 3-4)
4. Agent A starts UI once services ready (Day 5-7)
5. Daily sync: Share progress, blockers, integration needs

**Communication**:
- Update dev logs daily with progress
- Flag blockers immediately
- Share completed work for integration testing

---

**Sprint Start**: 2025-11-23  
**Sprint End**: 2025-12-06 (2 weeks)  
**Let's build the core of FunLynk! 🎉**

