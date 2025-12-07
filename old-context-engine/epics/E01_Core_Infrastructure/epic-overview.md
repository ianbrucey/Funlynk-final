# E01 Core Infrastructure - Epic Overview

## Epic Purpose

The Core Infrastructure epic establishes the foundational services that all other system components depend on. This epic provides the bedrock data layer, security, location services, and communication infrastructure that enables all user-facing features.

**Note**: This epic defines the database schema for both **Posts** (ephemeral content from E04) and **Events** (structured activities from E03). The dual content model is a core architectural decision.

## Epic Scope

### In Scope
- **Database Schema & Models**: Complete data structure for the entire application (including posts and events)
- **Authentication Service**: User identity, registration, login, session management
- **Geolocation Service**: Location-based logic, spatial queries, distance calculations (for both posts and events)
- **Notification Service**: Centralized communication hub for push notifications and emails

### Out of Scope
- User-facing features (handled by other epics)
- Business logic specific to posts, events, payments, or social features
- Admin dashboard UI (handled by E07 Administration)

## Component Breakdown

### 1.1 Database Schema & Models
**Purpose**: Defines the complete data structure of the application
**Responsibilities**:
- User data models (profiles, authentication)
- **Posts data models** (ephemeral content, reactions, conversions) - E04
- **Events data models** (structured activities, RSVPs, comments) - E03
- Social graph models (follows, relationships)
- Payment data models (transactions, stripe accounts)
- System data models (tags, notifications, moderation)

**Key Tables to Define**:
- `users` - Core user identity and profile data
- **`posts`** - Ephemeral content (24-48h lifespan) - E04
- **`post_reactions`** - "I'm down" / "Join me" interactions - E04
- **`post_conversions`** - Post-to-event evolution tracking - E04
- `activities` - Structured events with RSVPs - E03
- `follows` - Social graph relationships
- `rsvps` - Event attendance tracking (not for posts)
- `comments` - User discussions on events
- `tags` - Categorization system (shared by posts and events)
- `notifications` - Communication tracking
- `payments` - Transaction records
- `stripe_accounts` - Payment provider integration
- `reports` - Content moderation system

**Posts vs Events Architecture**:
- **Posts**: Ephemeral, spontaneous "energy signals" (E04 Discovery Engine)
- **Events**: Structured, time-anchored experiences (E03 Activity Management)
- **Conversion**: Posts can evolve into events based on traction

### 1.2 Authentication Service
**Purpose**: Manages user identity and security across the platform
**Responsibilities**:
- User registration and email verification
- Secure password handling and storage
- Social login integration (Google, Apple)
- JWT token generation and validation
- Session management and refresh tokens
- Password reset functionality

**Key Decisions Needed**:
- Supabase Auth configuration and customization
- Social login provider setup
- Token expiration and refresh strategy
- User verification workflow

### 1.3 Geolocation Service
**Purpose**: Handles all location-based functionality for both posts and events
**Responsibilities**:
- Process and validate coordinates
- Calculate distances between points
- Perform spatial queries (find posts/events within radius)
- Optimize geospatial database queries (PostGIS)
- Handle location privacy and permissions

**Key Decisions Needed**:
- PostGIS extension setup and configuration
- Spatial indexing strategy for performance (critical for post discovery)
- Location precision and privacy levels
- Distance calculation algorithms

**Posts vs Events Considerations**:
- **Posts**: Tighter radius (5-10km) for spontaneous, local discovery
- **Events**: Wider radius (25-50km) for planned activities worth traveling to

### 1.4 Notification Service
**Purpose**: Centralized service for all platform communications
**Responsibilities**:
- Push notification delivery (iOS/Android)
- Email notification sending
- Notification preference management
- Delivery tracking and retry logic
- Template management for different notification types

**Key Decisions Needed**:
- Push notification provider (Firebase FCM)
- Email service provider integration
- Notification batching and rate limiting
- User preference granularity

## Dependencies

### External Dependencies
- **Supabase**: Database, authentication, real-time subscriptions
- **PostGIS**: Geospatial database extension
- **Firebase Cloud Messaging**: Push notifications
- **Email Service**: (TBD - SendGrid, AWS SES, or Supabase built-in)

### Internal Dependencies
- **None**: This is the foundation epic that all others depend on

## Success Criteria

### Database Schema
- [ ] Complete schema supports all planned features across all epics
- [ ] Proper relationships and constraints maintain data integrity
- [ ] Indexes optimize for expected query patterns
- [ ] Migration strategy supports iterative development

### Authentication Service
- [ ] Users can register with email/password
- [ ] Social login works with Google and Apple
- [ ] JWT tokens are secure and properly validated
- [ ] Session management handles refresh and expiration
- [ ] Password reset flow is secure and user-friendly

### Geolocation Service
- [ ] Spatial queries perform efficiently at scale
- [ ] Distance calculations are accurate and fast
- [ ] Location data is properly indexed
- [ ] Privacy controls are implemented

### Notification Service
- [ ] Push notifications deliver reliably
- [ ] Email notifications are properly formatted
- [ ] User preferences are respected
- [ ] Delivery failures are handled gracefully

## Acceptance Criteria

### Technical Requirements
- [ ] All services are properly documented with API specifications
- [ ] Database schema includes proper constraints and indexes
- [ ] Authentication follows security best practices
- [ ] Geolocation queries perform under 100ms for typical use cases
- [ ] Notification delivery has >95% success rate

### Integration Requirements
- [ ] Services expose clean interfaces for other epics to consume
- [ ] Error handling is consistent across all services
- [ ] Logging and monitoring are implemented
- [ ] Services can be independently tested and deployed

### Performance Requirements
- [ ] Database queries are optimized for expected load
- [ ] Authentication operations complete under 200ms
- [ ] Geolocation queries scale to 10,000+ activities
- [ ] Notification service handles 1000+ concurrent deliveries

## Risk Assessment

### High Risk
- **Database Schema Changes**: Once other epics depend on schema, changes become expensive
- **Authentication Security**: Security vulnerabilities could compromise entire platform

### Medium Risk
- **Geolocation Performance**: Spatial queries could become bottleneck at scale
- **Notification Delivery**: Third-party service reliability affects user experience

### Low Risk
- **Service Integration**: Well-defined interfaces minimize integration risk

## Next Steps

1. **Database Schema Design**: Define complete schema with all tables and relationships
2. **Service Architecture Planning**: Define service boundaries and integration patterns
3. **API Contract Definition**: Specify interfaces for other epics to consume
4. **Integration Point Mapping**: Plan how other epics will interact with core services

---

**Epic Status**: 🔄 In Progress
**Started**: September 17, 2025
**Estimated Completion**: September 20, 2025
**Dependencies**: None
**Blocks**: E02, E03, E04, E05, E06, E07
