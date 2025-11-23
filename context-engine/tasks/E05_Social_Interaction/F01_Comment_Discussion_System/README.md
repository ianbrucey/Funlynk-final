# F01 Comment & Discussion System

## Feature Overview

This feature enables rich conversations and discussions around both **Posts** (ephemeral) and **Events** (structured activities) through a comprehensive commenting system with threading, reactions, moderation, and real-time updates. It transforms static content into dynamic discussion hubs that foster community engagement.

**Key Architecture**: Comments use polymorphic relationships to work on both `posts` and `activities` tables, allowing users to discuss spontaneous posts and structured events with the same commenting infrastructure.

The system supports threaded discussions up to 10 levels deep, rich text formatting with @mentions, comment reactions, and real-time updates via Livewire polling. Moderation tools in Filament allow administrators to manage discussions and enforce community guidelines.

## Feature Scope

### In Scope
- **Polymorphic Comment System**: Comments work on both Posts and Events (activities)
- **Threaded Discussions**: Nested replies up to 10 levels deep with parent-child relationships
- **Rich Text Support**: Markdown or simple formatting with @username mentions
- **Comment Reactions**: Like, helpful, funny reactions on individual comments
- **Real-time Updates**: Livewire polling for live comment updates (Laravel Echo optional Phase 2)
- **Moderation Tools**: Filament resources for comment management, flagging, and removal
- **Comment Analytics**: Engagement tracking and discussion quality metrics

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
**Estimated Time**: 5-6 hours
**Dependencies**: T02
**Artisan Commands**:
```bash
# Create Livewire components
php artisan make:livewire Comments/CommentThread --no-interaction
php artisan make:livewire Comments/CommentForm --no-interaction
php artisan make:livewire Comments/CommentItem --no-interaction
```

**Description**: Create Livewire components for displaying and managing comments. `CommentThread` displays all comments for a Post/Activity with threading visualization. `CommentForm` handles new comment creation with @mention autocomplete. `CommentItem` displays individual comments with reply, react, and report actions. Implement Livewire polling for real-time updates (every 5 seconds). Apply galaxy theme with glass morphism styling.

**Deliverables**:
- `app/Livewire/Comments/CommentThread.php` with threading display
- `app/Livewire/Comments/CommentForm.php` with @mention support
- `app/Livewire/Comments/CommentItem.php` with reactions
- Blade views with DaisyUI and galaxy theme styling

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

### T06: Real-time Comment Updates
**Estimated Time**: 4-5 hours
**Dependencies**: T02, T04
**Artisan Commands**:
```bash
# Create listener for CommentCreated event
php artisan make:listener BroadcastCommentCreated --event=CommentCreated --no-interaction

# Create notification for mentions
php artisan make:notification CommentMentionNotification --no-interaction
```

**Description**: Implement real-time comment updates using Livewire polling (poll every 5 seconds). When new comments are created, broadcast `CommentCreated` event. Livewire components listen for updates and refresh comment list. Create notifications for @mentions that notify mentioned users. Optional: Integrate Laravel Echo + Pusher for WebSocket-based real-time updates (Phase 2).

**Deliverables**:
- `app/Listeners/BroadcastCommentCreated.php` for event broadcasting
- `app/Notifications/CommentMentionNotification.php` for @mentions
- Livewire polling implementation in comment components

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

### Real-time Updates
- **MVP**: Livewire polling every 5 seconds (`wire:poll.5s`)
- **Phase 2**: Laravel Echo + Pusher/Soketi for WebSocket updates
- Broadcast `CommentCreated` event to channel: `comments.{commentable_type}.{commentable_id}`

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

### Galaxy Theme Integration
- Use DaisyUI classes: `card`, `card-body`, `btn`, `textarea`
- Apply glass morphism: `bg-base-100/80 backdrop-blur-lg`
- Use aurora gradient backgrounds for comment threads
- Implement smooth transitions for real-time updates

### Performance Optimization
- Eager load relationships: `Comment::with('user', 'replies', 'reactions')`
- Cache comment counts: `$post->comments_count` (use database counter)
- Paginate long comment threads (50 comments per page)
- Use database indexes on `commentable_type`, `commentable_id`, `parent_id`

---

**Estimated Total Time**: 25-32 hours

**Implementation Order**: T01 → T02 → T03 → T04 → T05 → T06 → T07

**Testing Priority**: High - Comments are core social feature, must be thoroughly tested
