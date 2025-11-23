# E05 Social Interaction - Laravel Documentation Guide

## Epic Context
**Purpose**: Transform FunLynk into a vibrant social community with comments, reactions, sharing, and real-time features.
**Reference**: `context-engine/epics/E05_Social_Interaction/epic-overview.md`

**CRITICAL**: Comments work on BOTH Posts (ephemeral) AND Events (structured). Use polymorphic relationships.

## Features to Document (4 total)

### F01: Comment & Discussion System
**Purpose**: Rich commenting on activities/posts with threading, reactions, moderation
**Key Components**:
- Threaded comments (polymorphic: activities AND posts)
- Rich text with mentions (@username)
- Comment reactions (like, helpful, funny)
- Real-time updates via Livewire polling or Laravel Echo
- Moderation tools in Filament

**E01 Integration**:
- Uses `comments` table (commentable_type, commentable_id)
- Uses `Comment` model with polymorphic relationships
- Enhances `CommentResource` in Filament

**Suggested Tasks (5-7 tasks, 25-35 hours)**:
- T01: Enhance Comment Model & Relationships (3-4h)
- T02: CommentService with Threading Logic (4-5h)
- T03: Filament CommentResource Enhancement (3-4h)
- T04: Livewire Comment Components (5-6h)
- T05: Comment Moderation Policies (3-4h)
- T06: Real-time Comment Updates (4-5h)
- T07: Comment Tests (3-4h)

**Key Packages**:
- `tiptap/tiptap` or `ckeditor` for rich text (or simple textarea with markdown)
- Laravel Echo + Pusher/Soketi for real-time (optional Phase 2)

---

### F02: Social Sharing & Engagement
**Purpose**: Share activities/posts, reactions, bookmarks, social proof
**Key Components**:
- Share to external platforms (social media links)
- Internal sharing (notifications to followers)
- Bookmark/save functionality
- Reaction system (like, love, excited)
- Social proof indicators

**E01 Integration**:
- May need new `bookmarks` table (user_id, bookmarkable_type, bookmarkable_id)
- May need new `shares` table for tracking
- Uses existing `post_reactions` table for posts
- May need `activity_reactions` table for events

**Suggested Tasks (5-7 tasks, 25-35 hours)**:
- T01: Database Schema for Bookmarks & Shares (2-3h)
- T02: BookmarkService & ShareService (4-5h)
- T03: Filament Resources for Bookmarks (2-3h)
- T04: Livewire Bookmark & Share Components (5-6h)
- T05: Social Proof Calculations (3-4h)
- T06: External Share Integration (4-5h)
- T07: Engagement Tests (3-4h)

**Key Packages**:
- `spatie/laravel-share` for social media sharing
- Laravel notifications for internal sharing

---

### F03: Community Features
**Purpose**: Auto-generated communities around activities, interests, locations
**Key Components**:
- Community creation (auto-generated or manual)
- Community membership and roles
- Community discussions (uses comments)
- Community discovery
- Community moderation

**E01 Integration**:
- New `communities` table (name, slug, type, location_coordinates)
- New `community_members` pivot table (community_id, user_id, role)
- Uses `comments` for community discussions
- Uses PostGIS for location-based communities

**Suggested Tasks (6-7 tasks, 30-40 hours)**:
- T01: Community Database Schema (3-4h)
- T02: Community & CommunityMember Models (3-4h)
- T03: CommunityService with Auto-generation Logic (5-6h)
- T04: Filament CommunityResource (4-5h)
- T05: Livewire Community Components (6-7h)
- T06: Community Policies & Moderation (4-5h)
- T07: Community Tests (4-5h)

**Key Packages**:
- matanyadaev/laravel-eloquent-spatial for location-based communities
- Laravel Scout for community search (optional)

---

### F04: Real-time Social Features
**Purpose**: Live chat, instant reactions, presence indicators, real-time notifications
**Key Components**:
- Activity-specific chat rooms
- Live reactions during events
- User presence indicators
- Real-time notifications
- Instant messaging (DMs)

**E01 Integration**:
- New `messages` table (sender_id, recipient_id, content, read_at)
- New `chat_rooms` table (activity_id, type)
- New `chat_messages` table (chat_room_id, user_id, content)
- Uses `notifications` table for real-time alerts

**Suggested Tasks (6-7 tasks, 30-40 hours)**:
- T01: Chat Database Schema (3-4h)
- T02: Message & ChatRoom Models (3-4h)
- T03: ChatService & MessageService (5-6h)
- T04: Filament Chat Management Resources (3-4h)
- T05: Livewire Chat Components (7-8h)
- T06: Laravel Echo Integration (5-6h)
- T07: Real-time Tests (4-5h)

**Key Packages**:
- Laravel Echo + Pusher/Soketi for WebSockets
- `beyondcode/laravel-websockets` for self-hosted WebSockets
- Laravel Broadcasting

---

## Common Patterns Across All Features

### Database Migrations
```bash
php artisan make:migration create_bookmarks_table --no-interaction
php artisan make:migration create_communities_table --no-interaction
php artisan make:migration create_messages_table --no-interaction
```

### Models
```bash
php artisan make:model Bookmark --no-interaction
php artisan make:model Community --no-interaction
php artisan make:model Message --no-interaction
```

### Filament Resources
```bash
php artisan make:filament-resource Bookmark --generate --no-interaction
php artisan make:filament-resource Community --generate --no-interaction
php artisan make:filament-resource Message --generate --no-interaction
```

### Service Classes
```bash
php artisan make:class Services/CommentService --no-interaction
php artisan make:class Services/BookmarkService --no-interaction
php artisan make:class Services/CommunityService --no-interaction
php artisan make:class Services/ChatService --no-interaction
```

### Livewire Components
```bash
php artisan make:livewire Comments/CommentThread --no-interaction
php artisan make:livewire Social/BookmarkButton --no-interaction
php artisan make:livewire Communities/CommunityCard --no-interaction
php artisan make:livewire Chat/ChatWindow --no-interaction
```

### Policies
```bash
php artisan make:policy CommentPolicy --model=Comment --no-interaction
php artisan make:policy CommunityPolicy --model=Community --no-interaction
```

### Tests
```bash
php artisan make:test --pest Feature/CommentThreadTest --no-interaction
php artisan make:test --pest Feature/BookmarkTest --no-interaction
php artisan make:test --pest Feature/CommunityTest --no-interaction
php artisan make:test --pest Feature/ChatTest --no-interaction
```

---

## Testing Checklist

### F01: Comment & Discussion System
- [ ] Can create threaded comments on activities and posts
- [ ] Can mention users with @username
- [ ] Can react to comments
- [ ] Can moderate/delete comments (host/admin)
- [ ] Real-time updates work correctly

### F02: Social Sharing & Engagement
- [ ] Can bookmark activities and posts
- [ ] Can share to external platforms
- [ ] Can share internally to followers
- [ ] Social proof displays correctly
- [ ] Engagement metrics tracked

### F03: Community Features
- [ ] Can create communities (manual and auto-generated)
- [ ] Can join/leave communities
- [ ] Can post in community discussions
- [ ] Community discovery works
- [ ] Community moderation enforced

### F04: Real-time Social Features
- [ ] Can send/receive direct messages
- [ ] Can join activity chat rooms
- [ ] Live reactions work during events
- [ ] Presence indicators accurate
- [ ] Real-time notifications delivered

