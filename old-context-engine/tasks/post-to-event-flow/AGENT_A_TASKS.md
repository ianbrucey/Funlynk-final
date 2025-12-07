# Agent A: Backend Foundation & Conversion Logic

> **Focus**: Service layer, business logic, database operations, event handling  
> **Estimated Time**: 6-7 days  
> **Dependencies**: E01 (Notifications), E03 (Activities)

---

## A1: Database Migrations & Models (Day 1)

### Task Overview
Create database migrations for conversion tracking and update models with new functionality.

### Implementation Steps

#### Step 1: Create Migration for Posts Table
```bash
php artisan make:migration add_conversion_tracking_to_posts_table --no-interaction
```

**Migration Content**:
```php
public function up(): void
{
    Schema::table('posts', function (Blueprint $table) {
        $table->timestamp('conversion_prompted_at')->nullable()->after('expires_at');
        $table->timestamp('conversion_dismissed_at')->nullable()->after('conversion_prompted_at');
        $table->integer('conversion_dismiss_count')->default(0)->after('conversion_dismissed_at');
        
        // Indexes for performance
        $table->index('conversion_prompted_at');
        $table->index(['status', 'reaction_count']);
    });
}

public function down(): void
{
    Schema::table('posts', function (Blueprint $table) {
        $table->dropIndex(['conversion_prompted_at']);
        $table->dropIndex(['status', 'reaction_count']);
        $table->dropColumn(['conversion_prompted_at', 'conversion_dismissed_at', 'conversion_dismiss_count']);
    });
}
```

#### Step 2: Create Migration for Post Conversions Table
```bash
php artisan make:migration add_notification_tracking_to_post_conversions_table --no-interaction
```

**Migration Content**:
```php
public function up(): void
{
    Schema::table('post_conversions', function (Blueprint $table) {
        $table->integer('interested_users_notified')->default(0)->after('activity_id');
        $table->integer('invited_users_notified')->default(0)->after('interested_users_notified');
        $table->timestamp('notification_sent_at')->nullable()->after('invited_users_notified');
    });
}
```

#### Step 3: Create Migration for Post Reactions Index
```bash
php artisan make:migration add_index_to_post_reactions_table --no-interaction
```

**Migration Content**:
```php
public function up(): void
{
    Schema::table('post_reactions', function (Blueprint $table) {
        $table->index(['post_id', 'reaction_type']);
    });
}
```

#### Step 4: Update Post Model
**File**: `app/Models/Post.php`

Add to casts:
```php
protected function casts(): array
{
    return [
        // ... existing casts
        'conversion_prompted_at' => 'datetime',
        'conversion_dismissed_at' => 'datetime',
        'conversion_dismiss_count' => 'integer',
    ];
}
```

Add scopes:
```php
public function scopeEligibleForConversion($query)
{
    return $query->where('status', 'active')
                 ->where('reaction_count', '>=', 5);
}

public function scopeNotPrompted($query)
{
    return $query->whereNull('conversion_prompted_at');
}

public function scopeConvertedPosts($query)
{
    return $query->where('status', 'converted');
}
```

Add helper methods:
```php
public function isEligibleForConversion(): bool
{
    return $this->status === 'active' 
        && $this->reaction_count >= 5
        && !$this->hasReachedDismissLimit();
}

public function hasReachedDismissLimit(): bool
{
    return $this->conversion_dismiss_count >= 3;
}

public function shouldReprompt(): bool
{
    if (!$this->conversion_dismissed_at) {
        return false;
    }
    
    // Re-prompt after 7 days
    return $this->conversion_dismissed_at->addDays(7)->isPast();
}
```

#### Step 5: Update PostConversion Model
**File**: `app/Models/PostConversion.php`

Add to casts:
```php
protected function casts(): array
{
    return [
        // ... existing casts
        'interested_users_notified' => 'integer',
        'invited_users_notified' => 'integer',
        'notification_sent_at' => 'datetime',
    ];
}
```

### Testing
```bash
php artisan make:test --pest Database/PostConversionMigrationsTest --no-interaction
```

**Test Cases**:
- Migration runs successfully
- Rollback works correctly
- Indexes are created
- Model casts work correctly
- Scopes return correct results
- Helper methods return expected values

