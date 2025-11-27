# Agent C: Post Reaction System Testing & Integration
**Date**: 2025-11-23 20:00  
**Epic**: E04 Discovery Engine + E05 Social Interaction  
**Estimated Time**: 3-4 hours  
**Prerequisites**: Agent A & B must complete all tasks first

## Context

Test and integrate the post reaction system:
- Verify all backend functionality works
- Test real-time notifications
- Ensure UI components work correctly
- Write comprehensive tests

## Your Tasks

### Task 1: Backend Integration Testing (1 hour)

**Test Reaction Flow**:
```bash
php artisan tinker
```

```php
// Test reaction creation
$user = User::first();
$post = Post::first();
$service = app(\App\Services\PostService::class);

// React to post
$reaction = $service->reactToPost($post->id, 'im_down', $user);
// Verify: reaction created, post.reaction_count updated, event fired

// Test invitation
$friends = User::where('id', '!=', $user->id)->limit(3)->pluck('id')->toArray();
$invitations = $service->inviteFriendsToPost($post->id, $friends, $user);
// Verify: invitations created, events fired

// Test conversion eligibility
$eligibility = $service->checkConversionEligibility($post->id);
// Verify: returns correct thresholds
```

**Test Broadcasting**:
```bash
# In one terminal, start queue worker
php artisan queue:work

# In another terminal, trigger events
php artisan tinker
event(new \App\Events\PostReacted($post, $reaction, $eligibility));
```

**Verify**:
- ✅ Reactions create successfully
- ✅ Invitations create successfully
- ✅ Events broadcast to correct channels
- ✅ Notifications persist in database

---

### Task 2: Write Feature Tests (2 hours)

**Create Test File**:
```bash
php artisan make:test --pest Feature/PostReactionSystemTest --no-interaction
```

**File**: `tests/Feature/PostReactionSystemTest.php`
```php
<?php

use App\Models\User;
use App\Models\Post;
use App\Models\PostReaction;
use App\Models\PostInvitation;
use App\Services\PostService;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->post = Post::factory()->create();
    $this->service = app(PostService::class);
});

it('can create im_down reaction', function () {
    $this->actingAs($this->user);
    
    $reaction = $this->service->reactToPost($this->post->id, 'im_down', $this->user);
    
    expect($reaction)->toBeInstanceOf(PostReaction::class);
    expect($reaction->reaction_type)->toBe('im_down');
    expect($this->post->fresh()->reaction_count)->toBe(1);
});

it('can create invite_friends reaction', function () {
    $this->actingAs($this->user);
    
    $reaction = $this->service->reactToPost($this->post->id, 'invite_friends', $this->user);
    
    expect($reaction->reaction_type)->toBe('invite_friends');
});

it('updates reaction if user reacts again', function () {
    $this->actingAs($this->user);
    
    $this->service->reactToPost($this->post->id, 'im_down', $this->user);
    $this->service->reactToPost($this->post->id, 'invite_friends', $this->user);
    
    expect(PostReaction::where('post_id', $this->post->id)->count())->toBe(1);
    expect(PostReaction::first()->reaction_type)->toBe('invite_friends');
});

it('can invite friends to post', function () {
    $this->actingAs($this->user);
    $friends = User::factory()->count(3)->create();
    
    $invitations = $this->service->inviteFriendsToPost(
        $this->post->id,
        $friends->pluck('id')->toArray(),
        $this->user
    );
    
    expect($invitations)->toHaveCount(3);
    expect(PostInvitation::count())->toBe(3);
});

it('checks conversion eligibility at 5 reactions', function () {
    User::factory()->count(5)->create()->each(function ($user) {
        $this->service->reactToPost($this->post->id, 'im_down', $user);
    });
    
    $eligibility = $this->service->checkConversionEligibility($this->post->id);
    
    expect($eligibility['eligible'])->toBeTrue();
    expect($eligibility['auto_convert'])->toBeFalse();
});

it('checks auto conversion at 10 reactions', function () {
    User::factory()->count(10)->create()->each(function ($user) {
        $this->service->reactToPost($this->post->id, 'im_down', $user);
    });
    
    $eligibility = $this->service->checkConversionEligibility($this->post->id);
    
    expect($eligibility['eligible'])->toBeTrue();
    expect($eligibility['auto_convert'])->toBeTrue();
});

it('creates notification when user reacts', function () {
    $this->actingAs($this->user);
    
    $this->service->reactToPost($this->post->id, 'im_down', $this->user);
    
    expect(\App\Models\Notification::where('user_id', $this->post->user_id)->count())->toBe(1);
});

it('creates notification when user is invited', function () {
    $this->actingAs($this->user);
    $friend = User::factory()->create();
    
    $this->service->inviteFriendsToPost($this->post->id, [$friend->id], $this->user);
    
    expect(\App\Models\Notification::where('user_id', $friend->id)->count())->toBe(1);
});
```

