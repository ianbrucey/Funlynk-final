# E04 Discovery Engine - Epic Overview

## Epic Purpose

The Discovery Engine epic transforms FunLynk from a traditional event platform into a **spontaneous, niche-discovery social network**. This epic enables users to discover hard-to-find activities through real-time "energy signals" (Posts), location-aware feeds, and implicit community formation—connecting people with spontaneous experiences they'd never find on Meetup or Eventbrite.

**Core Differentiator**: "From Events to Energy" - We treat posts as spontaneous invitations to connect, not transactional event registrations.

## Epic Scope

### In Scope
- **Posts vs Events Dual Model**: Lightweight posts that can evolve into structured events
- **Discovery Feed Service**: Real-time nearby feed, personalized "For You" feed, and interactive map view
- **Temporal Intelligence**: Posts fade quickly (24-48h), events persist until completion
- **Social Resonance**: "I'm down" / "Join me" interactions instead of passive likes
- **Implicit Communities**: Auto-generated clusters from activity patterns (e.g., "Atlanta Musicians")
- **Post-to-Event Evolution**: Conversion flow when posts gain traction
- **Recommendation Engine**: Multi-factor scoring with temporal decay for posts

### Out of Scope
- Basic event/activity CRUD (handled by E03 Activity Management)
- User profile data (handled by E02 User & Profile Management)
- Advanced social features like comments/shares (handled by E05 Social Interaction)
- Payment processing for events (handled by E06 Payments & Monetization)
- AI-powered vibe matching (Phase 2 / v2 feature)

## Core Differentiator: Posts vs Events Dual Model

### What Makes FunLynk Different

**The Problem**: Niche activities (jam sessions at random churches, pickup basketball, spontaneous meetups) are invisible unless you're "in the circle." Meetup and Eventbrite focus on professional, structured events—not spontaneous, community-driven experiences.

**FunLynk's Solution**: A dual content model that treats discovery as "energy signals" rather than event transactions.

### Posts: Spontaneous Energy Signals
**Purpose**: Lightweight, conversational, real-time "what's happening" updates

**Characteristics**:
- **Ephemeral**: Auto-expire after 24-48 hours
- **Conversational**: "Open jam at Clark ATL tonight 🎸", "Anyone down for pickup basketball?"
- **Spontaneous**: Created on-the-fly, minimal friction
- **Evolvable**: Can convert to structured events if they gain traction
- **Location-aware**: Geo-tagged for proximity discovery
- **Mood-tagged**: Creative, social, active, chill, etc.

**Examples**:
- "Jam session tonight at 8pm, bring your guitar"
- "Anyone want to hike Arabia Mountain this weekend?"
- "Pickup basketball at Piedmont Park in 30 mins"
- "Coffee and coding at Octane, who's in?"

### Events: Structured Experiences
**Purpose**: Time-anchored activities with RSVPs, payments, and formal planning

**Characteristics**:
- **Persistent**: Remain active until event completion
- **Structured**: Clear date/time, location, capacity, pricing
- **Formal RSVPs**: Confirmed attendance tracking
- **Payment-enabled**: Can charge admission via Stripe
- **Originated from**: Either created directly OR evolved from popular posts

**Examples**:
- "Jazz Night @ High Museum — $10 cover, Friday 8pm"
- "5K Charity Run — Register by March 1st"
- "Photography Workshop — $50, limited to 15 people"

### Post-to-Event Evolution Flow
**The Magic**: Posts that gain traction can evolve into structured events

**Conversion Triggers**:
- Multiple "I'm down" reactions (threshold: 5+)
- Host manually converts post to event
- Time-sensitive posts approaching expiration with high engagement

**Conversion Process**:
1. Post gains traction (reactions, comments, shares)
2. System suggests "Convert to Event" to host
3. Host adds structure (exact time, capacity, pricing)
4. Post becomes event, original post links to event
5. Engaged users auto-notified of conversion

## Component Breakdown

### 4.1 Discovery Feed Service (formerly "Search Service")
**Purpose**: Real-time discovery of posts and events through location-aware, interest-based feeds

