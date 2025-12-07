# F01 Comment & Discussion System

## Architecture Overview

```
┌─────────────────────────────────────────────────────────────────┐
│                         FEED CARDS                              │
│  ┌──────────────────┐  ┌──────────────────┐                    │
│  │  Post Card       │  │  Event Card      │                    │
│  │  💬 12 comments  │  │  💬 5 comments   │  (Click to detail) │
│  └──────────────────┘  └──────────────────┘                    │
└─────────────────────────────────────────────────────────────────┘
                              ↓
┌─────────────────────────────────────────────────────────────────┐
│                      DETAIL PAGE                                │
│  ┌───────────────────────────────────────────────────────────┐ │
│  │  CommentSection (Polymorphic)                             │ │
│  │  ┌─────────────────────────────────────────────────────┐ │ │
│  │  │ CommentForm (New comment)                           │ │ │
│  │  └─────────────────────────────────────────────────────┘ │ │
│  │  ┌─────────────────────────────────────────────────────┐ │ │
│  │  │ CommentItem (Alice: "Great idea!")                  │ │ │
│  │  │   ├─ CommentItem (Bob: "I agree!")  [Reply]         │ │ │
│  │  │   └─ CommentItem (Charlie: "Me too!") [Reply]       │ │ │
│  │  └─────────────────────────────────────────────────────┘ │ │
│  │  ┌─────────────────────────────────────────────────────┐ │ │
│  │  │ CommentItem (David: "When?")                        │ │ │
│  │  └─────────────────────────────────────────────────────┘ │ │
│  └───────────────────────────────────────────────────────────┘ │
└─────────────────────────────────────────────────────────────────┘
                              ↓
┌─────────────────────────────────────────────────────────────────┐
│                   REAL-TIME UPDATES                             │
│  Laravel Reverb (WebSocket) → Laravel Echo → Livewire          │
│  New comment → Broadcast → All viewers see update instantly    │
└─────────────────────────────────────────────────────────────────┘
                              ↓
┌─────────────────────────────────────────────────────────────────┐
│                     NOTIFICATIONS                               │
│  Content Owner: "Bob commented on your post"                   │
│  Comment Author: "Charlie replied to your comment"             │
│  @Mentioned User: "Alice mentioned you in a comment"           │
└─────────────────────────────────────────────────────────────────┘
```

## Feature Overview

This feature enables rich conversations and discussions around both **Posts** (ephemeral) and **Events** (structured activities) through a comprehensive commenting system with threading, reactions, moderation, and real-time updates. It transforms static content into dynamic discussion hubs that foster community engagement.

**Key Architecture**: Comments use polymorphic relationships to work on both `posts` and `activities` tables, allowing users to discuss spontaneous posts and structured events with the same commenting infrastructure.

The system supports threaded discussions (nested replies) up to 10 levels deep, rich text formatting with @mentions, comment reactions, and real-time updates via **Laravel Reverb** (WebSocket-based). Moderation tools in Filament allow administrators to manage discussions and enforce community guidelines.

**UX Strategy**: Feed cards (Nearby Feed, For You Feed) display comment counts only. Clicking navigates to the detail page where the full comment section is displayed. This keeps feeds fast and clean while providing rich discussion on detail pages.

## Feature Scope

### In Scope
- **Polymorphic Comment System**: Single unified component works on both Posts and Events (activities)
- **Threaded Discussions**: Nested replies up to 10 levels deep with parent-child relationships
- **Rich Text Support**: Simple formatting with @username mentions
- **Comment Reactions**: Like reactions on individual comments
- **Real-time Updates**: Laravel Reverb (WebSocket) for instant comment updates
- **Smart Notifications**: Content owners notified of comments, users notified of replies to their comments
- **Moderation Tools**: Filament resources for comment management, flagging, and removal
- **Feed Integration**: Comment counts on feed cards, full comments on detail pages
- **Rate Limiting**: Max 5 comments per minute per user to prevent spam

### Out of Scope
- **Direct Messaging**: One-on-one messaging between users (handled by F04 Real-time Social Features)
- **Community Discussions**: Community-wide discussion boards (handled by F03 Community Features)
- **Reviews & Ratings**: Structured activity reviews (handled by E03 Activity Management)
- **Social Sharing**: Sharing comments externally (handled by F02 Social Sharing & Engagement)

