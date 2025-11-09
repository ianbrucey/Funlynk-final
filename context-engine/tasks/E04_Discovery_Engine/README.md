# E04 Discovery Engine - Task Overview

## Epic Summary

The Discovery Engine epic transforms FunLynk into a **spontaneous, niche-discovery social network** through the Posts vs Events dual model, temporal intelligence, and social resonance features. This differentiates FunLynk from traditional event platforms like Meetup and Eventbrite.

**Core Differentiator**: "From Events to Energy" - We treat posts as spontaneous invitations to connect, not transactional event registrations.

See `context-engine/epics/E04_Discovery_Engine/posts-vs-events-architecture.md` for detailed architecture.

## Feature Breakdown

### F01 Discovery Feed Service (6 Tasks) - RENAMED
**Purpose**: Real-time discovery of posts and events through location-aware, interest-based feeds
**Scope**: Nearby feed, For You feed, map view, temporal decay scoring, geo-proximity discovery

**Key Changes from Original "Search Service"**:
- Focus on real-time feeds (Nearby, For You) instead of traditional search
- Map view with interactive pins for posts/events
- Temporal decay scoring for posts (recent posts rank higher)
- Geo-proximity filtering with PostGIS

**Tasks** (NEED UPDATES):
- **T01**: Discovery Feed UX Design (Nearby, For You, Map) - UPDATE NEEDED
- **T02**: Post Discovery Backend (Geo + Temporal) - UPDATE NEEDED
- **T03**: Discovery Feed Frontend (Feed + Map Views) - UPDATE NEEDED
- **T04**: Post Expiration & Temporal Decay - UPDATE NEEDED
- **T05**: Discovery Analytics (Post Engagement) - UPDATE NEEDED
- **T06**: Real-time Post Updates & WebSocket - UPDATE NEEDED

### F02 Recommendation Engine (6 Tasks) - NEEDS REFOCUS
**Purpose**: Multi-factor scoring with temporal intelligence for posts and events
**Scope**: Temporal decay for posts, interest matching, location proximity, social signals, vibe matching

**Key Changes**:
- Add temporal weighting for posts (recency matters more)
- Separate scoring for posts vs events
- Add "vibe matching" (mood + tags + location)
- Implicit community influence on recommendations

**Tasks** (NEED UPDATES):
- **T01**: Recommendation System UX Design & Presentation - MINOR UPDATES
- **T02**: Recommendation Algorithms & Scoring Engine - MAJOR UPDATES (temporal decay)
- **T03**: Recommendation Frontend Components & Display - MINOR UPDATES
- **T04**: Personalization & User Behavior Analysis - UPDATES (post vs event behavior)
- **T05**: Recommendation Analytics & A/B Testing - UPDATES (post metrics)
- **T06**: Social & Collaborative Filtering Integration - MINOR UPDATES

### F03 Social Resonance & Post Evolution (6 Tasks) - RENAMED & REFOCUSED
**Purpose**: Enable spontaneous social connections and post-to-event evolution
**Scope**: "I'm down" / "Join me" buttons, quick chat, post-to-event conversion, engagement tracking

**Key Changes from Original "Feed Generation"**:
- Focus on social resonance interactions instead of generic feeds
- Post-to-event evolution algorithms and UI
- Quick connection features (DM, group chat)
- Engagement-based conversion triggers

**Tasks** (NEED MAJOR UPDATES):
- **T01**: Social Resonance UX Design ("I'm down", "Join me") - MAJOR REWRITE
- **T02**: Post-to-Event Evolution Algorithms - MAJOR REWRITE
- **T03**: Social Resonance Frontend (Quick Chat, Reactions) - MAJOR REWRITE
- **T04**: Implicit Community Detection & Display - NEW FOCUS
- **T05**: Post Engagement Analytics & Conversion Tracking - MAJOR REWRITE
- **T06**: Real-time Social Signals & Notifications - UPDATES

### F04 Implicit Communities (6 Tasks) - NEW FEATURE (Phase 2)
**Purpose**: Auto-generate communities from activity patterns without manual group creation
**Scope**: Pattern detection, clustering algorithms, community feeds, member suggestions

**Tasks** (TO BE CREATED):
- **T01**: Community Detection Algorithm Design
- **T02**: Community Backend (Clustering, Scoring)
- **T03**: Community Frontend (Auto-generated Groups)
- **T04**: Community Analytics and Growth Tracking
- **T05**: Community Recommendations & Suggestions
- **T06**: Community Feed Generation & Optimization

## Implementation Dependencies

### Prerequisites (Must be completed first)
- **E01 Core Infrastructure**: Database schema (PostGIS), geolocation, notifications, WebSocket support
- **E02 User Profile Management**: User profiles, interests, social graph, location data
- **E03 Activity Management**: Event data, tags, RSVP information
- **NEW**: Posts table, post reactions table, post conversions table

