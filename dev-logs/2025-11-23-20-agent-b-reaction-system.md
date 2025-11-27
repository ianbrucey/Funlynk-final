# Agent B: Post Reaction System Backend
**Date**: 2025-11-23 20:00  
**Epic**: E04 Discovery Engine + E05 Social Interaction  
**Estimated Time**: 12-15 hours

## Context

Build the complete backend for post reactions with real-time notifications:
- **"I'm Down"**: Signal intent to participate (triggers post-to-event conversion at 5+/10+ reactions)
- **"Invite Friends"**: Share post with selected friends (renamed from "join_me")
- **Single Channel Architecture**: Each user has ONE WebSocket channel (`user.{userId}`) for ALL notifications

## Your Tasks

### Task 1: Update Reaction Types (30 min)

**Update Model**:
```bash
# File: app/Models/PostReaction.php
# Change validReactionTypes() to return ['im_down', 'invite_friends']
```

**Create Migration**:
```bash
php artisan make:migration update_post_reaction_types --no-interaction
# Update existing 'join_me' reactions to 'invite_friends'
DB::table('post_reactions')->where('reaction_type', 'join_me')->update(['reaction_type' => 'invite_friends']);
```

**Update Factory**:
```bash
# File: database/factories/PostReactionFactory.php
# Change to: fake()->randomElement(['im_down', 'invite_friends'])
```

---

### Task 2: Create Post Invitations System (2 hours)

**Create Migration**:
```bash
php artisan make:migration create_post_invitations_table --no-interaction
```

**Schema**:
```php
Schema::create('post_invitations', function (Blueprint $table) {
    $table->uuid('id')->primary();
    $table->foreignUuid('post_id')->constrained()->onDelete('cascade');
    $table->foreignUuid('inviter_id')->constrained('users')->onDelete('cascade');
    $table->foreignUuid('invitee_id')->constrained('users')->onDelete('cascade');
    $table->enum('status', ['pending', 'viewed', 'reacted', 'ignored'])->default('pending');
    $table->timestampTz('created_at')->useCurrent();
    $table->timestampTz('viewed_at')->nullable();
    $table->timestampTz('reacted_at')->nullable();
    
    $table->unique(['post_id', 'inviter_id', 'invitee_id']);
    $table->index(['post_id']);
    $table->index(['invitee_id', 'status']);
});
```

**Create Model**:
```bash
php artisan make:model PostInvitation --no-interaction
```

**Add to PostService** (`app/Services/PostService.php`):
```php
public function inviteFriendsToPost(string $postId, array $friendIds, ?User $inviter = null): Collection
{
    $inviter = $inviter ?? auth()->user();
    $post = Post::findOrFail($postId);
    $invitations = collect();
    
    foreach ($friendIds as $friendId) {
        $invitation = PostInvitation::updateOrCreate(
            ['post_id' => $postId, 'inviter_id' => $inviter->id, 'invitee_id' => $friendId],
            ['status' => 'pending', 'created_at' => now()]
        );
        
        event(new PostInvitationSent($invitation, $post, $inviter, User::find($friendId)));
        $invitations->push($invitation);
    }
    
    return $invitations;
}

public function getPostInvitees(string $postId): Collection
{
    return PostInvitation::where('post_id', $postId)->with('invitee')->get();
}

public function markInvitationViewed(string $invitationId): void
{
    PostInvitation::where('id', $invitationId)->update(['viewed_at' => now(), 'status' => 'viewed']);
}

public function getUserPendingInvitations(string $userId): Collection
{
    return PostInvitation::where('invitee_id', $userId)
        ->where('status', 'pending')
        ->with(['post', 'inviter'])
        ->orderBy('created_at', 'desc')
        ->get();
}
```

---

### Task 3: Real-Time Notification System (3 hours)

**Create User Channel**:
```bash
php artisan make:channel UserChannel --no-interaction
```