### Deliverables
- ✅ 3 migration files
- ✅ Updated Post model with scopes and helpers
- ✅ Updated PostConversion model
- ✅ Migration tests passing

---

## A2: Service Layer - Conversion Eligibility (Day 1-2)

### Task Overview
Implement business logic for determining when to prompt post owners for conversion.

### Implementation Steps

#### Step 1: Create ConversionEligibilityService
```bash
php artisan make:class Services/ConversionEligibilityService --no-interaction
```

**File**: `app/Services/ConversionEligibilityService.php`

```php
<?php

namespace App\Services;

use App\Models\Post;
use App\Events\PostConversionPrompted;
use Illuminate\Support\Facades\DB;

class ConversionEligibilityService
{
    public function checkAndPrompt(Post $post): array
    {
        // Idempotency check
        if (!$this->shouldPrompt($post)) {
            return [
                'should_prompt' => false,
                'reason' => $this->getNoPromptReason($post),
            ];
        }
        
        $threshold = $this->getThresholdLevel($post);
        
        // Mark as prompted (idempotent)
        DB::transaction(function () use ($post) {
            $post->update(['conversion_prompted_at' => now()]);
        });
        
        // Dispatch event for notification
        event(new PostConversionPrompted($post, $threshold));
        
        return [
            'should_prompt' => true,
            'threshold' => $threshold,
            'reaction_count' => $post->reaction_count,
        ];
    }
    
    protected function shouldPrompt(Post $post): bool
    {
        // Not eligible
        if (!$post->isEligibleForConversion()) {
            return false;
        }
        
        // Already prompted and not time to re-prompt
        if ($post->conversion_prompted_at && !$post->shouldReprompt()) {
            return false;
        }
        
        // Reached dismiss limit
        if ($post->hasReachedDismissLimit()) {
            return false;
        }
        
        return true;
    }
    
    protected function getThresholdLevel(Post $post): string
    {
        if ($post->reaction_count >= 10) {
            return 'strong'; // 10+ reactions
        }
        
        return 'soft'; // 5-9 reactions
    }
    
    protected function getNoPromptReason(Post $post): string
    {
        if ($post->status !== 'active') {
            return 'post_not_active';
        }
        
        if ($post->reaction_count < 5) {
            return 'insufficient_reactions';
        }
        
        if ($post->hasReachedDismissLimit()) {
            return 'dismiss_limit_reached';
        }
        
        if ($post->conversion_prompted_at && !$post->shouldReprompt()) {
            return 'already_prompted';
        }
        
        return 'unknown';
    }
}
```

#### Step 2: Add Methods to PostService
**File**: `app/Services/PostService.php`

```php
public function dismissConversionPrompt(string $postId, ?User $user = null): void
{
    $user = $user ?? auth()->user();
    $post = Post::findOrFail($postId);
    
    // Authorization check
    if ($post->user_id !== $user->id) {
        throw new \Exception('Unauthorized');
    }
    
    DB::transaction(function () use ($post) {
        $post->update([
            'conversion_dismissed_at' => now(),
            'conversion_dismiss_count' => $post->conversion_dismiss_count + 1,
        ]);
    });
}

public function getConversionEligibility(string $postId): array
{
    $post = Post::findOrFail($postId);
    
    return app(ConversionEligibilityService::class)->checkAndPrompt($post);
}
```

### Testing
```bash
php artisan make:test --pest Services/ConversionEligibilityServiceTest --no-interaction
```

**Test Cases**:
- Prompt at 5 reactions (soft threshold)
- Prompt at 10 reactions (strong threshold)
- Don't prompt if already prompted within 7 days
- Re-prompt after 7 days
- Don't prompt after 3 dismissals
- Idempotency: Multiple calls don't create duplicate prompts
- Authorization: Only post owner can dismiss

### Deliverables
- ✅ ConversionEligibilityService class
- ✅ PostService methods for dismissal and eligibility
- ✅ Unit tests passing (95%+ coverage)

---

## A3: Service Layer - Conversion Execution (Day 2-3)

### Task Overview
Implement the core conversion logic that transforms a post into an event.

### Implementation Steps

#### Step 1: Create ActivityConversionService
```bash
php artisan make:class Services/ActivityConversionService --no-interaction
```

