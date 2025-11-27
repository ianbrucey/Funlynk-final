# Comment System - Domain Context

## Overview

The comment system enables rich, threaded discussions on both **Posts** (ephemeral, 24-48h) and **Activities** (structured events). It uses polymorphic relationships to provide a unified commenting experience across different content types.

**Implementation Date**: November 2024  
**Epic**: E05 Social Interaction  
**Feature**: F01 Comment & Discussion System  
**Status**: ✅ Production Ready

---

## Architecture

### Polymorphic Design

The comment system uses Laravel's polymorphic relationships to work with multiple commentable entities:

```php
// Comment belongs to Post OR Activity
commentable_type: 'App\Models\Post' | 'App\Models\Activity'
commentable_id: UUID of the post or activity
```

### Threading Model

- **Max Depth**: 10 levels of nesting
- **Depth Tracking**: `depth` column automatically calculated on creation
- **Visual Hierarchy**: 2rem left indentation per level + gradient border
- **Parent-Child**: `parent_comment_id` foreign key creates tree structure

### Key Components

```
┌─────────────────────────────────────────────┐
│           Comment System Stack              │
├─────────────────────────────────────────────┤
│ UI Layer                                    │
│  ├─ CommentSection (main container)         │
│  ├─ CommentForm (create/reply)              │
│  └─ CommentItem (display + threading)       │
├─────────────────────────────────────────────┤
│ Business Logic                              │
│  └─ CommentService (CRUD + threading)       │
├─────────────────────────────────────────────┤
│ Events & Notifications                      │
│  ├─ CommentCreated (broadcast)              │
│  ├─ BroadcastCommentCreated (listener)      │
│  └─ 3 Notification Types                    │
├─────────────────────────────────────────────┤
│ Authorization                               │
│  └─ CommentPolicy (edit/delete/moderate)    │
├─────────────────────────────────────────────┤
│ Database                                    │
│  ├─ comments (polymorphic + threading)      │
│  └─ comment_reactions (likes/reactions)     │
└─────────────────────────────────────────────┘
```

---

## Database Schema

### Comments Table

```sql
comments
  - id (UUID, primary key)
  - commentable_type (string) -- 'App\Models\Post' or 'App\Models\Activity'
  - commentable_id (UUID) -- ID of the post or activity
  - user_id (UUID, foreign key to users)
  - parent_comment_id (UUID, nullable, self-referencing)
  - depth (tiny integer, default 0) -- Threading depth (0-10)
  - content (text, 1-500 chars)
  - is_edited (boolean, default false)
  - is_deleted (boolean, default false)
  - created_at (timestamp)
  - updated_at (timestamp)
  - deleted_at (timestamp, nullable) -- Soft deletes

Indexes:
  - idx_comments_commentable (commentable_type, commentable_id)
  - idx_comments_depth (depth)
  - idx_comments_user (user_id)
  - idx_comments_parent (parent_comment_id)
```

### Comment Reactions Table

```sql
comment_reactions
  - id (UUID, primary key)
  - user_id (UUID, foreign key to users)
  - comment_id (UUID, foreign key to comments)
  - reaction_type (string, default 'like') -- 'like', 'helpful', 'funny'
  - created_at (timestamp)
  - updated_at (timestamp)

Unique Constraint:
  - unique_user_comment_reaction (user_id, comment_id)
```

---

## Core Service: CommentService

**Location**: `app/Services/CommentService.php`

### Key Methods

```php
// Create a new comment
public function createComment(
    Model $commentable,      // Post or Activity
    User $user,             // Comment author
    string $content,        // Comment text (1-500 chars)
    ?Comment $parent = null // Optional parent for replies
): Comment

// Update existing comment
public function updateComment(Comment $comment, string $content): Comment

// Soft delete comment
public function deleteComment(Comment $comment): bool

// Get paginated comments with threading
public function getCommentsForEntity(Model $commentable, int $perPage = 20)

// Get total comment count
public function getCommentCount(Model $commentable): int

// Check if comment can have replies (depth < 10)
public function canReply(Comment $comment): bool
```

### Business Rules

1. **Content Validation**:
   - Min: 1 character (after trim)
   - Max: 500 characters
   - Cannot be empty or whitespace-only

2. **Threading Rules**:
   - Depth starts at 0 (top-level comment)
   - Each reply increments depth by 1
   - Max depth is 10 (enforced by service)
   - Parent must belong to same commentable entity

3. **Rate Limiting**:
   - 5 comments per minute per user
   - Enforced via Laravel's RateLimiter facade
   - Key format: `comment-creation:{user_id}`

