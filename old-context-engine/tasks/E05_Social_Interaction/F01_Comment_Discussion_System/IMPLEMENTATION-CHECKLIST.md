# Comment System - Implementation Checklist

## Pre-Implementation Setup

- [ ] Review all task documents in this directory
- [ ] Ensure Laravel Reverb is installed and configured
- [ ] Verify E01 foundation tables exist (comments, users, posts, activities)
- [ ] Check that Livewire v3 and Filament v4 are properly set up

---

## T01: Enhance Comment Model & Relationships (3-4 hours)

### Database
- [ ] Create migration for `comment_reactions` table
  - [ ] `user_id` (foreign key to users)
  - [ ] `comment_id` (foreign key to comments)
  - [ ] `reaction_type` (enum: 'like', 'helpful', 'funny')
  - [ ] Unique constraint on (user_id, comment_id)
  - [ ] Timestamps
- [ ] Run migration: `php artisan migrate`

### Model Enhancement
- [ ] Update `Comment` model with polymorphic relationships
  - [ ] `commentable()` - MorphTo relationship
  - [ ] `user()` - BelongsTo relationship
  - [ ] `parent()` - BelongsTo self-relationship
  - [ ] `replies()` - HasMany self-relationship
  - [ ] `reactions()` - HasMany relationship
- [ ] Add `casts()` method for timestamps
- [ ] Add `depth` calculation logic
- [ ] Add soft deletes

### Factory
- [ ] Create `CommentFactory` with realistic data
- [ ] Support for nested comments (parent_id)
- [ ] Support for both Post and Activity commentables

### Testing
- [ ] Test polymorphic relationships work for Posts
- [ ] Test polymorphic relationships work for Activities
- [ ] Test threading (parent-child relationships)
- [ ] Test soft deletes

---

## T02: CommentService with Threading Logic (4-5 hours)

### Service Class
- [ ] Create `app/Services/CommentService.php`
- [ ] Implement `createComment($data)` method
  - [ ] Validate max depth (10 levels)
  - [ ] Calculate depth from parent
  - [ ] Parse @mentions
  - [ ] Create comment
  - [ ] Trigger notifications
  - [ ] Broadcast event
- [ ] Implement `updateComment($comment, $data)` method
- [ ] Implement `deleteComment($comment)` method (soft delete)
- [ ] Implement `getCommentsForCommentable($type, $id)` method
- [ ] Implement rate limiting logic

### Event
- [ ] Create `CommentCreated` event
- [ ] Implement `broadcastOn()` for Reverb
- [ ] Add comment data to broadcast payload

### Testing
- [ ] Test comment creation
- [ ] Test threading logic (depth calculation)
- [ ] Test max depth validation (10 levels)
- [ ] Test @mention parsing
- [ ] Test rate limiting (5 per minute)

---

## T03: Filament CommentResource Enhancement (3-4 hours)

### Resource Enhancement
- [ ] Update `CommentResource` to show commentable type
- [ ] Add filters for commentable type (Post/Activity)
- [ ] Add filters for flagged status
- [ ] Add filters for date range
- [ ] Display threading hierarchy in table
- [ ] Add bulk actions (approve, delete)

### Custom Page
- [ ] Create moderation page
- [ ] Show flagged comments
- [ ] Bulk moderation actions
- [ ] Comment statistics

### Testing
- [ ] Test filters work correctly
- [ ] Test bulk actions
- [ ] Test moderation workflow

---

## T04: Livewire Comment Components (6-7 hours)

### CommentSection Component
- [ ] Create `app/Livewire/Comments/CommentSection.php`
- [ ] Accept `$commentableType` and `$commentableId` props
- [ ] Load comments with threading
- [ ] Implement pagination (20 per page)
- [ ] Listen to Laravel Echo for real-time updates
- [ ] Create blade view with galaxy theme

### CommentForm Component
- [ ] Create `app/Livewire/Comments/CommentForm.php`
- [ ] Accept `$commentableType`, `$commentableId`, `$parentId` props
- [ ] Text area with 500 char limit
- [ ] Character counter
- [ ] Validation
- [ ] Submit handler
- [ ] Loading states
- [ ] Create blade view with galaxy theme