**File**: `app/Services/ActivityConversionService.php`

```php
<?php

namespace App\Services;

use App\Models\{Post, Activity, PostConversion, User};
use App\Events\PostConvertedToEvent;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ActivityConversionService
{
    public function createFromPost(Post $post, array $eventData, User $host): Activity
    {
        return DB::transaction(function () use ($post, $eventData, $host) {
            // Validate post is eligible
            if (!$post->isEligibleForConversion()) {
                throw new \Exception('Post is not eligible for conversion');
            }

            // Create activity with pre-filled data
            $activity = Activity::create([
                'id' => Str::uuid(),
                'user_id' => $host->id,
                'title' => $eventData['title'] ?? $post->title,
                'description' => $eventData['description'] ?? $post->description,
                'location_name' => $eventData['location_name'] ?? $post->location_name,
                'location_coordinates' => $eventData['location_coordinates'] ?? $post->location_coordinates,
                'start_time' => $eventData['start_time'],
                'end_time' => $eventData['end_time'],
                'max_attendees' => $eventData['max_attendees'],
                'price' => $eventData['price'] ?? 0,
                'is_paid' => ($eventData['price'] ?? 0) > 0,
                'status' => 'published',
                'originated_from_post_id' => $post->id,
            ]);

            // Sync tags
            if (isset($eventData['tags'])) {
                $activity->tags()->sync($eventData['tags']);
            } else {
                $activity->tags()->sync($post->tags->pluck('id'));
            }

            // Create conversion record
            $conversion = PostConversion::create([
                'post_id' => $post->id,
                'activity_id' => $activity->id,
                'converted_by' => $host->id,
                'reaction_count_at_conversion' => $post->reaction_count,
            ]);

            // Update post status
            $post->update(['status' => 'converted']);

            // Dispatch event for notifications
            event(new PostConvertedToEvent($post, $activity, $conversion));

            return $activity;
        });
    }

    public function previewConversion(Post $post, array $eventData): array
    {
        // Get interested users count
        $interestedCount = $post->reactions()
            ->where('reaction_type', 'im_down')
            ->count();

        // Get invited users count (pending invitations)
        $invitedCount = $post->invitations()
            ->where('status', 'pending')
            ->count();

        // Calculate suggested capacity
        $suggestedCapacity = (int) ceil($interestedCount * 1.5);

        return [
            'interested_users_count' => $interestedCount,
            'invited_users_count' => $invitedCount,
            'total_potential_attendees' => $interestedCount + $invitedCount,
            'suggested_capacity' => max($suggestedCapacity, 10), // Min 10
            'event_preview' => [
                'title' => $eventData['title'] ?? $post->title,
                'description' => $eventData['description'] ?? $post->description,
                'location' => $eventData['location_name'] ?? $post->location_name,
                'start_time' => $eventData['start_time'] ?? null,
                'price' => $eventData['price'] ?? 0,
            ],
        ];
    }
}
```

#### Step 2: Add Conversion Method to PostService
**File**: `app/Services/PostService.php`

```php
public function convertToEvent(string $postId, array $eventData, ?User $user = null): Activity
{
    $user = $user ?? auth()->user();
    $post = Post::with(['tags', 'reactions', 'invitations'])->findOrFail($postId);

    // Authorization check
    if ($post->user_id !== $user->id) {
        throw new \Exception('Unauthorized: Only post owner can convert');
    }

    // Validate required event fields
    $this->validateEventData($eventData);

    return app(ActivityConversionService::class)->createFromPost($post, $eventData, $user);
}

protected function validateEventData(array $data): void
{
    $required = ['start_time', 'end_time', 'max_attendees'];

    foreach ($required as $field) {
        if (!isset($data[$field])) {
            throw new \Exception("Missing required field: {$field}");
        }
    }

    // Validate times
    if (strtotime($data['start_time']) < now()->timestamp) {
        throw new \Exception('Start time must be in the future');
    }

    if (strtotime($data['end_time']) <= strtotime($data['start_time'])) {
        throw new \Exception('End time must be after start time');
    }

    // Validate capacity
    if ($data['max_attendees'] < 1) {
        throw new \Exception('Max attendees must be at least 1');
    }
}
```