## Tasks Breakdown

### T01: Enhance Comment Model & Relationships
**Estimated Time**: 3-4 hours
**Dependencies**: E01 (comments table exists)
**Artisan Commands**:
```bash
# Comment model already exists from E01, enhance it
# Add factory for testing
php artisan make:factory CommentFactory --model=Comment --no-interaction

# Create migration for comment_reactions table
php artisan make:migration create_comment_reactions_table --no-interaction
```

**Description**: Enhance the existing `Comment` model from E01 to support polymorphic relationships (commentable_type, commentable_id), threading (parent_id, depth), and rich text (content as text). Add relationships for user, commentable (Post/Activity), parent, replies, and reactions. Implement `casts()` method for timestamps. Create factory for testing with realistic comment data.

**Deliverables**:
- Enhanced `app/Models/Comment.php` with polymorphic relationships
- `database/factories/CommentFactory.php` for testing
- Migration for `comment_reactions` table (user_id, comment_id, reaction_type)

---

### T02: CommentService with Threading Logic
**Estimated Time**: 4-5 hours
**Dependencies**: T01
**Artisan Commands**:
```bash
# Create service class
php artisan make:class Services/CommentService --no-interaction

# Create event for new comments
php artisan make:event CommentCreated --no-interaction
```

**Description**: Create `CommentService` to handle comment business logic including creating comments, threading (calculating depth, validating max depth of 10), @mention parsing, and comment deletion (soft delete). Implement threading algorithm to maintain parent-child relationships. Parse @username mentions and create notifications. Handle comment editing with edit history tracking.

**Deliverables**:
- `app/Services/CommentService.php` with CRUD and threading logic
- `app/Events/CommentCreated.php` for real-time updates
- @mention parsing and notification creation

---

### T03: Filament CommentResource Enhancement
**Estimated Time**: 3-4 hours
**Dependencies**: T01, T02
**Artisan Commands**:
```bash
# CommentResource already exists from E01, enhance it
# Create custom Filament page for comment moderation
php artisan make:filament-page ManageComments --resource=CommentResource --type=custom --no-interaction
```

**Description**: Enhance the existing `CommentResource` from E01 to display threaded comments, show commentable type (Post/Activity), display user information, and provide moderation actions (approve, flag, delete). Add filters for commentable type, flagged status, and date range. Create custom moderation page with bulk actions.

**Deliverables**:
- Enhanced `app/Filament/Resources/CommentResource.php`
- Custom moderation page with bulk actions
- Filters for commentable type and moderation status

---

### T04: Livewire Comment Components
**Estimated Time**: 6-7 hours
**Dependencies**: T02
**Artisan Commands**:
```bash
# Create Livewire components
php artisan make:livewire Comments/CommentSection --no-interaction
php artisan make:livewire Comments/CommentForm --no-interaction
php artisan make:livewire Comments/CommentItem --no-interaction
```

**Description**: Create unified Livewire components for displaying and managing comments on both Posts and Activities.

**CommentSection** (main component):
- Accepts `$commentableType` and `$commentableId` props (polymorphic)
- Displays all comments with nested threading visualization (indentation)
- Listens to Laravel Echo for real-time updates
- Handles pagination (20 comments per page, infinite scroll)

**CommentForm**:
- Handles new comment creation and replies
- Character limit (500 chars)
- @mention autocomplete (future enhancement)
- Rate limiting validation

**CommentItem**:
- Displays individual comment with user avatar, username, timestamp
- Reply button (opens nested CommentForm)
- Like/react button
- Delete button (own comments only)
- Report button

**Feed Integration**:
- Add comment count display to post-card and event cards
- Comment count is clickable, navigates to detail page
- No inline comment display on feed cards (keeps feed clean)

Apply galaxy theme with glass morphism styling to all components.

**Deliverables**:
- `app/Livewire/Comments/CommentSection.php` (polymorphic, works for Posts + Activities)
- `app/Livewire/Comments/CommentForm.php` with validation
- `app/Livewire/Comments/CommentItem.php` with threading
- Blade views with DaisyUI and galaxy theme styling
- Comment count integration in feed cards

---

