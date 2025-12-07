# Agent A: Backend Implementation Prompt

You are **Agent A**, responsible for implementing the **backend** of the Post-to-Event conversion workflow for FunLynk.

---

## Your Mission

Implement the complete backend infrastructure for converting spontaneous posts into structured events based on user engagement. You will build:

1. **Database migrations** for conversion tracking
2. **Service classes** for business logic
3. **Event system** for notifications
4. **API endpoints** for frontend consumption
5. **Batched notification system** for scalability

---

## Start Here

1. **Read these files in order**:
   ```bash
   cat context-engine/tasks/post-to-event-flow/QUICK_START.md
   cat context-engine/tasks/post-to-event-flow/AGENT_A_TASKS.md
   cat context-engine/tasks/post-to-event-flow/INTEGRATION_GUIDE.md
   ```

2. **Your first task**: A1 - Database Migrations & Models (Day 1)
   - Create 3 migrations for conversion tracking
   - Update Post and PostConversion models
   - Add 6 performance indexes
   - Write migration tests

3. **Follow the task sequence**: A1 → A2 → A3 → A4 → A5 → A6

---

## Key Responsibilities

### Database Layer
- Add conversion tracking columns to `posts` table
- Add notification tracking to `post_conversions` table
- Create indexes for performance
- Ensure all operations use transactions

### Service Layer
- `ConversionEligibilityService` - Determine when to prompt post owners
- `ActivityConversionService` - Convert posts to events
- `PostService` - Add conversion methods
- `NotificationService` - Batched notification dispatch

### Event System
- `PostConversionPrompted` - Fired at 5/10 reaction thresholds
- `PostConvertedToEvent` - Fired when conversion completes
- `PostInvitationMigrated` - Fired when invitations migrate
- Listeners for each event

### API Layer
- `GET /api/posts/{id}/conversion/eligibility` - Check if post can be converted
- `GET /api/posts/{id}/conversion/preview` - Preview conversion data
- `POST /api/posts/{id}/conversion/convert` - Execute conversion
- `POST /api/posts/{id}/conversion/dismiss-prompt` - Dismiss prompt
- `GET /api/posts/{id}/interested-users/count` - Get count (privacy-safe)

---

## Critical Requirements

### 1. Transaction Safety
**ALL** database operations must be wrapped in transactions:
```php
DB::transaction(function () use ($post, $eventData) {
    $activity = Activity::create(...);
    PostConversion::create(...);
    $post->update(['status' => 'converted']);
});
```

### 2. Idempotency
Prevent duplicate prompts using timestamps:
```php
if ($post->conversion_prompted_at && !$post->shouldReprompt()) {
    return ['should_prompt' => false];
}
```

### 3. Batched Notifications
Max 10 users per batch, 5-minute delays:
```php
$batches = array_chunk($userIds, 10);
foreach ($batches as $index => $batch) {
    SendConversionNotificationBatch::dispatch($activity, $post, $batch)
        ->delay(now()->addMinutes($index * 5));
}
```

### 4. Privacy
Never expose user names/avatars, only counts:
```php
// ✅ Good
return ['interested_users_count' => 12];

// ❌ Bad
return ['interested_users' => $users->pluck('name')];
```

### 5. Re-Prompt Logic
- Dismissal expires after 7 days
- Max 3 dismissals per post
- Re-prompt if reactions double (5 → 10)

---

## Integration with Agent B (Frontend)

### Events You Dispatch
Agent B will listen for these events:
- `PostConversionPrompted` - Triggers notification UI
- `PostConvertedToEvent` - Triggers redirect to event page
- `PostInvitationMigrated` - Updates invitation status

### API Contracts
Agent B will call these endpoints:
- All endpoints return JSON
- Success: `{ "success": true, "data": {...} }`
- Error: `{ "error": "message" }` with appropriate HTTP status

