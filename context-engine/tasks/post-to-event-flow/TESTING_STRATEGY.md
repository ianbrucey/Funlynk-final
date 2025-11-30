# Testing Strategy: Post-to-Event Conversion

> **Goal**: Achieve 95%+ test coverage with comprehensive unit, integration, and E2E tests  
> **Framework**: Pest v4  
> **Last Updated**: 2025-11-30

---

## Testing Pyramid

```
        /\
       /  \      E2E Tests (5%)
      /____\     - Full user flows
     /      \    
    /        \   Integration Tests (25%)
   /__________\  - Component interactions
  /            \ 
 /              \ Unit Tests (70%)
/________________\ - Individual functions
```

---

## 1. Unit Tests (70% of tests)

### Agent A: Backend Unit Tests

#### Database & Models
```bash
php artisan make:test --pest Database/PostConversionMigrationsTest
```

**Test Cases**:
```php
it('adds conversion tracking columns to posts table')
it('adds notification tracking columns to post_conversions table')
it('creates indexes for performance')
it('rolls back migrations correctly')
it('casts conversion dates correctly')
it('scopes return correct posts')
it('isEligibleForConversion returns true when eligible')
it('hasReachedDismissLimit returns true after 3 dismissals')
it('shouldReprompt returns true after 7 days')
```

#### Services
```bash
php artisan make:test --pest Services/ConversionEligibilityServiceTest
php artisan make:test --pest Services/ActivityConversionServiceTest
```

**Test Cases**:
```php
// ConversionEligibilityService
it('prompts at 5 reactions with soft threshold')
it('prompts at 10 reactions with strong threshold')
it('does not prompt if already prompted within 7 days')
it('re-prompts after 7 days')
it('does not prompt after 3 dismissals')
it('prevents duplicate prompts (idempotency)')
it('returns correct no-prompt reason')

// ActivityConversionService
it('creates activity from post successfully')
it('pre-fills activity data from post')
it('syncs tags from post to activity')
it('creates post conversion record')
it('updates post status to converted')
it('throws exception if post not eligible')
it('calculates suggested capacity correctly')
it('returns preview data correctly')
```

#### Events & Listeners
```bash
php artisan make:test --pest Events/PostConversionEventsTest
```

**Test Cases**:
```php
it('dispatches PostConversionPrompted event')
it('dispatches PostConvertedToEvent event')
it('listener creates notification for post owner')
it('listener notifies interested users in batches')
it('listener migrates post invitations')
it('events serialize correctly')
```

#### Jobs
```bash
php artisan make:test --pest Jobs/SendConversionNotificationBatchTest
```

**Test Cases**:
```php
it('creates notifications for all users in batch')
it('includes correct notification data')
it('handles missing users gracefully')
it('can be queued successfully')
```

### Agent B: Frontend Unit Tests

#### Livewire Components
```bash
php artisan make:test --pest Livewire/Profile/InterestedTabTest
php artisan make:test --pest Livewire/Modals/ConvertPostModalTest
```

**Test Cases**:
```php
// InterestedTab
it('displays interested posts correctly')
it('filters by active posts')
it('filters by converted posts')
it('filters by expired posts')
it('removes interest successfully')
it('shows empty state when no posts')
it('paginates results')
it('only allows post owner to remove interest')

// ConvertPostModal
it('opens with correct post data')
it('pre-fills form from post')
it('calculates smart defaults correctly')
it('validates required fields')
it('validates start time is in future')
it('validates end time is after start time')
it('submits conversion successfully')
it('handles conversion errors')
it('closes modal on cancel')
it('only allows post owner to convert')
```

#### Blade Components
```bash
php artisan make:test --pest Components/ConversionBadgeTest
php artisan make:test --pest Components/ConvertedPostOverlayTest
```

**Test Cases**:
```php
// ConversionBadge
it('shows soft badge at 5 reactions')
it('shows strong badge at 10 reactions')
it('does not show after 3 dismissals')
it('only visible to post owner')
it('dispatches modal event on click')

// ConvertedPostOverlay
it('shows overlay for converted posts')
it('links to correct event')
it('shows reaction count at conversion')
it('does not show for active posts')
```