### T05: Comment Moderation Policies
**Estimated Time**: 3-4 hours
**Dependencies**: T01
**Artisan Commands**:
```bash
# Create policy
php artisan make:policy CommentPolicy --model=Comment --no-interaction

# Create middleware for comment moderation
php artisan make:middleware CheckCommentModeration --no-interaction
```

**Description**: Create `CommentPolicy` to control who can create, edit, delete, and moderate comments. Users can edit/delete their own comments. Post/Activity owners can moderate comments on their content. Admins can moderate all comments. Implement soft delete for comments. Create middleware to check moderation permissions.

**Deliverables**:
- `app/Policies/CommentPolicy.php` with authorization rules
- `app/Http/Middleware/CheckCommentModeration.php`
- Policy registration in `AuthServiceProvider`

---

### T06: Real-time Comment Updates & Notifications
**Estimated Time**: 5-6 hours
**Dependencies**: T02, T04
**Artisan Commands**:
```bash
# Create listener for CommentCreated event
php artisan make:listener BroadcastCommentCreated --event=CommentCreated --no-interaction

# Create notifications
php artisan make:notification CommentMentionNotification --no-interaction
php artisan make:notification CommentOnYourContentNotification --no-interaction
php artisan make:notification ReplyToYourCommentNotification --no-interaction
```

**Description**: Implement real-time comment updates using **Laravel Reverb** (WebSocket). When new comments are created, broadcast `CommentCreated` event to channel `comments.{commentable_type}.{commentable_id}`. Livewire components listen via Laravel Echo and update in real-time.

**Notification Strategy**:
1. **Content Owner**: Always notified when someone comments on their Post/Activity
2. **Comment Author**: Notified when someone replies to their comment
3. **@Mentions**: Notified when mentioned in a comment (future enhancement)
4. **Opt-out**: Users can disable notifications in settings

**Rate Limiting**: Max 5 comments per minute per user to prevent spam.

**Deliverables**:
- `app/Listeners/BroadcastCommentCreated.php` for Reverb broadcasting
- `app/Notifications/CommentOnYourContentNotification.php` for content owners
- `app/Notifications/ReplyToYourCommentNotification.php` for comment authors
- `app/Notifications/CommentMentionNotification.php` for @mentions
- Laravel Echo integration in Livewire components
- Rate limiting middleware for comment creation

---

### T07: Comment Tests
**Estimated Time**: 3-4 hours
**Dependencies**: T01-T06
**Artisan Commands**:
```bash
# Create Pest tests
php artisan make:test --pest Feature/CommentThreadTest --no-interaction
php artisan make:test --pest Feature/CommentModerationTest --no-interaction
php artisan make:test --pest Feature/CommentPolicyTest --no-interaction
```

**Description**: Write comprehensive Pest tests for comment functionality. Test comment creation on Posts and Activities (polymorphic). Test threading logic (max depth 10). Test @mention parsing and notifications. Test comment moderation (flagging, deletion). Test authorization policies. Test Livewire components with browser testing.

**Deliverables**:
- `tests/Feature/CommentThreadTest.php` for threading logic
- `tests/Feature/CommentModerationTest.php` for moderation workflows
- `tests/Feature/CommentPolicyTest.php` for authorization
- Browser tests for Livewire components

## Success Criteria

### Functional Requirements
- [ ] Users can comment on both Posts and Events (polymorphic relationships work)
- [ ] Comments support threading up to 10 levels deep with clear parent-child relationships
- [ ] @username mentions parse correctly and send notifications to mentioned users
- [ ] Users can react to comments (like, helpful, funny)
- [ ] Comment owners can edit and delete their own comments
- [ ] Post/Activity owners can moderate comments on their content
- [ ] Admins can moderate all comments via Filament dashboard

### Technical Requirements
- [ ] Comment model uses polymorphic relationships (commentable_type, commentable_id)
- [ ] Threading logic correctly calculates depth and prevents exceeding 10 levels
- [ ] Livewire polling updates comments every 5 seconds without page refresh
- [ ] Comment reactions stored in `comment_reactions` table with proper relationships
- [ ] Soft delete implemented for comments (deleted_at timestamp)
- [ ] CommentPolicy enforces authorization rules correctly