### Testing
```bash
php artisan make:test --pest Services/ActivityConversionServiceTest --no-interaction
```

**Test Cases**:
- Successfully convert post to event
- Pre-fill event data from post
- Sync tags from post to event
- Create PostConversion record
- Update post status to 'converted'
- Dispatch PostConvertedToEvent event
- Transaction rollback on failure
- Authorization: Only post owner can convert
- Validation: Required fields enforced
- Validation: Start time in future
- Validation: End time after start time

### Deliverables
- ✅ ActivityConversionService class
- ✅ PostService conversion methods
- ✅ Validation logic
- ✅ Unit tests passing (95%+ coverage)

---

## A4: Event System (Day 3-4)

### Task Overview
Create Laravel events and listeners for the conversion workflow.

### Implementation Steps

#### Step 1: Create PostConversionPrompted Event
```bash
php artisan make:event PostConversionPrompted --no-interaction
```

**File**: `app/Events/PostConversionPrompted.php`

```php
<?php

namespace App\Events;

use App\Models\Post;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class PostConversionPrompted
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public Post $post,
        public string $threshold // 'soft' or 'strong'
    ) {}
}
```

#### Step 2: Create PostConvertedToEvent Event
```bash
php artisan make:event PostConvertedToEvent --no-interaction
```

**File**: `app/Events/PostConvertedToEvent.php`

```php
<?php

namespace App\Events;

use App\Models\{Post, Activity, PostConversion};
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class PostConvertedToEvent
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public Post $post,
        public Activity $activity,
        public PostConversion $conversion
    ) {}
}
```

#### Step 3: Create PostInvitationMigrated Event
```bash
php artisan make:event PostInvitationMigrated --no-interaction
```

**File**: `app/Events/PostInvitationMigrated.php`

```php
<?php

namespace App\Events;

use App\Models\{PostInvitation, Activity};
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class PostInvitationMigrated
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public PostInvitation $invitation,
        public Activity $activity
    ) {}
}
```

#### Step 4: Create Listeners
```bash
php artisan make:listener SendConversionPromptNotification --event=PostConversionPrompted --no-interaction
php artisan make:listener NotifyInterestedUsers --event=PostConvertedToEvent --no-interaction
php artisan make:listener MigratePostInvitations --event=PostConvertedToEvent --no-interaction
```

**File**: `app/Listeners/SendConversionPromptNotification.php`

```php
<?php

namespace App\Listeners;

use App\Events\PostConversionPrompted;
use App\Models\Notification;

class SendConversionPromptNotification
{
    public function handle(PostConversionPrompted $event): void
    {
        Notification::create([
            'user_id' => $event->post->user_id,
            'type' => 'post_conversion_prompt',
            'data' => [
                'post_id' => $event->post->id,
                'post_title' => $event->post->title,
                'reaction_count' => $event->post->reaction_count,
                'threshold' => $event->threshold,
                'message' => $this->getMessage($event->threshold, $event->post->reaction_count),
            ],
        ]);
    }

    protected function getMessage(string $threshold, int $count): string
    {
        if ($threshold === 'strong') {
            return "🔥 {$count}+ people want to join! Turn this into an event now.";
        }

        return "🎉 {$count} people are interested! Consider creating an event.";
    }
}
```

#### Step 5: Register Listeners
**File**: `app/Providers/EventServiceProvider.php`

```php
protected $listen = [
    PostConversionPrompted::class => [
        SendConversionPromptNotification::class,
    ],
    PostConvertedToEvent::class => [
        NotifyInterestedUsers::class,
        MigratePostInvitations::class,
    ],
];
```

### Testing
```bash
php artisan make:test --pest Events/PostConversionEventsTest --no-interaction
```

**Test Cases**:
- PostConversionPrompted event dispatched correctly
- PostConvertedToEvent event dispatched correctly
- Listeners receive correct data
- Notifications created for post owner
- Event serialization works correctly

### Deliverables
- ✅ 3 event classes
- ✅ 3 listener classes
- ✅ EventServiceProvider registration
- ✅ Event tests passing

---

## A5: Notification Service (Day 4-5)

### Task Overview
Implement batched notification system for interested users.

### Implementation Steps

