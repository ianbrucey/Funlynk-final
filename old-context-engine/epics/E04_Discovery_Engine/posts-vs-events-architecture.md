# Posts vs Events Architecture

## Overview

This document defines FunLynk's core differentiator: a **dual content model** that treats discovery as "energy signals" rather than event transactions. This architecture enables spontaneous, niche-discovery experiences that set FunLynk apart from traditional event platforms like Meetup and Eventbrite.

## The Problem We're Solving

### What's Missing in Existing Platforms

**Meetup/Eventbrite Focus**:
- Professional, structured events
- Formal planning and registration
- Transactional mindset (pay → attend)
- High friction for spontaneous activities

**Real-World Gap**:
- Jam sessions at random churches
- Pickup basketball games
- Spontaneous coffee meetups
- Last-minute hiking trips
- Impromptu creative sessions

**The Insight**: These activities are **invisible unless you're "in the circle"**. They happen through word-of-mouth, group chats, and informal networks—not formal event platforms.

## FunLynk's Solution: Posts vs Events

### Core Concept: "From Events to Energy"

We treat posts as **spontaneous invitations to connect**, not transactional event registrations. This creates a social network feel while maintaining the utility of event discovery.

---

## Posts: Spontaneous Energy Signals

### Definition
Lightweight, conversational, real-time "what's happening" updates that invite spontaneous participation.

### Characteristics

| Attribute | Value |
|-----------|-------|
| **Lifecycle** | Ephemeral (24-48h auto-expire) |
| **Tone** | Conversational, casual |
| **Creation Friction** | Minimal (like tweeting) |
| **Structure** | Loose (optional time/location) |
| **Evolution** | Can convert to events |
| **Discovery** | Real-time feeds, map view |

### Examples

```
"Open jam at Clark ATL tonight 🎸 — bring your guitar, starts around 8pm"

"Anyone down for pickup basketball at Piedmont Park in 30 mins?"

"Coffee and coding at Octane this afternoon, who's in?"

"Spontaneous hike at Arabia Mountain this weekend — DM me!"

"Late-night food run to Waffle House, leaving in 10 🧇"
```

### Post Schema

```sql
CREATE TABLE posts (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    user_id UUID NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    
    -- Content
    content TEXT NOT NULL,
    tags TEXT[] DEFAULT '{}',
    
    -- Location (optional but recommended)
    location_name VARCHAR(255),
    location_coordinates GEOGRAPHY(POINT, 4326),
    geo_hash VARCHAR(12), -- For efficient proximity queries
    
    -- Temporal metadata
    approximate_time TIMESTAMP, -- "tonight", "this weekend", etc.
    expires_at TIMESTAMP NOT NULL DEFAULT (NOW() + INTERVAL '48 hours'),
    
    -- Mood/vibe tagging
    mood VARCHAR(50), -- creative, social, active, chill, adventurous
    
    -- Evolution tracking
    evolved_to_event_id UUID REFERENCES activities(id),
    conversion_triggered_at TIMESTAMP,
    
    -- Engagement metrics
    view_count INTEGER DEFAULT 0,
    reaction_count INTEGER DEFAULT 0,
    
    -- Timestamps
    created_at TIMESTAMP DEFAULT NOW(),
    updated_at TIMESTAMP DEFAULT NOW(),
    
    -- Indexes
    INDEX idx_posts_location (location_coordinates) USING GIST,
    INDEX idx_posts_geo_hash (geo_hash),
    INDEX idx_posts_expires_at (expires_at),
    INDEX idx_posts_created_at (created_at DESC),
    INDEX idx_posts_tags (tags) USING GIN
);
```

### Post Expiration Strategy

**Automatic Expiration**:
- Default: 48 hours from creation
- Configurable by user (24h, 48h, 72h)
- Cron job runs every hour to soft-delete expired posts
- Expired posts remain in database for analytics but hidden from feeds

**Manual Expiration**:
- User can manually expire post early
- Auto-expire when converted to event

**Expiration Notifications**:
- Host notified 6 hours before expiration
- Option to extend or convert to event

---

## Events: Structured Experiences