**File**: `app/Broadcasting/UserChannel.php`
```php
public function join(User $user, string $userId): bool
{
    return $user->id === $userId;
}
```

**Register Channel** in `routes/channels.php`:
```php
Broadcast::channel('user.{userId}', \App\Broadcasting\UserChannel::class);
```

**Update PostReacted Event** (`app/Events/PostReacted.php`):
```php
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Broadcasting\Channel;

class PostReacted implements ShouldBroadcast
{
    // ... existing code ...
    
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
                'reactor_avatar' => $reactor->avatar_url ?? null,
                'reaction_count' => $this->post->reaction_count,
                'conversion_eligible' => $this->conversionEligibility['eligible'],
            ],
            'actions' => [
                ['label' => 'View Post', 'route' => "/posts/{$this->post->id}"],
            ],
        ];
    }
}
```

**Create PostInvitationSent Event**:
```bash
php artisan make:event PostInvitationSent --no-interaction
```

**File**: `app/Events/PostInvitationSent.php`
```php
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;

class PostInvitationSent implements ShouldBroadcast
{
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
                'inviter_id' => $this->inviter->id,
                'inviter_name' => $this->inviter->name,
                'inviter_avatar' => $this->inviter->avatar_url ?? null,
            ],
            'actions' => [
                ['label' => 'View Post', 'route' => "/posts/{$this->post->id}"],
            ],
        ];
    }
}
```

---

### Task 4: Notification Persistence (1 hour)

**Create Listener**:
```bash
php artisan make:listener SendPostReactionNotification --event=PostReacted --no-interaction
```

**File**: `app/Listeners/SendPostReactionNotification.php`
```php
public function handle(PostReacted $event): void
{
    Notification::create([
        'user_id' => $event->post->user_id,
        'type' => 'post_reaction',
        'title' => "{$event->reaction->user->name} reacted to your post",
        'message' => "Someone is down for \"{$event->post->title}\"",
        'data' => [
            'post_id' => $event->post->id,
            'reactor_id' => $event->reaction->user_id,
            'reaction_type' => $event->reaction->reaction_type,
        ],
    ]);
}
```

**Create Listener**:
```bash
php artisan make:listener SendPostInvitationNotification --event=PostInvitationSent --no-interaction
```

**Register in EventServiceProvider**.

---

### Task 5: API Endpoints (2 hours)

**Create Controller**:
```bash
php artisan make:controller Api/PostReactionController --no-interaction
```

**Add Routes** to `routes/api.php`:
```php
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/posts/{post}/react', [PostReactionController::class, 'react']);
    Route::delete('/posts/{post}/react', [PostReactionController::class, 'unreact']);
    Route::get('/posts/{post}/reactions', [PostReactionController::class, 'getReactions']);
    Route::post('/posts/{post}/invite', [PostReactionController::class, 'invite']);
    Route::get('/users/me/invitations', [PostReactionController::class, 'getInvitations']);
});
```

**Implement Controller Methods** (react, unreact, getReactions, invite, getInvitations).

---

### Task 6: Post-to-Event Conversion (2 hours)

**Create Job**:
```bash
php artisan make:job CheckPostConversionEligibility --no-interaction
```

**Create Listener**:
```bash
php artisan make:listener CheckPostConversion --event=PostReacted --no-interaction
```

**Dispatch job only for "im_down" reactions**.

---

## Testing

Write tests in `tests/Feature/PostReactionSystemTest.php`:
- Test reaction creation
- Test invitation creation
- Test broadcasting
- Test conversion triggers

---

## Success Criteria

✅ Reaction types updated to "im_down" and "invite_friends"
✅ Post invitations table and model created
✅ Real-time notifications broadcast to `user.{userId}` channel
✅ Notifications persist in database
✅ API endpoints work
✅ Conversion triggers at 5+ and 10+ reactions
✅ All tests passing

---

**Start with Task 1, then proceed sequentially. Ask questions if anything is unclear.**

