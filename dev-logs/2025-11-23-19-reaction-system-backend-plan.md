# Post Reaction System - Backend Implementation Plan
**Date**: 2025-11-23 19:30  
**Epic**: E04 Discovery Engine + E05 Social Interaction  
**Status**: Planning Phase

## Overview
Complete backend implementation for post reactions ("I'm Down" and "Invite Friends") with real-time notifications using a single-channel-per-user architecture.

---

## Reaction Types

### 1. "I'm Down" Reaction
**Database Value**: `im_down`
**Purpose**: Signal intent to participate
**Behavior**:
- Instant notification to post creator
- Adds user to "interested users" list
- Increases post visibility in feeds
- Contributes to post-to-event conversion threshold (5+ → suggest, 10+ → auto-convert)

### 2. "Invite Friends" Reaction
**Database Value**: `invite_friends` (rename from `join_me`)
**Purpose**: Share post with selected friends
**Behavior**:
- Opens friend selector modal
- Sends notification to selected friends with post link
- Tracks invitation success rate
- Helps posts go viral

---

## Architecture: Single Channel Per User

### Notification Channel Design
**Principle**: Each user has ONE WebSocket channel that receives ALL notifications

**Channel Name**: `user.{user_id}`
**Example**: `user.123e4567-e89b-12d3-a456-426614174000`

**Benefits**:
- Single WebSocket connection per user
- No need to subscribe to multiple channels
- Easy to scale and manage
- Client-side routing based on notification type

**Notification Payload Structure**:
```json
{
  "id": "notif-uuid",
  "type": "post_reaction",
  "subtype": "im_down",
  "timestamp": "2025-11-23T19:30:00Z",
  "data": {
    "post_id": "post-uuid",
    "reactor_id": "user-uuid",
    "reactor_name": "John Doe",
    "reactor_avatar": "https://...",
    "post_title": "Coffee at Starbucks",
    "reaction_count": 5,
    "conversion_eligible": true
  },
  "actions": [
    {"label": "View Post", "route": "/posts/{post_id}"},
    {"label": "Convert to Event", "route": "/posts/{post_id}/convert"}
  ]
}
```

---

## Backend Tasks Breakdown

### **Task 1: Update Database Schema** (Agent B)
**Priority**: P0 - Foundation
**Epic**: E04 Discovery Engine
**Estimated Time**: 30 minutes

#### 1.1 Update PostReaction Model
**File**: `app/Models/PostReaction.php`

**Changes**:
```php
public static function validReactionTypes(): array
{
    return ['im_down', 'invite_friends']; // Changed from 'join_me'
}
```

#### 1.2 Create Migration for Reaction Type Update
**File**: `database/migrations/2025_11_23_XXXXXX_update_post_reaction_types.php`

**Changes**:
```php
// Update existing 'join_me' reactions to 'invite_friends'
DB::table('post_reactions')
    ->where('reaction_type', 'join_me')
    ->update(['reaction_type' => 'invite_friends']);
```

#### 1.3 Update Factory
**File**: `database/factories/PostReactionFactory.php`

**Changes**:
```php
'reaction_type' => fake()->randomElement(['im_down', 'invite_friends']),
```

---

### **Task 2: Implement Friend Invitation System** (Agent B)
**Priority**: P1 - Core Feature
**Epic**: E04 Discovery Engine
**Estimated Time**: 2-3 hours

#### 2.1 Create PostInvitation Model & Migration
**File**: `app/Models/PostInvitation.php`
**File**: `database/migrations/2025_11_23_XXXXXX_create_post_invitations_table.php`

**Schema**:
```sql
CREATE TABLE post_invitations (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    post_id UUID NOT NULL REFERENCES posts(id) ON DELETE CASCADE,
    inviter_id UUID NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    invitee_id UUID NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    status VARCHAR(20) DEFAULT 'pending', -- pending, viewed, reacted, ignored
    created_at TIMESTAMP WITH TIME ZONE DEFAULT NOW(),
    viewed_at TIMESTAMP WITH TIME ZONE,
    reacted_at TIMESTAMP WITH TIME ZONE,
    
    UNIQUE(post_id, inviter_id, invitee_id),
    INDEX idx_post_invitations_post (post_id),
    INDEX idx_post_invitations_invitee (invitee_id, status)
);
```

#### 2.2 Add Methods to PostService
**File**: `app/Services/PostService.php`

**New Methods**:
```php
/**
 * Invite friends to a post
 * 
 * @param string $postId
 * @param array $friendIds Array of user IDs to invite
 * @param User|null $inviter
 * @return Collection PostInvitation instances
 */
public function inviteFriendsToPost(string $postId, array $friendIds, ?User $inviter = null): Collection

/**
 * Get users who were invited to a post
 */
public function getPostInvitees(string $postId): Collection

/**
 * Mark invitation as viewed
 */
public function markInvitationViewed(string $invitationId): void

/**
 * Get pending invitations for a user
 */
public function getUserPendingInvitations(string $userId): Collection
```

---

### **Task 3: Real-Time Notification System** (Agent B)
**Priority**: P1 - Core Feature
**Epic**: E01 Core Infrastructure + E05 Social Interaction
**Estimated Time**: 3-4 hours

#### 3.1 Create User Notification Channel
**File**: `app/Broadcasting/UserChannel.php`

**Implementation**:
```php
<?php

namespace App\Broadcasting;

use App\Models\User;

class UserChannel
{
    public function join(User $user, string $userId): bool
    {
        // User can only join their own channel
        return $user->id === $userId;
    }
}
```

**Register in**: `routes/channels.php`
```php
Broadcast::channel('user.{userId}', UserChannel::class);
```

#### 3.2 Create Base Notification Event
**File**: `app/Events/UserNotification.php`

**Implementation**:
```php
<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class UserNotification implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public string $userId,
        public string $type,
        public string $subtype,
        public array $data,
        public array $actions = []
    ) {}

    public function broadcastOn(): Channel
    {
        return new Channel("user.{$this->userId}");
    }

    public function broadcastAs(): string
    {
        return 'notification';
    }

    public function broadcastWith(): array
    {
        return [
            'id' => (string) \Illuminate\Support\Str::uuid(),
            'type' => $this->type,
            'subtype' => $this->subtype,
            'timestamp' => now()->toIso8601String(),
            'data' => $this->data,
            'actions' => $this->actions,
        ];
    }
}
```