4. **@Mentions**:
   - Parsed via regex: `/@([\w]+)/`
   - Creates notifications for mentioned users
   - Mentions are NOT validated (user might not exist)

---

## Livewire Components

### 1. CommentSection (Container)

**File**: `app/Livewire/Comments/CommentSection.php`  
**View**: `resources/views/livewire/comments/comment-section.blade.php`

**Purpose**: Main container for the entire comment system on a detail page.

**Props**:
```php
public string $commentableType;  // 'App\Models\Post' or 'App\Models\Activity'
public string $commentableId;    // UUID of the entity
```

**Features**:
- Displays total comment count
- Embeds CommentForm for new comments
- Lists all top-level comments with CommentItem
- Handles pagination (20 comments per page)
- Listens for `comment-created` and `comment-deleted` events
- Shows "Sign in to comment" for guests

**Usage**:
```blade
<livewire:comments.comment-section
    :commentable-type="'App\\Models\\Post'"
    :commentable-id="$post->id"
/>
```

---

### 2. CommentForm (Input)

**File**: `app/Livewire/Comments/CommentForm.php`  
**View**: `resources/views/livewire/comments/comment-form.blade.php`

**Purpose**: Form for creating new comments or replies.

**Props**:
```php
public Model $commentable;      // The post or activity
public ?Comment $parent = null; // Parent comment for replies
```

**Features**:
- Character counter (X/500)
- Rate limiting validation
- Visual distinction for replies vs top-level
- Cancel button for reply forms
- Dispatches `comment-created` event on success

**Validation**:
```php
[
    'content' => 'required|string|min:1|max:500',
]
```

---

### 3. CommentItem (Display)

**File**: `app/Livewire/Comments/CommentItem.php`  
**View**: `resources/views/livewire/comments/comment-item.blade.php`

**Purpose**: Displays individual comment with threading, actions, and nested replies.

**Props**:
```php
public Comment $comment;    // The comment to display
public bool $canReply;      // Whether reply is allowed (depth < 10)
```

**Features**:
- User avatar with initials
- Username and timestamp
- Comment content
- Depth badge for nested comments
- Reply button (if depth allows)
- Delete button (own comments only)
- Reaction counts
- Recursive rendering of nested replies

**Visual Threading**:
```css
/* Indentation increases with depth */
margin-left: {{ $comment->depth * 2 }}rem;

/* Gradient border for nested comments */
.absolute -left-4 top-0 bottom-0 w-0.5 bg-gradient-to-b from-cyan-500/50 to-purple-500/50
```

---

## Events & Notifications

### CommentCreated Event

**File**: `app/Events/CommentCreated.php`

**Purpose**: Broadcasts new comments via Laravel Reverb (WebSocket).

**Broadcast Channel**:
```php
"comments.{commentable_type}.{commentable_id}"
// Example: "comments.App\Models\Post.550e8400-e29b-41d4-a716-446655440000"
```

**Broadcast Data**:
```php
[
    'comment' => [
        'id' => '...',
        'content' => '...',
        'user' => ['id', 'username', 'name'],
        'parent_comment_id' => '...',
        'depth' => 1,
        'created_at' => '2024-11-27T04:00:00.000000Z',
    ],
]
```

---

### Notification Strategy

**Listener**: `app/Listeners/BroadcastCommentCreated.php`  
**Queued**: Yes (implements `ShouldQueue`)

#### Who Gets Notified:

1. **Content Owner** (`CommentOnYourContentNotification`)
   - Post owner (via `user_id`) OR Activity host (via `host_id`)
   - Message: "Bob commented on your post"
   - **Not sent if**: Commenter is the content owner

2. **Parent Comment Author** (`ReplyToYourCommentNotification`)
   - User who wrote the parent comment
   - Message: "Charlie replied to your comment"
   - **Not sent if**: Replier is the parent author OR content owner

3. **@Mentioned Users** (`CommentMentionNotification`)
   - Users mentioned via @username in content
   - Message: "Alice mentioned you in a comment"
   - **Not sent if**: Mentioned user is the commenter

#### Notification Data:

```php
[
    'type' => 'comment_on_content' | 'comment_reply' | 'comment_mention',
    'comment_id' => '...',
    'commenter_id' => '...',
    'commenter_name' => 'username',
    'content_type' => 'Post' | 'Activity',
    'content_id' => '...',
    'content_title' => '...',
    'comment_preview' => substr(content, 0, 100),
    'message' => 'Human-readable message',
    'url' => route('posts.show', ...) or route('activities.show', ...),
]
```

