# Architecture Overview

**Generated:** 2025-12-02

## System Architecture

FunLynk follows a **layered monolithic architecture** with clear separation of concerns:

```
┌─────────────────────────────────────────────────────────────┐
│                     Presentation Layer                       │
│  ┌──────────────┐  ┌──────────────┐  ┌──────────────┐      │
│  │   Livewire   │  │   Filament   │  │  Blade Views │      │
│  │  Components  │  │   Resources  │  │              │      │
│  └──────────────┘  └──────────────┘  └──────────────┘      │
└─────────────────────────────────────────────────────────────┘
                            ↓
┌─────────────────────────────────────────────────────────────┐
│                    Application Layer                         │
│  ┌──────────────┐  ┌──────────────┐  ┌──────────────┐      │
│  │ Controllers  │  │   Policies   │  │  Middleware  │      │
│  └──────────────┘  └──────────────┘  └──────────────┘      │
└─────────────────────────────────────────────────────────────┘
                            ↓
┌─────────────────────────────────────────────────────────────┐
│                     Business Logic Layer                     │
│  ┌──────────────┐  ┌──────────────┐  ┌──────────────┐      │
│  │   Services   │  │    Events    │  │  Listeners   │      │
│  └──────────────┘  └──────────────┘  └──────────────┘      │
└─────────────────────────────────────────────────────────────┘
                            ↓
┌─────────────────────────────────────────────────────────────┐
│                       Data Layer                             │
│  ┌──────────────┐  ┌──────────────┐  ┌──────────────┐      │
│  │   Models     │  │  Migrations  │  │  Factories   │      │
│  └──────────────┘  └──────────────┘  └──────────────┘      │
└─────────────────────────────────────────────────────────────┘
                            ↓
┌─────────────────────────────────────────────────────────────┐
│                    Infrastructure Layer                      │
│  ┌──────────────┐  ┌──────────────┐  ┌──────────────┐      │
│  │  PostgreSQL  │  │ Meilisearch  │  │    Redis     │      │
│  │   + PostGIS  │  │              │  │   (Queue)    │      │
│  └──────────────┘  └──────────────┘  └──────────────┘      │
└─────────────────────────────────────────────────────────────┘
```

## Core Domain Models

### Primary Entities

1. **User** - Platform users with location, interests, social graph
2. **Post** - Ephemeral activity proposals (24-48h TTL)
3. **Activity** - Structured events with RSVPs and payments
4. **Group** - Communities organized around interests/locations
5. **Conversation** - Multi-context chat system
6. **Tag** - Activity categorization and discovery

### Supporting Entities

- **PostReaction** - User engagement with Posts ("I'm down", "Join me")
- **PostConversion** - Tracking Post → Activity evolution
- **PostInvitation** - Direct invites to Posts
- **ActivityInvitation** - Direct invites to Activities
- **Rsvp** - Event attendance commitments
- **Follow** - User social connections
- **Notification** - User alerts and updates
- **Message** - Chat messages across all contexts
- **GroupMember** - Group membership and roles
- **StripeAccount** - Host payment accounts
- **Transaction** - Payment records

## Data Flow Patterns

### 1. Post Creation & Discovery Flow

```
User Creates Post
    ↓
PostService.createPost()
    ↓
Post Model Saved (with PostGIS coordinates)
    ↓
PostCreated Event Dispatched
    ↓
Indexed in Meilisearch (via Scout)
    ↓
Appears in Nearby Feed (spatial query)
```

### 2. Post-to-Event Conversion Flow

```
User Reacts to Post
    ↓
PostReacted Event Dispatched
    ↓
CheckPostConversion Listener
    ↓
ConversionEligibilityService.check()
    ↓
If 5+ reactions: PostConversionSuggested Event
If 10+ reactions: PostAutoConverted Event
    ↓
ActivityConversionService.createFromPost()
    ↓
Activity Created with originated_from_post_id
    ↓
NotifyInterestedUsers Listener
    ↓
Notifications Sent to Reactors
```

### 3. Real-Time Chat Flow

```
User Sends Message
    ↓
ChatService.sendMessage()
    ↓
Message Model Saved
    ↓
MessageSent Event Dispatched
    ↓
Broadcast via Reverb WebSocket
    ↓
Notification Created (if recipient offline)
    ↓
Real-Time UI Update (if recipient online)
```

### 4. Spatial Discovery Flow

```
User Opens Nearby Feed
    ↓
FeedService.getNearbyPosts()
    ↓
PostGIS Spatial Query (whereDistance)
    ↓
Filter by user interests/tags
    ↓
RecommendationEngine.rankPosts()
    ↓
Return sorted, paginated results
```

## Integration Points

### External Services

1. **Stripe API**
   - Payment processing for paid Activities
   - Stripe Connect for host payouts
   - Webhook handling for payment events

2. **Meilisearch**
   - Full-text search for Posts, Activities, Users
   - Tag-based discovery
   - Typo-tolerant search

3. **Laravel Reverb (WebSockets)**
   - Real-time notifications
   - Live chat updates
   - Presence channels for online status

4. **Social OAuth Providers**
   - Google OAuth
   - Facebook OAuth
   - Account linking via SocialAccount model

### Internal Service Communication

Services communicate via:
- **Direct method calls** (synchronous)
- **Events & Listeners** (asynchronous, decoupled)
- **Queued Jobs** (background processing)

Example service dependencies:
```
PostService
    ↓ uses
ConversionEligibilityService
    ↓ triggers
ActivityConversionService
    ↓ uses
ActivityService
```

## Background Job Processing

### Queue System
- **Driver:** Redis (production) / Database (development)
- **Worker:** `php artisan queue:listen`

### Key Jobs

1. **CheckPostConversionEligibility** - Periodic check for conversion thresholds
2. **ExpirePostsJob** - Remove expired Posts (24-48h TTL)
3. **UpdateTagAnalytics** - Aggregate tag usage statistics

## Caching Strategy

- **Session Storage:** Database-backed sessions
- **Cache Driver:** Redis (production) / File (development)
- **Cached Data:**
  - User location coordinates (1 hour)
  - Tag analytics (15 minutes)
  - Feed results (5 minutes)

## Security Architecture

### Authentication
- Laravel's built-in authentication
- Session-based (web routes)
- Social OAuth integration

### Authorization
- Policy-based (Laravel Policies)
- Gate checks in Livewire components
- Filament resource policies

### Data Protection
- PostGIS geography columns for precise location
- Privacy levels on User model
- Conversation participant authorization
- Group membership verification