**Run Tests**:
```bash
php artisan test --filter=PostReactionSystemTest
```

---

### Task 3: API Endpoint Testing (1 hour)

**Test API Endpoints**:
```bash
# Test react endpoint
curl -X POST http://funlynk.test/api/posts/{post-id}/react \
  -H "Authorization: Bearer {token}" \
  -H "Content-Type: application/json" \
  -d '{"reaction_type": "im_down"}'

# Test unreact endpoint
curl -X DELETE http://funlynk.test/api/posts/{post-id}/react \
  -H "Authorization: Bearer {token}"

# Test get reactions
curl http://funlynk.test/api/posts/{post-id}/reactions \
  -H "Authorization: Bearer {token}"

# Test invite friends
curl -X POST http://funlynk.test/api/posts/{post-id}/invite \
  -H "Authorization: Bearer {token}" \
  -H "Content-Type: application/json" \
  -d '{"friend_ids": ["uuid1", "uuid2"]}'

# Test get invitations
curl http://funlynk.test/api/users/me/invitations \
  -H "Authorization: Bearer {token}"
```

**Verify**:
- ✅ All endpoints return correct status codes
- ✅ Response data is correct
- ✅ Errors are handled properly

---

### Task 4: UI Testing (30 min)

**Manual Browser Testing**:

1. **Test Notification Bell**:
   - Open browser, login
   - Have another user react to your post
   - Verify: Bell count updates in real-time
   - Click bell → dropdown shows notification
   - Click "Mark all read" → count goes to 0

2. **Test Friend Selector**:
   - Go to any post
   - Click "Invite Friends" button
   - Verify: Modal opens
   - Search for friends
   - Select multiple friends
   - Click "Invite" → success message appears

3. **Test Real-Time Notifications**:
   - Open browser in two tabs (two different users)
   - User 1: React to User 2's post
   - Verify: User 2 sees toast notification immediately
   - Verify: Notification bell count updates

4. **Test Post Conversion**:
   - Create a post
   - Have 5 users react with "I'm Down"
   - Verify: Conversion suggestion appears
   - Have 5 more users react (10 total)
   - Verify: Post auto-converts to event

---

### Task 5: Documentation (30 min)

**Update README** or create `docs/REACTIONS.md`:

```markdown
# Post Reaction System

## Features
- "I'm Down" reactions signal intent to participate
- "Invite Friends" shares posts with selected friends
- Real-time notifications via WebSocket
- Single channel per user architecture

## Usage

### React to Post
```php
$service = app(PostService::class);
$reaction = $service->reactToPost($postId, 'im_down', $user);
```

### Invite Friends
```php
$invitations = $service->inviteFriendsToPost($postId, $friendIds, $inviter);
```

### Check Conversion Eligibility
```php
$eligibility = $service->checkConversionEligibility($postId);
// Returns: ['eligible' => bool, 'auto_convert' => bool, ...]
```

## WebSocket Integration

Subscribe to user channel:
```javascript
Echo.channel(`user.${userId}`)
    .listen('.notification', (notification) => {
        console.log(notification);
    });
```

## Testing
```bash
php artisan test --filter=PostReactionSystemTest
```
```

---

## Success Criteria

✅ All backend tests passing
✅ All API endpoints working
✅ Real-time notifications working
✅ UI components functional
✅ Documentation complete
✅ No console errors
✅ No breaking changes to existing features

---

## Deliverables

1. **Test Report**: Summary of all tests run and results
2. **Bug Report**: Any issues found during testing
3. **Documentation**: Updated docs with reaction system usage
4. **Integration Checklist**: Verified all components work together

---

**Start after Agent A & B complete their tasks. Report any issues immediately.**