**Responsibilities**:
- **Nearby Feed**: "What's happening within 5 miles right now"
- **For You Feed**: Posts/events matching user interests with temporal decay
- **Map View**: Interactive map with real-time pins for posts/events
- **Post Discovery**: Geo-proximity + interest matching + temporal weighting
- **Event Discovery**: Traditional search with advanced filtering
- **Saved Searches**: Notification preferences for specific activity types

**Key Features**:
- Real-time geo-proximity feed (PostGIS-powered)
- Temporal decay scoring (recent posts rank higher)
- Interest-based content matching
- Interactive map with tap-to-open post/event details
- Filter by category, time, cost, friends attending
- Post expiration handling (24-48h auto-removal)

### 4.2 Recommendation Engine
**Purpose**: Personalized post/event recommendations with temporal intelligence and vibe matching

**Responsibilities**:
- **Temporal Weighting**: Recent posts rank higher than older posts
- **Interest Matching**: Tag-based affinity scoring
- **Location Intelligence**: Proximity-based relevance
- **Social Signals**: Friend engagement amplification
- **Vibe Matching**: Mood + tags + location + time-of-day patterns
- **Collaborative Filtering**: "Users like you also enjoyed..."
- **Trending Detection**: Velocity-based trending posts/events

**Key Features**:
- **Scoring Formula**: `score = (recency * location_proximity * interest_match * social_boost)`
- Temporal decay for posts (24-48h lifecycle)
- Persistent scoring for events (no decay)
- Real-time recommendation updates
- Diversity optimization to avoid filter bubbles
- Explainable recommendations ("Because you're into jazz + nearby")
- Cold start handling for new users

### 4.3 Social Resonance & Post Evolution Service (formerly "Feed Generation")
**Purpose**: Enable spontaneous social connections and post-to-event evolution

**Responsibilities**:
- **Social Resonance Interactions**: "I'm down" / "Join me" buttons instead of passive likes
- **Quick Connection**: Instant DM or group chat initiation from posts
- **Post-to-Event Conversion**: Algorithms and UI for evolution flow
- **Engagement Tracking**: Monitor post traction for conversion suggestions
- **Signal Amplification**: Boost posts when friends engage
- **Group Formation**: Facilitate micro-groups from recurring posts

**Key Features**:
- "I'm down" button with instant notification to host
- "Join me" button to invite friends to post
- Quick chat initiation from post (1-tap DM or group chat)
- Post-to-event conversion UI with traction metrics
- Automatic conversion suggestions when posts hit thresholds
- Friend engagement indicators ("3 friends are interested")
- Micro-group suggestions for recurring post patterns

### 4.4 Implicit Communities (Phase 2 / v2)
**Purpose**: Auto-generate communities from activity patterns without manual group creation

**Responsibilities**:
- **Pattern Detection**: Identify clusters of users with shared tags + locations
- **Community Naming**: Auto-generate names (e.g., "Atlanta Musicians", "Midtown Runners")
- **Member Suggestions**: Recommend users join discovered communities
- **Community Feeds**: Curated feeds for each implicit community
- **Growth Tracking**: Monitor community health and engagement

**Key Features**:
- Clustering algorithm based on tags, location, and engagement patterns
- Auto-generated community names and descriptions
- Community discovery UI ("You might like: Atlanta Musicians")
- Community-specific feeds and recommendations
- No manual group creation required—communities emerge organically

## Dependencies

### External Dependencies
- **E01 Core Infrastructure**: Database (PostGIS), geolocation, notifications, caching (Redis)
- **E02 User & Profile Management**: User profiles, interests, social graph, location data
- **E03 Activity Management**: Event data, tags, RSVP information
- **Real-time Infrastructure**: WebSocket support for live post updates

### Internal Dependencies
- **Posts table** (NEW): Ephemeral content with expiration and evolution tracking
- **Events/Activities table**: Structured events (existing from E03)
- **Post Reactions table** (NEW): "I'm down" / "Join me" interactions
- **Activity Clusters table** (NEW): Implicit communities data
- **Users table**: User profiles and interests for personalization
- **Follows table**: Social graph for social recommendations
- **RSVPs table**: User behavior data for collaborative filtering
- **Tags table**: Activity categorization for filtering and matching