#### Step 1: Create NotifyInterestedUsers Listener
**File**: `app/Listeners/NotifyInterestedUsers.php`

```php
<?php

namespace App\Listeners;

use App\Events\PostConvertedToEvent;
use App\Jobs\SendConversionNotificationBatch;
use Illuminate\Support\Facades\DB;

class NotifyInterestedUsers
{
    public function handle(PostConvertedToEvent $event): void
    {
        // Get all interested users (ordered by most recent reaction first)
        $interestedUserIds = DB::table('post_reactions')
            ->where('post_id', $event->post->id)
            ->where('reaction_type', 'im_down')
            ->orderBy('created_at', 'desc')
            ->pluck('user_id')
            ->unique()
            ->toArray();

        // Batch into groups of 10
        $batches = array_chunk($interestedUserIds, 10);

        // Dispatch jobs with delays (5 minutes between batches)
        foreach ($batches as $index => $batch) {
            SendConversionNotificationBatch::dispatch(
                $event->activity,
                $event->post,
                $batch
            )->delay(now()->addMinutes($index * 5));
        }

        // Update conversion record
        $event->conversion->update([
            'interested_users_notified' => count($interestedUserIds),
            'notification_sent_at' => now(),
        ]);
    }
}
```

#### Step 2: Create SendConversionNotificationBatch Job
```bash
php artisan make:job SendConversionNotificationBatch --no-interaction
```

**File**: `app/Jobs/SendConversionNotificationBatch.php`

```php
<?php

namespace App\Jobs;

use App\Models\{Activity, Post, Notification};
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\{InteractsWithQueue, SerializesModels};

class SendConversionNotificationBatch implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public Activity $activity,
        public Post $post,
        public array $userIds
    ) {}

    public function handle(): void
    {
        foreach ($this->userIds as $userId) {
            Notification::create([
                'user_id' => $userId,
                'type' => 'post_converted_to_event',
                'data' => [
                    'post_id' => $this->post->id,
                    'post_title' => $this->post->title,
                    'activity_id' => $this->activity->id,
                    'activity_title' => $this->activity->title,
                    'host_name' => $this->activity->user->display_name,
                    'start_time' => $this->activity->start_time->toIso8601String(),
                    'location' => $this->activity->location_name,
                    'price' => $this->activity->price,
                    'is_free' => $this->activity->price == 0,
                    'attending_count' => $this->activity->rsvps()->where('status', 'attending')->count(),
                ],
            ]);
        }
    }
}
```

#### Step 3: Create MigratePostInvitations Listener
**File**: `app/Listeners/MigratePostInvitations.php`

```php
<?php

namespace App\Listeners;

use App\Events\{PostConvertedToEvent, PostInvitationMigrated};
use App\Models\Notification;

class MigratePostInvitations
{
    public function handle(PostConvertedToEvent $event): void
    {
        // Get pending invitations
        $invitations = $event->post->invitations()
            ->where('status', 'pending')
            ->get();

        foreach ($invitations as $invitation) {
            // Create notification for invited user
            Notification::create([
                'user_id' => $invitation->invitee_id,
                'type' => 'post_invitation_converted',
                'data' => [
                    'post_id' => $event->post->id,
                    'activity_id' => $event->activity->id,
                    'inviter_name' => $invitation->inviter->display_name,
                    'message' => "You were invited to a post that's now an event!",
                ],
            ]);

            // Mark invitation as migrated
            $invitation->update(['status' => 'migrated']);

            // Dispatch event
            event(new PostInvitationMigrated($invitation, $event->activity));
        }

        // Update conversion record
        $event->conversion->update([
            'invited_users_notified' => $invitations->count(),
        ]);
    }
}
```

### Testing
```bash
php artisan make:test --pest Jobs/SendConversionNotificationBatchTest --no-interaction
php artisan make:test --pest Listeners/NotifyInterestedUsersTest --no-interaction
```

**Test Cases**:
- Notifications batched correctly (10 per batch)
- Batches delayed by 5 minutes
- Most recent reactors notified first
- Conversion record updated with counts
- Invitations migrated correctly
- Jobs queued successfully

### Deliverables
- ✅ NotifyInterestedUsers listener
- ✅ SendConversionNotificationBatch job
- ✅ MigratePostInvitations listener
- ✅ Unit tests passing