### Definition
Time-anchored activities with formal RSVPs, payments, and structured planning.

### Characteristics

| Attribute | Value |
|-----------|-------|
| **Lifecycle** | Persistent until completion |
| **Tone** | Professional, structured |
| **Creation Friction** | Moderate (requires details) |
| **Structure** | Strict (exact time/location/capacity) |
| **Evolution** | Can originate from posts |
| **Discovery** | Search, filters, recommendations |

### Examples

```
"Jazz Night @ High Museum — $10 cover, Friday 8pm, limited to 50 people"

"5K Charity Run — Register by March 1st, $25 entry fee"

"Photography Workshop — $50, limited to 15 people, Saturday 10am-2pm"

"Trivia Night @ Brick Store Pub — Free, teams of 4, every Tuesday 7pm"
```

### Event Schema (Existing from E03)

Events use the existing `activities` table from E03 Activity Management with additional fields:

```sql
-- Additional fields for events originated from posts
ALTER TABLE activities ADD COLUMN originated_from_post_id UUID REFERENCES posts(id);
ALTER TABLE activities ADD COLUMN conversion_date TIMESTAMP;
```

---

## Post-to-Event Evolution Flow

### The Magic

Posts that gain traction can **evolve into structured events**, creating a seamless path from spontaneous idea to formal gathering.

### Conversion Triggers

**Automatic Suggestions**:
1. **Engagement Threshold**: 5+ "I'm down" reactions
2. **Time Sensitivity**: Post approaching expiration with high engagement
3. **Capacity Concerns**: Host mentions limited spots in comments

**Manual Conversion**:
- Host can convert any post to event at any time
- Conversion UI accessible from post detail page

### Conversion Process

```
1. Post gains traction
   └─> Multiple "I'm down" reactions
   └─> Comments asking for details
   └─> Shares to friends

2. System suggests conversion
   └─> "5 people are interested! Convert to event?"
   └─> Show potential attendee list

3. Host adds structure
   └─> Exact date/time
   └─> Capacity limit
   └─> Pricing (optional)
   └─> RSVP requirements

4. Post becomes event
   └─> Original post links to event
   └─> Post marked as "evolved"
   └─> Post remains visible with "Now an event" badge

5. Engaged users notified
   └─> "The jam session is now an official event!"
   └─> One-tap RSVP from notification
```

### Conversion UI Flow

**Step 1: Trigger Conversion**
```
[Post Detail Page]
┌─────────────────────────────────────┐
│ 🎸 Open jam tonight at Clark ATL    │
│                                     │
│ 👍 5 people are down                │
│ 💬 3 comments                       │
│                                     │
│ [Convert to Event] ← Suggested      │
└─────────────────────────────────────┘
```

**Step 2: Add Structure**
```
[Conversion Form]
┌─────────────────────────────────────┐
│ Convert Post to Event               │
│                                     │
│ Event Title: [Open Jam Session]    │
│ Date: [Tonight, 8:00 PM]           │
│ Location: [Clark ATL]              │
│ Capacity: [20 people]              │
│ Price: [Free] or [$10]             │
│                                     │
│ [Create Event] [Cancel]            │
└─────────────────────────────────────┘
```

**Step 3: Notify Engaged Users**
```
[Notification]
┌─────────────────────────────────────┐
│ 🎉 The jam session is now an event! │
│                                     │
│ Open Jam Session                    │
│ Tonight, 8:00 PM @ Clark ATL       │
│                                     │
│ [RSVP Now] [View Details]          │
└─────────────────────────────────────┘
```

### Conversion Tracking

```sql
-- Track conversion metrics
CREATE TABLE post_conversions (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    post_id UUID NOT NULL REFERENCES posts(id),
    event_id UUID NOT NULL REFERENCES activities(id),
    
    -- Conversion context
    trigger_type VARCHAR(50), -- manual, engagement_threshold, time_sensitive
    reactions_at_conversion INTEGER,
    comments_at_conversion INTEGER,
    views_at_conversion INTEGER,
    
    -- Conversion outcome
    rsvp_conversion_rate DECIMAL(5,2), -- % of engaged users who RSVP'd
    
    created_at TIMESTAMP DEFAULT NOW()
);
```