---

## 2. Integration Tests (25% of tests)

### Shared Integration Tests

```bash
php artisan make:test --pest Integration/PostToEventConversionFlowTest
```

**Test Cases**:
```php
it('completes full conversion flow', function () {
    // 1. Create post
    $post = Post::factory()->create();
    
    // 2. Add 5 reactions
    PostReaction::factory()->count(5)->create(['post_id' => $post->id]);
    
    // 3. Check eligibility
    $eligibility = app(ConversionEligibilityService::class)->checkAndPrompt($post);
    expect($eligibility['should_prompt'])->toBeTrue();
    expect($eligibility['threshold'])->toBe('soft');
    
    // 4. Verify notification created
    expect(Notification::where('user_id', $post->user_id)->count())->toBe(1);
    
    // 5. Convert post
    $activity = app(PostService::class)->convertToEvent($post->id, [
        'start_time' => now()->addDay(),
        'end_time' => now()->addDay()->addHours(2),
        'max_attendees' => 10,
        'price' => 0,
    ]);
    
    // 6. Verify activity created
    expect($activity)->toBeInstanceOf(Activity::class);
    expect($activity->originated_from_post_id)->toBe($post->id);
    
    // 7. Verify post status updated
    expect($post->fresh()->status)->toBe('converted');
    
    // 8. Verify conversion record created
    expect(PostConversion::where('post_id', $post->id)->count())->toBe(1);
    
    // 9. Verify notifications queued for interested users
    expect(Notification::where('type', 'post_converted_to_event')->count())->toBe(5);
});

it('handles dismissal and re-prompt correctly', function () {
    $post = Post::factory()->create(['reaction_count' => 5]);
    
    // First prompt
    app(ConversionEligibilityService::class)->checkAndPrompt($post);
    expect($post->fresh()->conversion_prompted_at)->not->toBeNull();
    
    // Dismiss
    app(PostService::class)->dismissConversionPrompt($post->id);
    expect($post->fresh()->conversion_dismiss_count)->toBe(1);
    
    // Should not re-prompt immediately
    $result = app(ConversionEligibilityService::class)->checkAndPrompt($post);
    expect($result['should_prompt'])->toBeFalse();
    
    // Travel 8 days
    $this->travel(8)->days();
    
    // Should re-prompt now
    $result = app(ConversionEligibilityService::class)->checkAndPrompt($post);
    expect($result['should_prompt'])->toBeTrue();
});

it('prevents race conditions on simultaneous reactions', function () {
    $post = Post::factory()->create(['reaction_count' => 4]);
    
    // Simulate 2 users reacting simultaneously
    $user1 = User::factory()->create();
    $user2 = User::factory()->create();
    
    // Both reactions should succeed
    app(PostService::class)->toggleReaction($post->id, 'im_down', $user1);
    app(PostService::class)->toggleReaction($post->id, 'im_down', $user2);
    
    // Should only prompt once (idempotency)
    expect(Notification::where('type', 'post_conversion_prompt')->count())->toBe(1);
});

it('rolls back transaction on conversion failure', function () {
    $post = Post::factory()->create(['reaction_count' => 5]);
    
    try {
        // Invalid data (missing required fields)
        app(PostService::class)->convertToEvent($post->id, []);
    } catch (\Exception $e) {
        // Expected
    }
    
    // Verify no partial data created
    expect(Activity::where('originated_from_post_id', $post->id)->count())->toBe(0);
    expect(PostConversion::where('post_id', $post->id)->count())->toBe(0);
    expect($post->fresh()->status)->not->toBe('converted');
});

it('batches notifications correctly', function () {
    $post = Post::factory()->create();
    
    // Create 25 interested users
    PostReaction::factory()->count(25)->create([
        'post_id' => $post->id,
        'reaction_type' => 'im_down',
    ]);
    
    // Convert post
    $activity = app(PostService::class)->convertToEvent($post->id, [
        'start_time' => now()->addDay(),
        'end_time' => now()->addDay()->addHours(2),
        'max_attendees' => 30,
        'price' => 0,
    ]);
    
    // Verify 3 batches queued (10 + 10 + 5)
    expect(Queue::size())->toBe(3);
});

it('migrates post invitations to event', function () {
    $post = Post::factory()->create();
    
    // Create pending invitations
    PostInvitation::factory()->count(5)->create([
        'post_id' => $post->id,
        'status' => 'pending',
    ]);
    
    // Convert post
    $activity = app(PostService::class)->convertToEvent($post->id, [
        'start_time' => now()->addDay(),
        'end_time' => now()->addDay()->addHours(2),
        'max_attendees' => 10,
        'price' => 0,
    ]);
    
    // Verify invitations migrated
    expect(PostInvitation::where('post_id', $post->id)->where('status', 'migrated')->count())->toBe(5);
    
    // Verify notifications sent to invited users
    expect(Notification::where('type', 'post_invitation_converted')->count())->toBe(5);
});

it('respects privacy (interested users not exposed)', function () {
    $post = Post::factory()->create();
    PostReaction::factory()->count(10)->create(['post_id' => $post->id]);
    
    // Get preview data
    $preview = app(ActivityConversionService::class)->previewConversion($post, []);
    
    // Should only return count, not user details
    expect($preview)->toHaveKey('interested_users_count');
    expect($preview)->not->toHaveKey('interested_users');
    expect($preview['interested_users_count'])->toBe(10);
});
```

