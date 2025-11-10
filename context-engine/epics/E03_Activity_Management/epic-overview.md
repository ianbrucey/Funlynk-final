# E03 Activity Management - Epic Overview

## Epic Purpose

The Activity Management epic provides the core functionality for creating, managing, and participating in **structured events** (referred to as "activities" in the database). This epic enables hosts to create formal events with RSVPs, participants to register and attend, and the platform to manage the entire event lifecycle from creation to completion.

**Important Distinction**: This epic handles **Events** (structured, time-anchored experiences with RSVPs/payments), NOT **Posts** (ephemeral, spontaneous invitations). See E04 Discovery Engine for the Posts vs Events architecture.

## Epic Scope

### In Scope
- **Activity CRUD Service**: Complete event creation, reading, updating, and deletion
- **Tagging & Category System**: Flexible tagging and categorization for event discovery
- **RSVP & Attendance Service**: Registration, attendance tracking, and capacity management
- **Activity Lifecycle Management**: Status transitions, cancellations, and completions
- **Post-to-Event Conversion**: Handle events that originated from posts (created via E04)

### Out of Scope
- **Posts** (ephemeral content handled by E04 Discovery Engine)
- Activity discovery algorithms (handled by E04 Discovery Engine)
- Payment processing for paid activities (handled by E06 Payments & Monetization)
- Social interactions on activities (handled by E05 Social Interaction)
- Administrative moderation (handled by E07 Administration)

## Posts vs Events: Terminology Clarification

**In FunLynk's Architecture**:
- **Posts** = Ephemeral, spontaneous "energy signals" (24-48h lifespan) - handled by E04
- **Events** = Structured, time-anchored experiences with RSVPs - handled by E03 (this epic)
- **Activities** = Database table name for Events (legacy naming from before Posts existed)

**In This Epic**:
- "Activity" and "Event" are used interchangeably
- All references to "activities" mean **structured events**, not posts
- Posts are explicitly out of scope for this epic

## Component Breakdown

### 3.1 Activity CRUD Service
**Purpose**: Manages the complete lifecycle of structured events from creation to deletion
**Responsibilities**:
- Event creation with validation and enrichment (direct creation OR post-to-event conversion)
- Event updates and modifications by hosts
- Event deletion and cancellation workflows
- Event status management (draft, published, cancelled, completed)
- Event image upload and management
- Event location validation and geocoding
- Post-to-event conversion handling (when events originate from posts)

**Key Features**:
- Rich event creation with title, description, location, time, capacity
- Multiple event images with automatic optimization
- Flexible scheduling (one-time, recurring, multi-day events)
- Capacity management with waitlists
- Host-controlled event settings and permissions
- Event templates for common event types
- **Post-originated events**: Track events that evolved from posts (via `originated_from_post_id`)

**Event Creation Paths**:
1. **Direct Creation**: Host creates event from scratch (traditional flow)
2. **Post Conversion**: Host converts popular post to event (via E04 Discovery Engine)

### 3.2 Tagging & Category System
**Purpose**: Provides flexible categorization and tagging for activity organization and discovery
**Responsibilities**:
- Predefined category management (sports, social, learning, etc.)
- User-generated tag creation and management
- Tag validation and moderation
- Category-based activity filtering
- Tag popularity tracking and trending
- Interest-based activity matching

**Key Features**:
- Hierarchical category system (Sports > Basketball > Pickup Games)
- Free-form user tags with auto-suggestions
- Tag synonyms and normalization
- Popular tag recommendations
- Category-specific activity templates
- Interest matching with user profiles

### 3.3 RSVP & Attendance Service
**Purpose**: Manages participant registration, attendance tracking, and capacity control
**Responsibilities**:
- RSVP creation and management
- Attendance confirmation and tracking
- Capacity enforcement and waitlist management
- RSVP notifications and reminders
- No-show tracking and penalties
- Group RSVP and guest management

**Key Features**:
- Instant RSVP with real-time capacity updates
- Waitlist management with automatic promotion
- RSVP requirements (questions, waivers, payments)
- Attendance check-in via QR codes or location
- Host tools for managing participants
- RSVP analytics and insights

## Dependencies

### External Dependencies
- **E01 Core Infrastructure**: Database, authentication, geolocation, notifications
- **E02 User & Profile Management**: User profiles, social graph, host verification
- **E04 Discovery Engine**: Post-to-event conversion flow (posts table, conversion tracking)
- **Supabase Storage**: Activity image storage and CDN
- **External APIs**: Geocoding services, weather APIs (optional)

### Internal Dependencies
- **Activities table**: Core event data from E01 (with post conversion fields)
- **Posts table**: Source posts for converted events (from E04)
- **Post Conversions table**: Tracking post-to-event evolution (from E04)
- **RSVPs table**: Participant registration data from E01
- **Tags table**: Activity categorization from E01
- **Users table**: Host and participant profiles from E01/E02

### Integration with E04 Discovery Engine
- **Post-to-Event Conversion**: E04 initiates conversion, E03 creates the event
- **Conversion Tracking**: E03 records `originated_from_post_id` and `conversion_date`
- **Engaged User Notification**: E03 notifies users who reacted to the original post
- **Post Linking**: Converted events maintain reference to original post for context

## Success Criteria

### Activity CRUD Service
- [ ] Hosts can create events in under 2 minutes (direct creation)
- [ ] Post-to-event conversion completes in under 30 seconds
- [ ] Event creation has 95%+ success rate
- [ ] Event updates reflect immediately for all participants
- [ ] Event images upload and optimize within 10 seconds
- [ ] Location validation provides accurate geocoding
- [ ] Event templates reduce creation time by 50%
- [ ] Post-originated events properly track `originated_from_post_id`