---

## Temporal Intelligence

### Temporal Decay for Posts

**Formula**:
```
decay_factor = 1 / (1 + hours_since_creation)

final_score = base_score * decay_factor
```

**Example**:
- Post created 1 hour ago: `decay_factor = 1 / (1 + 1) = 0.5`
- Post created 6 hours ago: `decay_factor = 1 / (1 + 6) = 0.14`
- Post created 24 hours ago: `decay_factor = 1 / (1 + 24) = 0.04`

**Result**: Recent posts rank **significantly higher** than older posts, creating urgency and freshness.

### No Decay for Events

Events maintain consistent scoring until completion, as they're time-anchored and planned in advance.

---

## Discovery UI: "Vibe Map"

### Three Core Views

#### 1. Nearby Feed
**Purpose**: "What's happening within 5 miles right now"

**Features**:
- Real-time posts within configurable radius (default 5 miles)
- Sorted by temporal decay (recent posts first)
- Visual distinction between posts and events
- Quick filters: category, time, cost

#### 2. For You Feed
**Purpose**: Posts/events matching user interests

**Features**:
- Interest-based content matching
- Social signals (friends' activity)
- Temporal weighting for posts
- Diversity optimization

#### 3. Map View
**Purpose**: Interactive map with real-time pins

**Features**:
- Posts = pulsing pins (animated)
- Events = static pins
- Tap pin to open post/event details
- Cluster pins when zoomed out
- Filter by category, time, cost

---

## Social Resonance Interactions

### "I'm Down" Button

**Purpose**: Signal intent to participate (stronger than "like")

**Behavior**:
- Instant notification to host
- Adds user to "interested" list
- Increases post visibility
- Contributes to conversion threshold

### "Join Me" Button

**Purpose**: Invite friends to post

**Behavior**:
- Opens friend selector
- Sends notification with post link
- Tracks invitation success rate

### Quick Chat Initiation

**Purpose**: Facilitate instant connection

**Behavior**:
- 1-tap DM to host from post
- 1-tap group chat with all "I'm down" users
- Pre-filled message context

---

## Implementation Priority

### Phase 1: MVP (Must Have)
1. ✅ Posts table with auto-expiration
2. ✅ Nearby feed with geo-proximity
3. ✅ For You feed with temporal decay
4. ✅ Map view with real-time pins
5. ✅ "I'm down" button and notifications
6. ✅ Post-to-event conversion flow

### Phase 2: Enhanced Discovery
1. ⏳ Implicit communities
2. ⏳ Advanced vibe matching
3. ⏳ Micro-groups from recurring posts
4. ⏳ AI-powered mood detection

---

## Success Metrics

### Post Engagement
- **Creation Rate**: Posts created per active user per week
- **Reaction Rate**: % of posts receiving "I'm down" reactions
- **Conversion Rate**: % of posts evolving into events
- **Expiration Rate**: % of posts expiring without engagement

### Discovery Effectiveness
- **Nearby Feed CTR**: Click-through rate on nearby posts
- **For You Feed CTR**: Click-through rate on personalized posts
- **Map View Engagement**: Pins tapped per session
- **Quick Chat Initiation**: % of posts leading to DMs

### Post-to-Event Evolution
- **Conversion Trigger Rate**: % of posts hitting engagement threshold
- **Conversion Completion Rate**: % of suggested conversions completed
- **RSVP Conversion Rate**: % of engaged users who RSVP to converted event
- **Event Success Rate**: % of converted events that actually happen

---

## Technical Considerations

### Performance
- PostGIS spatial indexes for fast geo-queries
- Redis caching for nearby feed results
- WebSocket for real-time post updates
- Cron job for post expiration (hourly)

### Scalability
- Partition posts table by creation date
- Archive expired posts after 30 days
- Horizontal scaling for feed generation
- CDN for map tile serving

### Data Retention
- Active posts: 24-48h in primary storage
- Expired posts: 30 days in archive
- Converted posts: Permanent (linked to events)
- Analytics data: Permanent (aggregated)