## Success Criteria

### Discovery Feed Service
- [ ] Nearby feed returns results in under 200ms for 95% of queries
- [ ] Temporal decay scoring prioritizes posts created within last 6 hours
- [ ] Map view loads and displays 100+ pins without performance degradation
- [ ] Post discovery → engagement rate above 30% ("I'm down" clicks)
- [ ] Geo-proximity accuracy within 100m for location-based discovery
- [ ] Feed handles 10,000+ concurrent users without degradation

### Recommendation Engine
- [ ] Post recommendations prioritize content created within last 24 hours
- [ ] Recommendation click-through rate above 25%
- [ ] Recommendation → "I'm down" conversion rate above 15%
- [ ] Users engage with recommended posts 3x more than random posts
- [ ] Temporal weighting improves post engagement by 40% vs static scoring
- [ ] Cold start recommendations work effectively for new users
- [ ] Recommendation latency under 100ms for real-time updates

### Social Resonance & Post Evolution
- [ ] "I'm down" button response time under 100ms
- [ ] Post-to-event conversion rate above 10% for posts with 5+ reactions
- [ ] Quick chat initiation works within 2 taps from post
- [ ] Friend engagement signals increase post visibility by 50%
- [ ] Post expiration system removes 95%+ of expired posts within 1 hour
- [ ] Conversion suggestions appear when posts hit engagement thresholds

### Implicit Communities (Phase 2)
- [ ] Community detection identifies 80%+ of recurring activity patterns
- [ ] Auto-generated community names are relevant 90%+ of the time
- [ ] Community recommendations increase user engagement by 25%
- [ ] Community feeds maintain 70%+ user satisfaction

## Acceptance Criteria

### Technical Requirements
- [ ] Posts table with auto-expiration (24-48h) implemented and tested
- [ ] Geo-proximity queries (PostGIS) return results in under 200ms
- [ ] Temporal decay scoring algorithm implemented for posts
- [ ] Post-to-event conversion tracking and analytics in place
- [ ] Real-time post updates via WebSocket for live feeds
- [ ] Discovery feeds scale to 100K+ concurrent users
- [ ] Recommendation algorithms process user data in real-time
- [ ] All services maintain 99.9% uptime during peak usage

### User Experience Requirements
- [ ] Nearby feed shows real-time posts within configurable radius (default 5 miles)
- [ ] For You feed balances posts and events based on user preferences
- [ ] Map view displays posts/events with tap-to-open details
- [ ] "I'm down" button provides instant feedback and notifications
- [ ] Post-to-event conversion UI is intuitive and frictionless
- [ ] Post expiration is transparent to users (countdown timers)
- [ ] Mobile experience optimized for spontaneous post creation
- [ ] Discovery features help users find niche activities in their area

### Integration Requirements
- [ ] Posts integrate with E05 Social Interaction (comments, shares)
- [ ] Post reactions ("I'm down") trigger notifications via E01
- [ ] Post-to-event conversion creates proper event records in E03
- [ ] Implicit communities feed into E02 user profile recommendations
- [ ] Discovery analytics inform product and business decisions
- [ ] Quick chat initiation integrates with messaging system

## Key Design Decisions

### Posts vs Events Architecture
- **Separate Tables**: Posts and Events are distinct entities with different lifecycles
- **Expiration Strategy**: Posts auto-expire after 24-48h via cron job or TTL
- **Evolution Tracking**: Posts maintain reference to converted events
- **Unified Discovery**: Both posts and events appear in feeds with visual distinction

### Discovery Feed Architecture
- **PostGIS for Geo-Proximity**: Use spatial indexes for fast location-based queries
- **Temporal Decay Formula**: `score = base_score * (1 / (1 + hours_since_creation))`
- **Real-time Updates**: WebSocket push for new posts in user's proximity
- **Hybrid Ranking**: Combine geo-proximity, interest match, and social signals