### CommentItem Component
- [ ] Create `app/Livewire/Comments/CommentItem.php`
- [ ] Accept `$comment` prop
- [ ] Display user avatar and username
- [ ] Display comment content
- [ ] Display timestamp (relative)
- [ ] Like button with count
- [ ] Reply button (opens nested form)
- [ ] Delete button (own comments only)
- [ ] Report button
- [ ] Recursive rendering for nested replies
- [ ] Create blade view with galaxy theme

### Feed Integration
- [ ] Add comment count to `post-card.blade.php`
- [ ] Add comment count to event cards in feeds
- [ ] Make comment count clickable (navigate to detail)
- [ ] Add `CommentSection` to Post detail page
- [ ] Add `CommentSection` to Activity detail page

### Testing
- [ ] Test component rendering
- [ ] Test comment submission
- [ ] Test reply functionality
- [ ] Test real-time updates
- [ ] Test threading display
- [ ] Browser tests with Pest

---

## T05: Comment Moderation Policies (3-4 hours)

### Policy
- [ ] Create `CommentPolicy`
- [ ] Implement `create()` - any authenticated user
- [ ] Implement `update()` - own comments only
- [ ] Implement `delete()` - own comments or content owner or admin
- [ ] Implement `moderate()` - content owner or admin
- [ ] Register policy in `bootstrap/app.php`

### Middleware
- [ ] Create `CheckCommentModeration` middleware
- [ ] Check user permissions
- [ ] Register middleware

### Testing
- [ ] Test create permission
- [ ] Test update permission (own comments)
- [ ] Test delete permission (own, content owner, admin)
- [ ] Test moderation permission

---

## T06: Real-time Updates & Notifications (5-6 hours)

### Listener
- [ ] Create `BroadcastCommentCreated` listener
- [ ] Listen to `CommentCreated` event
- [ ] Broadcast to Reverb channel

### Notifications
- [ ] Create `CommentOnYourContentNotification`
  - [ ] Notify content owner
  - [ ] Include comment data
  - [ ] Link to detail page
- [ ] Create `ReplyToYourCommentNotification`
  - [ ] Notify parent comment author
  - [ ] Include reply data
  - [ ] Link to detail page
- [ ] Create `CommentMentionNotification` (future)
  - [ ] Notify mentioned users
  - [ ] Include comment data
  - [ ] Link to detail page

### Laravel Echo Integration
- [ ] Add Echo listener to CommentSection blade view
- [ ] Handle real-time comment updates
- [ ] Smooth animations for new comments

### Rate Limiting
- [ ] Implement rate limiter in CommentService
- [ ] 5 comments per minute per user
- [ ] 20 comments per hour per user
- [ ] Clear error messages

### Testing
- [ ] Test Reverb broadcasting
- [ ] Test notifications sent correctly
- [ ] Test rate limiting
- [ ] Test real-time updates in browser

---

## T07: Comment Tests (3-4 hours)

### Feature Tests
- [ ] Create `CommentThreadTest.php`
  - [ ] Test comment creation on Posts
  - [ ] Test comment creation on Activities
  - [ ] Test threading (nested replies)
  - [ ] Test max depth validation
- [ ] Create `CommentModerationTest.php`
  - [ ] Test flagging comments
  - [ ] Test deleting comments
  - [ ] Test moderation permissions
- [ ] Create `CommentPolicyTest.php`
  - [ ] Test all policy methods
  - [ ] Test authorization rules

### Browser Tests
- [ ] Test comment form submission
- [ ] Test reply functionality
- [ ] Test real-time updates
- [ ] Test like button
- [ ] Test delete button

### Run Tests
- [ ] `php artisan test --filter=Comment`
- [ ] All tests pass ✅

---

## Final Checklist

- [ ] All 7 tasks completed
- [ ] All tests passing
- [ ] Galaxy theme applied to all components
- [ ] Real-time updates working via Reverb
- [ ] Notifications sending correctly
- [ ] Rate limiting working
- [ ] Feed integration complete
- [ ] Documentation updated
- [ ] Code formatted with Pint: `vendor/bin/pint --dirty`

---

**Estimated Total Time**: 28-36 hours  
**Status**: Ready for Implementation ✅