---

## 3. End-to-End Tests (5% of tests)

### Browser Tests with Laravel Dusk

```bash
php artisan make:test --pest Browser/PostConversionE2ETest
```

**Test Cases**:
```php
it('user can react to post and see conversion badge', function () {
    $this->browse(function (Browser $browser) {
        $post = Post::factory()->create(['reaction_count' => 4]);

        $browser->loginAs(User::factory()->create())
                ->visit('/discovery/nearby')
                ->assertSee($post->title)
                ->click('@im-down-button-' . $post->id)
                ->waitForText('5')
                ->assertSee('⭐ Ready'); // Soft badge appears
    });
});

it('post owner can convert post to event via modal', function () {
    $this->browse(function (Browser $browser) {
        $user = User::factory()->create();
        $post = Post::factory()->create([
            'user_id' => $user->id,
            'reaction_count' => 10,
        ]);

        $browser->loginAs($user)
                ->visit('/profile/' . $user->username)
                ->click('@posts-tab')
                ->assertSee($post->title)
                ->click('@convert-badge-' . $post->id)
                ->waitFor('@conversion-modal')
                ->assertInputValue('title', $post->title)
                ->type('start_time', now()->addDay()->format('Y-m-d\TH:i'))
                ->type('end_time', now()->addDay()->addHours(2)->format('Y-m-d\TH:i'))
                ->type('max_attendees', '15')
                ->click('@preview-button')
                ->waitForText('Event Preview')
                ->assertSee($post->title)
                ->click('@confirm-button')
                ->waitForText('Post converted to event successfully')
                ->assertPathIs('/activities/*');
    });
});

it('interested users receive notification and can RSVP', function () {
    $this->browse(function (Browser $browser1, Browser $browser2) {
        $owner = User::factory()->create();
        $interested = User::factory()->create();

        $post = Post::factory()->create(['user_id' => $owner->id]);
        PostReaction::factory()->create([
            'post_id' => $post->id,
            'user_id' => $interested->id,
            'reaction_type' => 'im_down',
        ]);

        // Owner converts post
        $browser1->loginAs($owner)
                 ->visit('/posts/' . $post->id)
                 ->click('@convert-button')
                 ->waitFor('@conversion-modal')
                 ->fillConversionForm()
                 ->click('@submit-button')
                 ->waitForText('success');

        // Interested user sees notification
        $browser2->loginAs($interested)
                 ->visit('/notifications')
                 ->waitForText($post->title . ' is now an event!')
                 ->click('@notification-' . $post->id)
                 ->waitForText('RSVP to Event')
                 ->click('@rsvp-button')
                 ->waitForText('You\'re attending!');
    });
});

it('converted post shows overlay and event link', function () {
    $this->browse(function (Browser $browser) {
        $post = Post::factory()->create(['status' => 'converted']);
        $activity = Activity::factory()->create(['originated_from_post_id' => $post->id]);

        $browser->loginAs(User::factory()->create())
                ->visit('/posts/' . $post->id)
                ->assertSee('Converted to Event')
                ->assertSee('View Event')
                ->click('@view-event-button')
                ->waitForLocation('/activities/' . $activity->id)
                ->assertSee($activity->title);
    });
});

it('profile interested tab shows correct posts', function () {
    $this->browse(function (Browser $browser) {
        $user = User::factory()->create();
        $post1 = Post::factory()->create(['status' => 'active']);
        $post2 = Post::factory()->create(['status' => 'converted']);

        PostReaction::factory()->create([
            'user_id' => $user->id,
            'post_id' => $post1->id,
            'reaction_type' => 'im_down',
        ]);
        PostReaction::factory()->create([
            'user_id' => $user->id,
            'post_id' => $post2->id,
            'reaction_type' => 'im_down',
        ]);

        $browser->loginAs($user)
                ->visit('/profile/' . $user->username)
                ->click('@interested-tab')
                ->assertSee($post1->title)
                ->assertSee($post2->title)
                ->click('@filter-converted')
                ->waitUntilMissing($post1->title)
                ->assertSee($post2->title);
    });
});
```

