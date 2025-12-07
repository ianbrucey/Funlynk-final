# Database Schema

**Generated:** 2025-12-02  
**Database:** PostgreSQL with PostGIS extension

## Core Tables

### users
Primary user accounts and profiles.

```sql
id                          uuid PRIMARY KEY
email                       varchar UNIQUE NOT NULL
username                    varchar UNIQUE NOT NULL
display_name                varchar
password                    varchar
email_verified_at           timestamp
onboarding_completed_at     timestamp
bio                         text
profile_image_url           varchar
location_name               varchar
location_coordinates        geography(Point, 4326)  -- PostGIS
interests                   jsonb
is_host                     boolean DEFAULT false
stripe_account_id           varchar
stripe_onboarding_complete  boolean DEFAULT false
follower_count              integer DEFAULT 0
following_count             integer DEFAULT 0
activity_count              integer DEFAULT 0
is_verified                 boolean DEFAULT false
is_active                   boolean DEFAULT true
privacy_level               varchar DEFAULT 'public'
notification_preferences    jsonb
created_at                  timestamp
updated_at                  timestamp
```

**Indexes:**
- `users_location_coordinates_index` (GIST) - Spatial queries
- `users_username_index` (GIN) - Full-text search with trigrams
- `users_display_name_index` (GIN) - Full-text search with trigrams

### posts
Ephemeral activity proposals (24-48h lifespan).

```sql
id                      uuid PRIMARY KEY
user_id                 uuid FOREIGN KEY → users.id
title                   varchar NOT NULL
description             text
location_name           varchar
location_coordinates    geography(Point, 4326)  -- PostGIS
time_hint               varchar
approximate_time        timestamp
tags                    jsonb
mood                    varchar
expires_at              timestamp NOT NULL
evolved_to_event_id     uuid FOREIGN KEY → activities.id
reaction_count          integer DEFAULT 0
invitation_count        integer DEFAULT 0
conversion_suggested_at timestamp
conversion_prompted_at  timestamp
created_at              timestamp
updated_at              timestamp
```

**Indexes:**
- `posts_location_coordinates_index` (GIST) - Spatial queries
- `posts_user_id_index` - User's posts
- `posts_expires_at_index` - TTL cleanup
- `posts_evolved_to_event_id_index` - Conversion tracking

### activities
Structured events with RSVPs and payments.

```sql
id                      uuid PRIMARY KEY
user_id                 uuid FOREIGN KEY → users.id
group_id                uuid FOREIGN KEY → groups.id (nullable)
title                   varchar NOT NULL
description             text
location_name           varchar NOT NULL
location_coordinates    geography(Point, 4326)  -- PostGIS
start_time              timestamp NOT NULL
end_time                timestamp
capacity                integer
price                   decimal(10,2) DEFAULT 0
is_paid                 boolean DEFAULT false
status                  varchar DEFAULT 'draft'
originated_from_post_id uuid FOREIGN KEY → posts.id (nullable)
rsvp_count              integer DEFAULT 0
created_at              timestamp
updated_at              timestamp
```

**Indexes:**
- `activities_location_coordinates_index` (GIST) - Spatial queries
- `activities_user_id_index` - Host's activities
- `activities_group_id_index` - Group activities
- `activities_start_time_index` - Chronological queries
- `activities_originated_from_post_id_index` - Conversion tracking

### post_reactions
User engagement with Posts.

```sql
id          uuid PRIMARY KEY
post_id     uuid FOREIGN KEY → posts.id
user_id     uuid FOREIGN KEY → users.id
type        varchar NOT NULL  -- 'im_down', 'join_me', 'interested'
created_at  timestamp
updated_at  timestamp

UNIQUE(post_id, user_id)  -- One reaction per user per post
```

**Indexes:**
- `post_reactions_post_id_user_id_index` (UNIQUE) - Prevent duplicates
- `post_reactions_post_id_index` - Count reactions per post

### post_conversions
Tracking Post → Activity evolution.

```sql
id                          uuid PRIMARY KEY
post_id                     uuid FOREIGN KEY → posts.id
activity_id                 uuid FOREIGN KEY → activities.id
converted_by_user_id        uuid FOREIGN KEY → users.id
conversion_type             varchar  -- 'manual', 'suggested', 'auto'
reaction_count_at_conversion integer
suggestion_sent_at          timestamp
prompt_sent_at              timestamp
converted_at                timestamp
created_at                  timestamp
updated_at                  timestamp
```

### rsvps
Event attendance commitments.

```sql
id              uuid PRIMARY KEY
activity_id     uuid FOREIGN KEY → activities.id
user_id         uuid FOREIGN KEY → users.id
status          varchar DEFAULT 'pending'  -- 'pending', 'confirmed', 'cancelled'
payment_status  varchar DEFAULT 'unpaid'   -- 'unpaid', 'paid', 'refunded'
payment_intent  varchar (nullable)
amount_paid     decimal(10,2) DEFAULT 0
created_at      timestamp
updated_at      timestamp

UNIQUE(activity_id, user_id)
```

### tags
Activity categorization.