### Tagging & Category System
- [ ] Tag suggestions are relevant and helpful
- [ ] Category browsing enables easy activity discovery
- [ ] Tag moderation prevents spam and inappropriate content
- [ ] Interest matching improves activity recommendations
- [ ] Popular tags surface trending activities
- [ ] Category hierarchy supports intuitive navigation

### RSVP & Attendance Service
- [ ] RSVP operations complete in under 500ms
- [ ] Capacity management prevents overbooking
- [ ] Waitlist promotions happen automatically and instantly
- [ ] Attendance tracking has 95%+ accuracy
- [ ] RSVP notifications are timely and relevant
- [ ] Host management tools are intuitive and powerful

## Acceptance Criteria

### Technical Requirements
- [ ] Activity operations scale to 10,000+ concurrent users
- [ ] RSVP operations handle race conditions correctly
- [ ] Image uploads support multiple formats and automatic optimization
- [ ] Location data integrates seamlessly with geolocation service
- [ ] Real-time updates work across all connected clients
- [ ] Data consistency maintained across all operations

### User Experience Requirements
- [ ] Activity creation flow is intuitive and engaging
- [ ] RSVP process is frictionless and immediate
- [ ] Host management tools provide clear activity insights
- [ ] Participant experience is smooth from discovery to attendance
- [ ] Error handling provides clear guidance and recovery options
- [ ] Mobile experience is optimized for on-the-go usage

### Integration Requirements
- [ ] Activity data enhances user profiles and social feeds
- [ ] RSVP data supports payment processing workflows
- [ ] Tag data improves discovery and recommendation algorithms
- [ ] Activity lifecycle integrates with notification systems
- [ ] Host tools connect with monetization features

## Key Design Decisions

### Activity Data Model
- **Rich Metadata**: Support for detailed activity information including requirements, equipment, skill level
- **Flexible Scheduling**: One-time events, recurring activities, and multi-session programs
- **Location Flexibility**: Physical locations, virtual activities, and hybrid events
- **Capacity Management**: Hard limits, soft limits, and unlimited capacity options

### Tagging Architecture
- **Hybrid System**: Predefined categories + user-generated tags for flexibility
- **Tag Normalization**: Automatic synonym detection and tag merging
- **Moderation Pipeline**: Automated filtering + human review for inappropriate tags
- **Trending Algorithm**: Tag popularity based on usage, engagement, and recency

### RSVP System Design
- **Optimistic Concurrency**: Handle race conditions for popular activities
- **Flexible Requirements**: Support for questions, waivers, and conditional RSVPs
- **Waitlist Intelligence**: Smart promotion based on user preferences and activity fit
- **Attendance Verification**: Multiple methods (QR, location, manual) for different activity types

## Risk Assessment

### High Risk
- **Race Conditions**: Popular activities could have RSVP conflicts during high traffic
- **Data Consistency**: Activity updates must propagate correctly to all participants

### Medium Risk
- **Image Storage Costs**: Activity images could become expensive at scale
- **Tag Spam**: User-generated tags could be abused for spam or inappropriate content
- **Capacity Management**: Complex waitlist logic could confuse users

### Low Risk
- **Geocoding Limits**: External geocoding APIs could hit rate limits
- **Activity Templates**: Templates might not cover all use cases

## Performance Considerations

### Activity Service Performance
- **Image Optimization**: Automatic resizing and compression for fast loading
- **Activity Caching**: Cache popular activities for improved response times
- **Search Indexing**: Optimize activity search with proper database indexes

### RSVP Service Performance
- **Concurrency Control**: Handle simultaneous RSVPs with proper locking
- **Real-time Updates**: Efficient WebSocket updates for capacity changes
- **Batch Operations**: Optimize bulk RSVP operations for large activities

### Tagging Performance
- **Tag Autocomplete**: Fast tag suggestions with indexed search
- **Category Queries**: Optimized hierarchical category browsing
- **Trending Calculations**: Efficient algorithms for popular tag detection

## Security Considerations

### Activity Security
- **Host Verification**: Ensure only verified hosts can create certain activity types
- **Content Moderation**: Automated and manual review of activity content
- **Privacy Controls**: Respect user privacy settings in activity visibility

### RSVP Security
- **Duplicate Prevention**: Prevent multiple RSVPs from same user
- **Capacity Enforcement**: Secure capacity checking to prevent overbooking
- **Data Protection**: Protect participant information and RSVP history

### Tag Security
- **Input Validation**: Sanitize all user-generated tag content
- **Spam Prevention**: Rate limiting and pattern detection for tag abuse
- **Content Filtering**: Automated filtering of inappropriate or offensive tags

## Integration with Other Epics

### E04 Discovery Engine
- Activity data powers search and recommendation algorithms
- Tag data enables category-based filtering and discovery
- RSVP data influences activity popularity and ranking

### E05 Social Interaction
- Activity context for comments and social interactions
- RSVP data determines interaction permissions
- Host-participant relationship management

### E06 Payments & Monetization
- Activity pricing and payment requirements
- RSVP data triggers payment processing
- Host payout calculations based on attendance

### E07 Administration
- Activity moderation and content review
- RSVP analytics and platform insights
- Host performance tracking and verification

## Next Steps

1. **Database Schema Review**: Validate activities, RSVPs, and tags table design from E01
2. **Service Architecture**: Define activity, tagging, and RSVP service boundaries
3. **API Contracts**: Specify interfaces for activity management operations
4. **Integration Points**: Plan integration with discovery, social, and payment features

---

**Epic Status**: 🔄 In Progress
**Started**: September 17, 2025
**Estimated Completion**: September 18, 2025
**Dependencies**: E01 Core Infrastructure ✅, E02 User & Profile Management ✅
**Blocks**: E04, E05, E06, E07