### Recommendation Strategy
- **Multi-Factor Scoring**: `score = (recency * location_proximity * interest_match * social_boost)`
- **Temporal Intelligence**: Posts decay quickly, events persist
- **Cold Start Handling**: Use location + popular tags for new users
- **Real-time Personalization**: Update recommendations based on immediate user actions
- **Diversity Optimization**: Balance relevance with discovery of new content
- **Explainable AI**: Provide clear reasons for recommendations to build user trust

### Feed Algorithm Design
- **Hybrid Feed**: Combine social signals (follows) with algorithmic recommendations
- **Engagement-Based Ranking**: Prioritize content likely to generate engagement
- **Temporal Decay**: Balance fresh content with evergreen popular activities
- **Content Diversity**: Ensure variety in activity types, locations, and times

## Risk Assessment

### High Risk
- **Search Performance**: Complex queries could impact response times at scale
- **Recommendation Quality**: Poor recommendations could reduce user engagement significantly

### Medium Risk
- **Data Freshness**: Stale search indexes could show outdated activity information
- **Algorithm Bias**: Recommendation algorithms could create filter bubbles
- **Cold Start Problem**: New users might receive poor recommendations initially

### Low Risk
- **Search Infrastructure Costs**: Advanced search capabilities could be expensive
- **Feed Refresh Rate**: Real-time feed updates could impact server performance

## Performance Considerations

### Search Performance
- **Search Index Optimization**: Proper indexing strategies for fast query execution
- **Query Caching**: Cache common search queries and results
- **Result Pagination**: Efficient pagination for large result sets
- **Search Analytics**: Monitor query performance and optimize slow queries

### Recommendation Performance
- **Model Caching**: Cache recommendation models and user profiles
- **Batch Processing**: Process recommendation updates in batches
- **Precomputed Recommendations**: Generate recommendations offline for faster serving
- **A/B Testing Infrastructure**: Efficient framework for testing recommendation algorithms

### Feed Performance
- **Feed Caching**: Cache generated feeds for active users
- **Incremental Updates**: Update feeds incrementally rather than regenerating
- **Content Preloading**: Preload feed content for smooth infinite scroll
- **Real-time Optimization**: Balance real-time updates with performance

## Security Considerations

### Search Security
- **Query Sanitization**: Prevent injection attacks through search queries
- **Rate Limiting**: Prevent abuse of search APIs
- **Privacy Filtering**: Respect user privacy settings in search results

### Recommendation Security
- **Data Privacy**: Protect user behavior data used for recommendations
- **Recommendation Transparency**: Allow users to understand and control recommendations
- **Bias Prevention**: Monitor and prevent discriminatory recommendation patterns

### Feed Security
- **Content Filtering**: Ensure inappropriate content doesn't appear in feeds
- **Privacy Enforcement**: Respect user privacy settings in feed generation
- **Spam Prevention**: Detect and filter spam activities from feeds

## Integration with Other Epics

### E05 Social Interaction
- Discovery content provides context for social interactions
- Social signals (likes, comments) influence discovery algorithms
- Shared activities appear in social feeds and recommendations

### E06 Payments & Monetization
- Paid activities receive appropriate visibility in search and recommendations
- Payment conversion data improves recommendation quality
- Premium features could enhance discovery capabilities

### E07 Administration
- Discovery analytics provide insights for platform management
- Content moderation affects search and recommendation visibility
- A/B testing data informs product decisions

## Next Steps

1. **Search Infrastructure Setup**: Design search index schema and query optimization
2. **Recommendation Algorithm Design**: Define scoring models and data pipelines
3. **Feed Generation Architecture**: Plan feed composition and ranking algorithms
4. **API Contracts**: Specify interfaces for search, recommendations, and feeds
5. **Integration Points**: Plan integration with activity, user, and social data

---

**Epic Status**: 🔄 In Progress
**Started**: September 18, 2025
**Estimated Completion**: September 18, 2025
**Dependencies**: E01 Core Infrastructure ✅, E02 User & Profile Management ✅, E03 Activity Management ✅
**Blocks**: E05, E06, E07