---

## A6: API Endpoints (Day 5-6)

### Task Overview
Create API endpoints for conversion operations.

### Implementation Steps

#### Step 1: Create PostConversionController
```bash
php artisan make:controller Api/PostConversionController --no-interaction
```

**File**: `app/Http/Controllers/Api/PostConversionController.php`

```php
<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\{PostService, ActivityConversionService};
use Illuminate\Http\{Request, JsonResponse};

class PostConversionController extends Controller
{
    public function __construct(
        protected PostService $postService,
        protected ActivityConversionService $conversionService
    ) {}

    public function checkEligibility(string $postId): JsonResponse
    {
        try {
            $eligibility = $this->postService->getConversionEligibility($postId);

            return response()->json($eligibility);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        }
    }

    public function preview(string $postId, Request $request): JsonResponse
    {
        try {
            $post = \App\Models\Post::findOrFail($postId);
            $preview = $this->conversionService->previewConversion($post, $request->all());

            return response()->json($preview);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        }
    }

    public function convert(string $postId, Request $request): JsonResponse
    {
        try {
            $activity = $this->postService->convertToEvent($postId, $request->all());

            return response()->json([
                'success' => true,
                'activity_id' => $activity->id,
                'message' => 'Post converted to event successfully',
            ], 201);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        }
    }

    public function dismissPrompt(string $postId): JsonResponse
    {
        try {
            $this->postService->dismissConversionPrompt($postId);

            return response()->json([
                'success' => true,
                'message' => 'Conversion prompt dismissed',
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        }
    }

    public function getInterestedUsersCount(string $postId): JsonResponse
    {
        try {
            $post = \App\Models\Post::findOrFail($postId);
            $count = $post->reactions()->where('reaction_type', 'im_down')->count();

            return response()->json([
                'count' => $count,
                'post_id' => $postId,
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        }
    }
}
```

#### Step 2: Add Routes
**File**: `routes/api.php`

```php
Route::middleware('auth:sanctum')->group(function () {
    Route::prefix('posts/{post}')->group(function () {
        Route::get('conversion/eligibility', [PostConversionController::class, 'checkEligibility']);
        Route::get('conversion/preview', [PostConversionController::class, 'preview']);
        Route::post('conversion/convert', [PostConversionController::class, 'convert']);
        Route::post('conversion/dismiss-prompt', [PostConversionController::class, 'dismissPrompt']);
        Route::get('interested-users/count', [PostConversionController::class, 'getInterestedUsersCount']);
    });
});
```

### Testing
```bash
php artisan make:test --pest Api/PostConversionApiTest --no-interaction
```

**Test Cases**:
- GET /api/posts/{id}/conversion/eligibility returns correct data
- GET /api/posts/{id}/conversion/preview returns preview data
- POST /api/posts/{id}/conversion/convert creates event
- POST /api/posts/{id}/conversion/dismiss-prompt dismisses prompt
- GET /api/posts/{id}/interested-users/count returns count
- Authorization: Only post owner can convert/dismiss
- Validation: Required fields enforced
- Error handling: Returns appropriate status codes

### Deliverables
- ✅ PostConversionController
- ✅ API routes registered
- ✅ API tests passing
- ✅ Postman/Insomnia collection (optional)

---

## Summary

### Total Deliverables
- ✅ 3 database migrations
- ✅ 2 updated models (Post, PostConversion)
- ✅ 3 service classes
- ✅ 3 events + 3 listeners
- ✅ 1 job (queued)
- ✅ 1 API controller + 5 endpoints
- ✅ 95%+ test coverage

### Integration Points for Agent B
- **Events**: PostConversionPrompted, PostConvertedToEvent
- **API Endpoints**: All 5 endpoints ready for frontend consumption
- **Models**: Post scopes and helpers available
- **Services**: PostService and ActivityConversionService ready

### Next Steps
1. Run all migrations: `php artisan migrate`
2. Run all tests: `php artisan test --filter=Conversion`
3. Verify API endpoints with Postman
4. Coordinate with Agent B on event listeners
5. Monitor queue for notification batches

---

*Agent A tasks complete. Ready for integration with Agent B.*