---

## 4. Performance Tests

### Load Testing

```bash
php artisan make:test --pest Performance/ConversionPerformanceTest
```

**Test Cases**:
```php
it('handles 100 simultaneous reactions efficiently', function () {
    $post = Post::factory()->create();
    $users = User::factory()->count(100)->create();

    $startTime = microtime(true);

    foreach ($users as $user) {
        app(PostService::class)->toggleReaction($post->id, 'im_down', $user);
    }

    $endTime = microtime(true);
    $duration = $endTime - $startTime;

    expect($duration)->toBeLessThan(5); // Should complete in < 5 seconds
    expect($post->fresh()->reaction_count)->toBe(100);
});

it('conversion API responds in < 200ms', function () {
    $post = Post::factory()->create(['reaction_count' => 10]);

    $startTime = microtime(true);

    $response = $this->actingAs($post->user)
                     ->getJson("/api/posts/{$post->id}/conversion/eligibility");

    $endTime = microtime(true);
    $duration = ($endTime - $startTime) * 1000; // Convert to ms

    expect($duration)->toBeLessThan(200);
    expect($response->status())->toBe(200);
});

it('batched notifications process efficiently', function () {
    $post = Post::factory()->create();
    PostReaction::factory()->count(100)->create(['post_id' => $post->id]);

    $startTime = microtime(true);

    $activity = app(PostService::class)->convertToEvent($post->id, [
        'start_time' => now()->addDay(),
        'end_time' => now()->addDay()->addHours(2),
        'max_attendees' => 150,
        'price' => 0,
    ]);

    $endTime = microtime(true);
    $duration = $endTime - $startTime;

    expect($duration)->toBeLessThan(2); // Should complete in < 2 seconds
    expect(Queue::size())->toBe(10); // 100 users / 10 per batch
});
```

---

## 5. Accessibility Tests

### WCAG 2.1 AA Compliance

```bash
php artisan make:test --pest Accessibility/ConversionAccessibilityTest
```

**Test Cases**:
```php
it('conversion modal is keyboard navigable', function () {
    $this->browse(function (Browser $browser) {
        $post = Post::factory()->create(['reaction_count' => 10]);

        $browser->loginAs($post->user)
                ->visit('/posts/' . $post->id)
                ->keys('@convert-badge', '{enter}') // Open with Enter
                ->waitFor('@conversion-modal')
                ->keys('body', '{tab}') // Tab to first field
                ->assertFocused('@title-input')
                ->keys('body', '{tab}', '{tab}', '{tab}') // Tab through fields
                ->assertFocused('@description-input');
    });
});

it('conversion badge has proper ARIA labels', function () {
    $post = Post::factory()->create(['reaction_count' => 10]);

    $this->actingAs($post->user)
         ->get('/posts/' . $post->id)
         ->assertSee('aria-label="Convert post to event"', false);
});

it('form errors are announced to screen readers', function () {
    $this->browse(function (Browser $browser) {
        $post = Post::factory()->create(['reaction_count' => 10]);

        $browser->loginAs($post->user)
                ->visit('/posts/' . $post->id)
                ->click('@convert-badge')
                ->waitFor('@conversion-modal')
                ->clear('@title-input')
                ->click('@submit-button')
                ->waitForText('The title field is required')
                ->assertAttribute('@title-input', 'aria-invalid', 'true')
                ->assertAttribute('@title-error', 'role', 'alert');
    });
});

it('color contrast meets WCAG AA standards', function () {
    // Use automated tool like axe-core
    $this->browse(function (Browser $browser) {
        $post = Post::factory()->create(['reaction_count' => 10]);

        $browser->loginAs($post->user)
                ->visit('/posts/' . $post->id)
                ->assertNoAccessibilityViolations(); // Custom assertion
    });
});
```