---

## Authorization (CommentPolicy)

**File**: `app/Policies/CommentPolicy.php`

### Rules

| Action | Who Can Do It |
|--------|---------------|
| `view` | Everyone (public) |
| `viewAny` | Everyone (public) |
| `create` | Authenticated users |
| `update` | Comment author only |
| `delete` | Comment author **OR** content owner |
| `restore` | Comment author only |
| `forceDelete` | Comment author only |
| `moderate` | Content owner only |

### Content Ownership Detection

```php
// For Posts
if (isset($commentable->user_id) && $user->id === $commentable->user_id)

// For Activities
if (isset($commentable->host_id) && $user->id === $commentable->host_id)
```

---

## Integration Points

### Adding Comments to a New Content Type

To add comments to a new model (e.g., `Event`):

1. **Add Relationship to Model**:
```php
// app/Models/Event.php
use Illuminate\Database\Eloquent\Relations\MorphMany;

public function comments(): MorphMany
{
    return $this->morphMany(Comment::class, 'commentable');
}
```

2. **Add Route for Detail Page**:
```php
// routes/web.php
Route::get('/events/{event}', EventDetail::class)->name('events.show');
```

3. **Embed CommentSection in Detail View**:
```blade
<!-- resources/views/livewire/events/event-detail.blade.php -->
<livewire:comments.comment-section
    :commentable-type="'App\\Models\\Event'"
    :commentable-id="$event->id"
/>
```

4. **Add Comment Count to Card**:
```blade
<!-- resources/views/components/event-card.blade.php -->
@php
    $commentCount = $event->comments_count ?? $event->comments()->count();
@endphp
<a href="{{ route('events.show', $event->id) }}">
    {{ $commentCount }} {{ $commentCount === 1 ? 'comment' : 'comments' }}
</a>
```

5. **Update Notification URL Logic**:
```php
// In notification classes, add:
if ($commentable instanceof \App\Models\Event) {
    return route('events.show', $commentable->id);
}
```

---

## Feed Display Pattern

### Post Cards
**File**: `resources/views/components/post-card.blade.php`

Shows comment count at bottom:
```blade
<a href="{{ route('posts.show', $post->id) }}">
    <svg>...</svg>
    {{ $commentCount }} {{ $commentCount === 1 ? 'comment' : 'comments' }}
</a>
```

### Activity Cards (In Feeds)
**File**: `resources/views/livewire/discovery/nearby-feed.blade.php`

Shows comment count before "View Event Details" button:
```blade
@php
    $commentCount = $item['data']->comments_count ?? $item['data']->comments()->count();
@endphp
<a href="{{ route('activities.show', $item['data']) }}">
    {{ $commentCount }} comments
</a>
```

---

## Detail Page Pattern

### Post Detail
**Route**: `/posts/{post}`  
**Component**: `app/Livewire/Posts/PostDetail.php`  
**View**: `resources/views/livewire/posts/post-detail.blade.php`

Structure:
1. Back button to feed
2. Full post card (expanded)
3. CommentSection component

### Activity Detail
**Route**: `/activities/{activity}`  
**Component**: `app/Livewire/Activities/ActivityDetail.php`  
**View**: `resources/views/livewire/activities/activity-detail.blade.php`

Structure:
1. Back button (if needed)
2. Activity details (images, description, RSVP)
3. CommentSection component (after main content)

---

## Testing

### Test Files

1. **CommentThreadTest.php** (`tests/Feature/Feature/CommentThreadTest.php`)
   - Tests polymorphic commenting on Posts and Activities
   - Tests threading depth calculation (0-10)
   - Tests max depth enforcement
   - Tests content validation
   - Tests CRUD operations
   - Tests @mention parsing

2. **CommentPolicyTest.php** (`tests/Feature/Feature/CommentPolicyTest.php`)
   - Tests view permissions
   - Tests create permissions
   - Tests update authorization (own comments)
   - Tests delete authorization (own + content owner)
   - Tests moderate permissions (content owner)

### Running Tests

```bash
# Run all comment tests
php artisan test --filter=Comment

# Run specific test file
php artisan test tests/Feature/Feature/CommentThreadTest.php

# Run with coverage
php artisan test --coverage --filter=Comment
```

---

## Performance Considerations

### Database Optimization

1. **Eager Loading**: Always load relationships to avoid N+1:
```php
Comment::with('user', 'replies.user', 'reactions')->get();
```