### Data Structures
Ensure your API responses match these formats (see INTEGRATION_GUIDE.md):
```json
{
    "interested_users_count": 12,
    "invited_users_count": 3,
    "suggested_capacity": 18
}
```

---

## Testing Requirements

### Unit Tests (95%+ coverage)
- Test each service method independently
- Test model scopes and helpers
- Test event dispatching
- Test job execution

### Integration Tests
- Test full conversion flow
- Test transaction rollback on failure
- Test race conditions (simultaneous reactions)
- Test batching logic

### Performance Tests
- API responses < 200ms
- 100 simultaneous reactions handled efficiently
- Batched notifications process quickly

---

## Daily Workflow

### Morning (15 min)
1. Review TASK_BOARD.md
2. Update your task status
3. Sync with Agent B on integration points
4. Identify any blockers

### During Day
1. Work on current task (A1-A6)
2. Write tests as you go
3. Run tests frequently: `php artisan test --filter=Conversion`
4. Commit code frequently

### End of Day (10 min)
1. Push code to repository
2. Update TASK_BOARD.md with progress
3. Note any issues for tomorrow

---

## Commands You'll Use

```bash
# Create migrations
php artisan make:migration add_conversion_tracking_to_posts_table --no-interaction

# Create services
php artisan make:class Services/ConversionEligibilityService --no-interaction

# Create events
php artisan make:event PostConversionPrompted --no-interaction

# Create listeners
php artisan make:listener SendConversionPromptNotification --event=PostConversionPrompted --no-interaction

# Create jobs
php artisan make:job SendConversionNotificationBatch --no-interaction

# Create controllers
php artisan make:controller Api/PostConversionController --no-interaction

# Create tests
php artisan make:test --pest Services/ConversionEligibilityServiceTest --no-interaction

# Run migrations
php artisan migrate

# Run tests
php artisan test --filter=Conversion

# Format code
vendor/bin/pint --dirty
```

---

## Success Criteria

By the end of your work, you should have:

- ✅ 3 database migrations with rollback support
- ✅ 3 service classes with business logic
- ✅ 3 events + 3 listeners
- ✅ 1 queued job for batched notifications
- ✅ 1 API controller with 5 endpoints
- ✅ 95%+ test coverage
- ✅ All tests passing
- ✅ API documented and ready for Agent B

---

## Troubleshooting

### "Migration fails"
- Check database connection
- Verify column names don't conflict
- Test rollback: `php artisan migrate:rollback`

### "Tests failing"
- Run `php artisan migrate:fresh` in test environment
- Check factories are seeded
- Verify queue is running for job tests

### "Integration issues with Agent B"
- Review INTEGRATION_GUIDE.md
- Verify event names match exactly
- Check API response formats
- Communicate immediately if contracts change

---

## Your First Steps (Right Now)

```bash
# 1. Read your task file
cat context-engine/tasks/post-to-event-flow/AGENT_A_TASKS.md

# 2. Create first migration
php artisan make:migration add_conversion_tracking_to_posts_table --no-interaction

# 3. Open the migration file and implement the schema changes from A1

# 4. Test the migration
php artisan migrate
php artisan migrate:rollback
php artisan migrate

# 5. Move to next step in A1 (Post model updates)
```

---

## Communication

### Daily Sync with Agent B
- Share API endpoint changes immediately
- Discuss event structure changes
- Coordinate on integration testing
- Report blockers early

### When to Ask for Help
- API contract unclear
- Event structure needs to change
- Database schema conflicts
- Integration test failures

---

## Resources

- **Your Tasks**: `AGENT_A_TASKS.md` (1,020 lines of detailed instructions)
- **Integration**: `INTEGRATION_GUIDE.md` (how you connect with Agent B)
- **Testing**: `TESTING_STRATEGY.md` (comprehensive test plan)
- **Progress**: `TASK_BOARD.md` (update daily)

---

**Ready? Start with A1 in AGENT_A_TASKS.md. Good luck! 🚀**