```sql
id          uuid PRIMARY KEY
name        varchar UNIQUE NOT NULL
slug        varchar UNIQUE NOT NULL
category    varchar
usage_count integer DEFAULT 0
created_at  timestamp
updated_at  timestamp
```

**Pivot Table:** `activity_tag` (many-to-many)

### follows
User social connections.

```sql
id           uuid PRIMARY KEY
follower_id  uuid FOREIGN KEY → users.id
following_id uuid FOREIGN KEY → users.id
created_at   timestamp
updated_at   timestamp

UNIQUE(follower_id, following_id)
```

## Chat System Tables

### conversations
Multi-context chat channels.

```sql
id                  uuid PRIMARY KEY
type                varchar NOT NULL  -- 'post', 'activity', 'group', 'direct'
post_id             uuid FOREIGN KEY → posts.id (nullable)
activity_id         uuid FOREIGN KEY → activities.id (nullable)
group_id            uuid FOREIGN KEY → groups.id (nullable)
last_message_at     timestamp
created_at          timestamp
updated_at          timestamp
```

### conversation_participants
Users in conversations.

```sql
id                  uuid PRIMARY KEY
conversation_id     uuid FOREIGN KEY → conversations.id
user_id             uuid FOREIGN KEY → users.id
joined_at           timestamp
last_read_at        timestamp
request_status      varchar  -- 'pending', 'accepted', 'rejected' (DMs only)
created_at          timestamp
updated_at          timestamp

UNIQUE(conversation_id, user_id)
```

### messages
Chat messages.

```sql
id                  uuid PRIMARY KEY
conversation_id     uuid FOREIGN KEY → conversations.id
user_id             uuid FOREIGN KEY → users.id
content             text NOT NULL
is_system_message   boolean DEFAULT false
created_at          timestamp
updated_at          timestamp
```

**Indexes:**
- `messages_conversation_id_index` - Fetch conversation history
- `messages_created_at_index` - Chronological ordering

## Groups Tables

### groups
Communities organized around interests/locations.

```sql
id                  uuid PRIMARY KEY
name                varchar NOT NULL
slug                varchar UNIQUE NOT NULL
description         text
location_name       varchar
location_coordinates geography(Point, 4326)  -- PostGIS
privacy             varchar DEFAULT 'public'  -- 'public', 'private'
member_count        integer DEFAULT 0
created_by_user_id  uuid FOREIGN KEY → users.id
created_at          timestamp
updated_at          timestamp
```

### group_members
Group membership and roles.

```sql
id          uuid PRIMARY KEY
group_id    uuid FOREIGN KEY → groups.id
user_id     uuid FOREIGN KEY → users.id
role        varchar DEFAULT 'member'  -- 'admin', 'member'
joined_at   timestamp
created_at  timestamp
updated_at  timestamp

UNIQUE(group_id, user_id)
```

## Payment Tables

### stripe_accounts
Host payment accounts.

```sql
id                      uuid PRIMARY KEY
user_id                 uuid FOREIGN KEY → users.id
stripe_account_id       varchar UNIQUE NOT NULL
onboarding_complete     boolean DEFAULT false
charges_enabled         boolean DEFAULT false
payouts_enabled         boolean DEFAULT false
created_at              timestamp
updated_at              timestamp
```

### transactions
Payment records.

```sql
id                  uuid PRIMARY KEY
rsvp_id             uuid FOREIGN KEY → rsvps.id
stripe_payment_intent varchar UNIQUE
amount              decimal(10,2) NOT NULL
platform_fee        decimal(10,2) DEFAULT 0
host_payout         decimal(10,2) NOT NULL
status              varchar DEFAULT 'pending'
created_at          timestamp
updated_at          timestamp
```

## Notification System

### notifications
User alerts and updates.

```sql
id              uuid PRIMARY KEY
user_id         uuid FOREIGN KEY → users.id
type            varchar NOT NULL
data            jsonb NOT NULL
read_at         timestamp (nullable)
created_at      timestamp
updated_at      timestamp
```

**Indexes:**
- `notifications_user_id_index` - User's notifications
- `notifications_read_at_index` - Unread filtering

## Key Relationships

```
User
  ├── hasMany: Post, Activity, PostReaction, Rsvp, Follow (as follower/following)
  ├── belongsToMany: Tag (via activities), Group (via group_members)
  └── hasOne: StripeAccount

Post
  ├── belongsTo: User
  ├── hasMany: PostReaction, PostInvitation
  ├── hasOne: PostConversion, Activity (evolved_to_event_id)
  └── hasOne: Conversation

Activity
  ├── belongsTo: User, Group (nullable), Post (originated_from_post_id, nullable)
  ├── hasMany: Rsvp, ActivityInvitation
  ├── belongsToMany: Tag
  └── hasOne: Conversation

Group
  ├── belongsTo: User (created_by)
  ├── hasMany: GroupMember, Activity, Post
  ├── belongsToMany: Tag
  └── hasOne: Conversation

Conversation
  ├── belongsTo: Post, Activity, Group (polymorphic via type)
  ├── hasMany: Message, ConversationParticipant
  └── belongsToMany: User (via conversation_participants)
```