---

## 6. Security Tests

```bash
php artisan make:test --pest Security/ConversionSecurityTest
```

**Test Cases**:
```php
it('prevents unauthorized users from converting posts', function () {
    $post = Post::factory()->create();
    $attacker = User::factory()->create();

    $response = $this->actingAs($attacker)
                     ->postJson("/api/posts/{$post->id}/conversion/convert", [
                         'start_time' => now()->addDay(),
                         'end_time' => now()->addDay()->addHours(2),
                         'max_attendees' => 10,
                         'price' => 0,
                     ]);

    expect($response->status())->toBe(403);
    expect($post->fresh()->status)->not->toBe('converted');
});

it('sanitizes user input in conversion form', function () {
    $post = Post::factory()->create();

    $response = $this->actingAs($post->user)
                     ->postJson("/api/posts/{$post->id}/conversion/convert", [
                         'title' => '<script>alert("XSS")</script>',
                         'description' => '<img src=x onerror=alert(1)>',
                         'start_time' => now()->addDay(),
                         'end_time' => now()->addDay()->addHours(2),
                         'max_attendees' => 10,
                         'price' => 0,
                     ]);

    $activity = Activity::where('originated_from_post_id', $post->id)->first();
    expect($activity->title)->not->toContain('<script>');
    expect($activity->description)->not->toContain('<img');
});

it('rate limits conversion attempts', function () {
    $post = Post::factory()->create();

    // Attempt 10 conversions in quick succession
    for ($i = 0; $i < 10; $i++) {
        $response = $this->actingAs($post->user)
                         ->postJson("/api/posts/{$post->id}/conversion/convert", [
                             'start_time' => now()->addDay(),
                             'end_time' => now()->addDay()->addHours(2),
                             'max_attendees' => 10,
                             'price' => 0,
                         ]);
    }

    expect($response->status())->toBe(429); // Too Many Requests
});
```

---

## Test Execution Plan

### Local Development
```bash
# Run all tests
php artisan test

# Run specific test suite
php artisan test --filter=Conversion

# Run with coverage
php artisan test --coverage --min=95

# Run performance tests
php artisan test --group=performance

# Run accessibility tests
php artisan test --group=accessibility
```

### CI/CD Pipeline
```yaml
# .github/workflows/tests.yml
name: Tests

on: [push, pull_request]

jobs:
  test:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v2
      - name: Setup PHP
        uses: shivammathur/setup-php@v2
        with:
          php-version: 8.3
      - name: Install Dependencies
        run: composer install
      - name: Run Unit Tests
        run: php artisan test --filter=Unit
      - name: Run Integration Tests
        run: php artisan test --filter=Integration
      - name: Run E2E Tests
        run: php artisan dusk
      - name: Check Coverage
        run: php artisan test --coverage --min=95
```

---

## Success Criteria

### Coverage Targets
- **Unit Tests**: 95%+ coverage
- **Integration Tests**: All critical paths covered
- **E2E Tests**: All user flows tested
- **Performance**: All endpoints < 200ms
- **Accessibility**: WCAG 2.1 AA compliant
- **Security**: No vulnerabilities found

### Quality Gates
- ✅ All tests passing
- ✅ No flaky tests
- ✅ Coverage > 95%
- ✅ Performance benchmarks met
- ✅ Accessibility audit passed
- ✅ Security scan clean

---

*End of Testing Strategy*