### User Experience Requirements
- [ ] Comment threads display with clear visual hierarchy (indentation/nesting)
- [ ] @mention autocomplete works in comment form
- [ ] Real-time updates appear smoothly without disrupting user input
- [ ] Galaxy theme with glass morphism applied to all comment components
- [ ] Comment form validates input and shows clear error messages
- [ ] Loading states displayed during comment submission

### Performance Requirements
- [ ] Comment list loads within 2 seconds for threads with 100+ comments
- [ ] Livewire polling doesn't cause performance degradation
- [ ] Database queries optimized with eager loading for relationships
- [ ] Comment reactions load efficiently without N+1 queries

## Dependencies

### E01 Prerequisites (Available Now)
- ✅ `comments` table with polymorphic columns (commentable_type, commentable_id, parent_id)
- ✅ `Comment` model with basic relationships
- ✅ `CommentResource` in Filament for basic CRUD
- ✅ `users` table for comment attribution
- ✅ `posts` and `activities` tables for commentable entities

### Blocking Dependencies
- **E02 User Management**: User profiles for @mention display (can use basic user data initially)
- **E01 Notifications**: Notification system for @mention alerts (already available)

### Optional Dependencies
- **Laravel Echo + Pusher**: For WebSocket-based real-time updates (Phase 2, Livewire polling sufficient for MVP)

## Technical Notes

### Laravel 12 Conventions
- Use `casts()` method in Comment model, not `$casts` property
- Register policies in `bootstrap/app.php` using `->withPolicies()`
- Use `->components([])` in Filament resources, not `->schema([])`
- Livewire components in `App\Livewire` namespace (Livewire v3)
- Use `wire:model.live` for real-time input binding

### Polymorphic Relationships
```php
// Comment model
public function commentable(): MorphTo
{
    return $this->morphTo();
}

// Post model
public function comments(): MorphMany
{
    return $this->morphMany(Comment::class, 'commentable');
}

// Activity model
public function comments(): MorphMany
{
    return $this->morphMany(Comment::class, 'commentable');
}
```