### Enables (Unlocked by this epic)
- **E05 Social Interaction**: Enhanced social features with post comments, shares, quick chat
- **E06 Payments & Monetization**: Discovery-driven paid event promotion, post-to-event monetization
- **E07 Administration**: Discovery analytics, post moderation, conversion tracking

## Task Update Status

### ⚠️ Features Requiring Updates
- **F01 Discovery Feed Service** (6/6 tasks need updates) ⚠️
  - Rename from "Search Service"
  - Refocus on Nearby feed, For You feed, Map view
  - Add temporal decay and post expiration

- **F02 Recommendation Engine** (6/6 tasks need updates) ⚠️
  - Add temporal weighting for posts
  - Separate scoring for posts vs events
  - Add vibe matching algorithms

- **F03 Social Resonance & Post Evolution** (6/6 tasks need major rewrites) ⚠️
  - Rename from "Feed Generation Service"
  - Complete refocus on social resonance and post-to-event evolution
  - New tasks for "I'm down" buttons, quick chat, conversion flow

### 📋 New Features to Create
- **F04 Implicit Communities** (6 tasks to be created) 📋
  - Phase 2 / v2 feature
  - Auto-generated communities from activity patterns
  - Community detection algorithms and UI

## Implementation Priority

### Phase 1: MVP (Must Have for Launch)
1. **Posts Table & Expiration** - Create posts schema with 24-48h auto-expiration
2. **Discovery Feeds** - Nearby feed + For You feed with temporal decay
3. **Map View** - Interactive map with real-time pins
4. **Social Resonance** - "I'm down" button and quick DM
5. **Post-to-Event Evolution** - Conversion flow and tracking

### Phase 2: Enhanced Discovery (Post-MVP)
1. **Implicit Communities** - Auto-detect clusters and suggest communities
2. **Advanced Vibe Matching** - AI-powered mood detection
3. **Micro-Groups** - Recurring post patterns and group suggestions
4. Begin implementation with Problem Definition phases
5. Update PLANNING-TRACKER.md with completion status

## Quality Standards

Each task follows the enhanced 4-phase template:
- **Problem Definition**: Clear scope and acceptance criteria
- **Research**: Technical decisions and analysis
- **Enhanced Planning**: UX/Backend/Frontend/Third-party specifications
- **Implementation Tracking**: Detailed progress monitoring

## Success Metrics

- **18 total tasks** created across 3 features
- **Complete coverage** of all epic requirements
- **Clear dependencies** with no circular relationships
- **Implementable scope** - each task completable in 1-4 hours
- **Template consistency** following established E01/E02/E03 patterns

## Key Technical Challenges

### Search Performance
- Complex queries with multiple filters and geospatial search
- Real-time search suggestions and autocomplete
- Search result personalization without performance impact

### Recommendation Quality
- Multi-factor scoring combining interests, social signals, and behavior
- Cold start problem for new users
- Recommendation diversity to avoid filter bubbles

### Feed Generation
- Real-time feed updates with high user concurrency
- Balancing social signals with algorithmic recommendations
- Infinite scroll performance with large datasets

## Integration Points

### Data Sources
- **Activities**: Core content for search and recommendations
- **Users**: Profiles and interests for personalization
- **Social Graph**: Following relationships for social recommendations
- **RSVPs**: User behavior data for collaborative filtering
- **Tags**: Activity categorization for filtering and matching

### External Services
- **Search Infrastructure**: Elasticsearch or similar for advanced search
- **Analytics**: User behavior tracking and recommendation feedback
- **Caching**: Redis for search results and recommendation caching
- **Real-time Updates**: WebSocket integration for live feed updates

---

**Epic**: E04 Discovery Engine
**Status**: ✅ Task Creation Complete
**Progress**: 18/18 tasks created
**Next Priority**: Begin implementation with Problem Definition phases

## Task Creation Summary

### F01 Search Service (6 tasks) ✅
- T01: Search System UX Design & Interface
- T02: Search Infrastructure & Backend APIs
- T03: Search Frontend Implementation & Filters
- T04: Advanced Search Features & Personalization
- T05: Search Analytics & Performance Optimization
- T06: Search Integration & Real-time Updates

### F02 Recommendation Engine (6 tasks) ✅
- T01: Recommendation System UX Design & Presentation
- T02: Recommendation Algorithms & Scoring Engine
- T03: Recommendation Frontend Components & Display
- T04: Personalization & User Behavior Analysis
- T05: Recommendation Analytics & A/B Testing
- T06: Social & Collaborative Filtering Integration

### F03 Feed Generation Service (6 tasks) ✅
- T01: Feed System UX Design & Navigation
- T02: Feed Generation Backend & Algorithms
- T03: Feed Frontend Implementation & Infinite Scroll
- T04: Social Feed Integration & Following
- T05: Feed Analytics & Engagement Optimization
- T06: Real-time Feed Updates & Performance
