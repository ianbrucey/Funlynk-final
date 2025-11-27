# Post Reaction System - Backend Plan (Part 2)
**Continuation of**: `2025-11-23-19-reaction-system-backend-plan.md`

---

#### 3.3 Update PostReacted Event
**File**: `app/Events/PostReacted.php`

**Add Broadcasting**:
```php
class PostReacted implements ShouldBroadcast
{
    // ... existing code ...
    
    public function __construct(
        public Post $post,
        public PostReaction $reaction,
        public array $conversionEligibility
    ) {}
    
    // Broadcast to post creator's channel
    public function broadcastOn(): Channel
    {
        return new Channel("user.{$this->post->user_id}");
    }
    
    public function broadcastAs(): string
    {
        return 'notification';
    }
    
    public function broadcastWith(): array
    {
        $reactor = $this->reaction->user;
        
        return [
            'id' => (string) \Illuminate\Support\Str::uuid(),
            'type' => 'post_reaction',
            'subtype' => $this->reaction->reaction_type,
            'timestamp' => now()->toIso8601String(),
            'data' => [
                'post_id' => $this->post->id,
                'post_title' => $this->post->title,
                'reactor_id' => $reactor->id,
                'reactor_name' => $reactor->name,
                'reactor_avatar' => $reactor->avatar_url,
                'reaction_count' => $this->post->reaction_count,
                'conversion_eligible' => $this->conversionEligibility['eligible'],
                'auto_convert' => $this->conversionEligibility['auto_convert'],
            ],
            'actions' => [
                ['label' => 'View Post', 'route' => "/posts/{$this->post->id}"],
                ...$this->conversionEligibility['eligible'] 
                    ? [['label' => 'Convert to Event', 'route' => "/posts/{$this->post->id}/convert"]]
                    : []
            ],
        ];
    }
}
```

#### 3.4 Create PostInvitationSent Event
**File**: `app/Events/PostInvitationSent.php`

**Implementation**:
```php
<?php

namespace App\Events;

use App\Models\Post;
use App\Models\PostInvitation;
use App\Models\User;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class PostInvitationSent implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public PostInvitation $invitation,
        public Post $post,
        public User $inviter,
        public User $invitee
    ) {}

    public function broadcastOn(): Channel
    {
        return new Channel("user.{$this->invitee->id}");
    }

    public function broadcastAs(): string
    {
        return 'notification';
    }

    public function broadcastWith(): array
    {
        return [
            'id' => (string) \Illuminate\Support\Str::uuid(),
            'type' => 'post_invitation',
            'subtype' => 'invited',
            'timestamp' => now()->toIso8601String(),
            'data' => [
                'invitation_id' => $this->invitation->id,
                'post_id' => $this->post->id,
                'post_title' => $this->post->title,
                'post_description' => $this->post->description,
                'inviter_id' => $this->inviter->id,
                'inviter_name' => $this->inviter->name,
                'inviter_avatar' => $this->inviter->avatar_url,
            ],
            'actions' => [
                ['label' => 'View Post', 'route' => "/posts/{$this->post->id}"],
                ['label' => "I'm Down!", 'route' => "/posts/{$this->post->id}/react/im_down"],
            ],
        ];
    }
}
```

---

### **Task 4: Notification Persistence** (Agent B)
**Priority**: P1 - Core Feature
**Epic**: E01 Core Infrastructure
**Estimated Time**: 1-2 hours

#### 4.1 Use Existing Notifications Table
**Table**: `notifications` (already exists from E01)

**Columns**:
- `id` (UUID)
- `user_id` (UUID) - recipient
- `type` (VARCHAR) - notification type
- `title` (VARCHAR)
- `message` (TEXT)
- `data` (JSONB) - flexible payload
- `read_at` (TIMESTAMP)
- `created_at` (TIMESTAMP)

#### 4.2 Create Notification Service
**File**: `app/Services/NotificationService.php`

**Methods**:
```php
/**
 * Send notification to user (both real-time and persistent)
 */
public function sendNotification(
    string $userId,
    string $type,
    string $subtype,
    array $data,
    array $actions = []
): Notification

/**
 * Mark notification as read
 */
public function markAsRead(string $notificationId): void

/**
 * Get unread notifications for user
 */
public function getUnreadNotifications(string $userId): Collection

/**
 * Get all notifications for user (paginated)
 */
public function getUserNotifications(string $userId, int $perPage = 20): LengthAwarePaginator
```