### Threading Logic
- Use `parent_id` foreign key for hierarchical relationships
- Calculate `depth` on comment creation (parent's depth + 1)
- Validate max depth of 10 levels before allowing replies
- Use eager loading to prevent N+1 queries: `Comment::with('replies.replies')`

### Real-time Updates with Laravel Reverb
- Use **Laravel Reverb** (WebSocket server) for real-time comment updates
- Broadcast `CommentCreated` event to channel: `comments.{commentable_type}.{commentable_id}`
- Livewire components listen via Laravel Echo and update instantly
- Configure Reverb in `.env`: `BROADCAST_CONNECTION=reverb`
- Start Reverb server: `php artisan reverb:start`

```php
// In CommentCreated event
public function broadcastOn(): array
{
    return [
        new Channel("comments.{$this->comment->commentable_type}.{$this->comment->commentable_id}"),
    ];
}
```

```javascript
// In Livewire component blade view
Echo.channel(`comments.{{ $commentableType }}.{{ $commentableId }}`)
    .listen('CommentCreated', (e) => {
        @this.call('refreshComments');
    });
```

### Notification Strategy

**Who Gets Notified:**

1. **Content Owner** (Post/Activity creator):
   - ✅ Always notified when someone comments on their content
   - Notification: "Bob commented on your post"
   - Notification: "Alice commented on your event"

2. **Comment Author** (Person who wrote a comment):
   - ✅ Notified when someone replies to their comment
   - Notification: "Charlie replied to your comment"
   - Does NOT get notified for other comments on the same post (too noisy)

3. **@Mentioned Users** (Future enhancement):
   - ✅ Notified when mentioned in a comment
   - Notification: "Bob mentioned you in a comment"

**Notification Opt-out Settings:**
- Users can disable "Comments on my posts/events"
- Users can disable "Replies to my comments"
- Users can disable "@mentions"
- Settings stored in `user_notification_preferences` table

**Example Scenarios:**

```
Scenario 1: Comment on your content
Alice posts "Looking for tennis partners"
Bob comments "I'm down!"
→ Alice gets notification: "Bob commented on your post"

Scenario 2: Reply to your comment
Alice posts "Looking for tennis partners"
Bob comments "I'm down!"
Charlie replies to Bob: "What time works?"
→ Bob gets notification: "Charlie replied to your comment"
→ Alice gets notification: "Charlie commented on your post"

Scenario 3: Multiple comments (NOT notified)
Alice posts "Looking for tennis partners"
Bob comments "I'm down!"
Charlie comments "Me too!"
David comments "Count me in!"
→ Bob does NOT get notified about Charlie or David's comments
→ Only Alice (content owner) gets notified
```

### @Mention Parsing
```php
// Parse @username mentions
preg_match_all('/@(\w+)/', $content, $matches);
$usernames = $matches[1];

// Find users and create notifications
$users = User::whereIn('username', $usernames)->get();
foreach ($users as $user) {
    $user->notify(new CommentMentionNotification($comment));
}
```

### Feed Integration Strategy

**Feed Cards (Nearby Feed, For You Feed):**
- Display comment count only: "💬 12 comments"
- Comment count is clickable
- Clicking navigates to detail page (Post detail or Activity detail)
- NO inline comment display on feed cards (keeps feed fast and clean)

**Detail Pages (Post detail, Activity detail):**
- Full `CommentSection` component displayed
- All comments visible with threading
- Comment form for new comments
- Real-time updates via Reverb

**Implementation:**
```blade
{{-- In post-card.blade.php --}}
<a href="{{ route('posts.show', $post) }}" class="text-gray-400 hover:text-cyan-400 transition">
    💬 {{ $post->comments_count }} comments
</a>

{{-- In activity-detail.blade.php --}}
<livewire:comments.comment-section
    :commentable-type="'App\Models\Activity'"
    :commentable-id="$activity->id"
/>
```

### Galaxy Theme Integration
- Use DaisyUI classes: `card`, `card-body`, `btn`, `textarea`
- Apply glass morphism: `bg-slate-800/50 backdrop-blur-lg border border-white/10`
- Use aurora gradient backgrounds for comment threads
- Implement smooth transitions for real-time updates
- Comment items have subtle hover effects
- Reply indentation uses left border with cyan accent

### Rate Limiting & Spam Prevention

**Rate Limits:**
- Max 5 comments per minute per user
- Max 20 comments per hour per user
- Implemented using Laravel's `RateLimiter` facade

```php
// In CommentService
use Illuminate\Support\Facades\RateLimiter;

public function createComment($data)
{
    $key = 'comment-creation:' . auth()->id();

    if (RateLimiter::tooManyAttempts($key, 5)) {
        throw new \Exception('Too many comments. Please wait before commenting again.');
    }

    RateLimiter::hit($key, 60); // 60 seconds

    // Create comment...
}
```

**Spam Detection:**
- Detect duplicate content (same comment posted multiple times)
- Detect rapid-fire comments (same user, same content type)
- Auto-flag suspicious comments for moderation

**Character Limits:**
- Min: 1 character
- Max: 500 characters
- Validated on both client and server side

### Performance Optimization
- Eager load relationships: `Comment::with('user', 'replies', 'reactions')`
- Cache comment counts: `$post->comments_count` (use database counter with `withCount()`)
- Paginate long comment threads (20 comments per page, infinite scroll)
- Use database indexes on `commentable_type`, `commentable_id`, `parent_id`
- Optimize threading queries with recursive CTEs for deep nesting

---

**Estimated Total Time**: 28-36 hours

**Implementation Order**: T01 → T02 → T03 → T04 → T05 → T06 → T07

**Testing Priority**: High - Comments are core social feature, must be thoroughly tested

## Implementation Phases

### Phase 1: Core Comment System (T01-T04)
- Database, models, relationships
- Service layer with threading logic
- Livewire components with basic UI
- Feed integration (comment counts)
- **Estimated**: 18-22 hours

### Phase 2: Authorization & Moderation (T05)
- Policies for comment permissions
- Moderation tools in Filament
- **Estimated**: 3-4 hours

### Phase 3: Real-time & Notifications (T06)
- Laravel Reverb integration
- Notification system
- Rate limiting
- **Estimated**: 5-6 hours

### Phase 4: Testing & Polish (T07)
- Comprehensive Pest tests
- Browser testing
- Performance optimization
- **Estimated**: 3-4 hours