2. **Counter Caching**: Use `withCount()` for comment counts:
```php
Post::withCount('comments')->get();
Activity::withCount('comments')->get();
```

3. **Indexes**: Key indexes already created:
   - `idx_comments_commentable` (polymorphic lookups)
   - `idx_comments_depth` (threading queries)
   - `idx_comments_parent` (parent-child traversal)

### Real-time Updates

- Uses Laravel Reverb (WebSocket) for instant updates
- Configure `.env`:
```bash
BROADCAST_CONNECTION=reverb
```

- Start Reverb server:
```bash
php artisan reverb:start
```

---

## Galaxy Theme Styling

All comment components use the FunLynk galaxy theme:

### Glass Cards
```css
.glass-card {
    background: rgba(15, 23, 42, 0.5);
    backdrop-filter: blur(12px);
    border: 1px solid rgba(255, 255, 255, 0.1);
}
```

### Gradient Borders (Threading)
```css
bg-gradient-to-b from-cyan-500/50 to-purple-500/50
```

### Accent Colors
- **Cyan**: Links, hover states (`text-cyan-400`)
- **Pink/Purple**: Primary actions (`from-pink-500 to-purple-500`)
- **Gray**: Secondary text (`text-gray-400`)

### Form Inputs
```css
bg-slate-800/50 border border-white/10
focus:border-cyan-500/50 focus:ring-2 focus:ring-cyan-500/20
```

---

## Common Patterns

### Getting Comment Count
```php
// With counter cache (preferred)
$post = Post::withCount('comments')->find($id);
$count = $post->comments_count;

// Without counter cache
$count = $post->comments()->count();

// Using service
$service = app(CommentService::class);
$count = $service->getCommentCount($post);
```

### Creating a Comment
```php
$service = app(CommentService::class);

// Top-level comment
$comment = $service->createComment($post, auth()->user(), 'Great post!');

// Reply to comment
$reply = $service->createComment($post, auth()->user(), 'I agree!', $parentComment);
```

### Checking Permissions
```php
// Can user edit comment?
$canEdit = auth()->user()->can('update', $comment);

// Can user delete comment?
$canDelete = auth()->user()->can('delete', $comment);

// Can user moderate comments on this post?
$canModerate = auth()->user()->can('moderate', $comment);
```

---

## Troubleshooting

### Comments Not Showing

1. Check polymorphic relationship is set up:
```php
// In Post or Activity model
public function comments(): MorphMany
{
    return $this->morphMany(Comment::class, 'commentable');
}
```

2. Verify CommentSection props are correct:
```blade
<!-- Must use double backslashes in string -->
:commentable-type="'App\\Models\\Post'"
```

### Real-time Updates Not Working

1. Ensure Reverb is running:
```bash
php artisan reverb:start
```

2. Check `.env` configuration:
```bash
BROADCAST_CONNECTION=reverb
REVERB_APP_ID=your-app-id
REVERB_APP_KEY=your-app-key
REVERB_APP_SECRET=your-app-secret
```

3. Verify queue is running (for notifications):
```bash
php artisan queue:work
```

### Max Depth Error

If users can't reply beyond depth 10:
- This is expected behavior (prevents infinite threading)
- The UI should show "Max depth" label instead of Reply button
- Check `CommentItem` component: `$canReply` should be `false`

---

## Future Enhancements

Potential improvements for future iterations:

1. **Comment Reactions**: Full like/helpful/funny system (table exists)
2. **@Mention Autocomplete**: Real-time username suggestions
3. **Comment Editing**: UI for editing comments (service method exists)
4. **Rich Text**: Markdown or basic formatting support
5. **Comment Moderation Queue**: Filament dashboard for flagged comments
6. **Comment Notifications Settings**: User preferences to opt-out
7. **Comment Search**: Search within comment threads
8. **Comment Sorting**: Sort by newest, oldest, most liked

---

## Related Documentation

- **E05 Epic Overview**: `context-engine/epics/E05_Social_Interaction/epic-overview.md`
- **F01 Task Breakdown**: `context-engine/tasks/E05_Social_Interaction/F01_Comment_Discussion_System/README.md`
- **UI Design Standards**: `context-engine/domain-contexts/ui-design-standards.md`
- **Database Context**: `context-engine/domain-contexts/database-context.md`
- **Auth Context**: `context-engine/domain-contexts/auth-context.md`

---

**Last Updated**: November 27, 2024  
**Implemented By**: AI Agent (Warp)  
**Status**: ✅ Production Ready
