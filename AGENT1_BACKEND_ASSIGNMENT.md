# Agent 1 Assignment: Groups Feature Backend (E05/F03)

## Overview

You are responsible for fixing backend tests and ensuring the Groups feature backend is production-ready. Agent 2 will handle frontend in parallel.

**Estimated Duration**: 8-12 hours
**Current Status**: Phase 1 Complete ✅, Phase 2a-2b In Progress

---

## Your Responsibilities

### Phase 2a: Backend Service Tests (4-6 hours)
Fix failing service tests to validate business logic

### Phase 2b: Backend Policy Tests (2-3 hours)
Fix authorization policy tests

### Phase 3: Integration & Bug Fixes (2-3 hours)
Ensure backend works end-to-end

---

## Phase 2a: Backend Service Tests

### Tests to Fix

1. **GroupServiceTest** (16 tests, ~8 failing)
   - Location: `tests/Feature/Feature/GroupServiceTest.php`
   - Issues: Event listeners causing transaction failures, Group::admins() relationship
   - Key Tests: create, update, delete, addMember, removeMember, updateRole

2. **GroupChatServiceTest** (6 tests, all failing)
   - Location: `tests/Feature/Feature/GroupChatServiceTest.php`
   - Issues: Transaction failures from event listeners
   - Key Tests: getOrCreateChat, sendMessage, addParticipant, removeParticipant

3. **GroupContentServiceTest** (5 tests, all failing)
   - Location: `tests/Feature/Feature/GroupContentServiceTest.php`
   - Issues: Transaction failures from event listeners
   - Key Tests: createPost, createEvent, getTimeline, getPosts, getEvents

### Root Cause Analysis

**Primary Issue**: Event listeners dispatching during transactions
- SendGroupNotification listener tries to access $group->admins
- Group::admins() is not a relationship (returns Collection)
- Causes transaction to abort

**Solution Options**:
1. Fix Group::admins() to return a proper relationship
2. Disable event listeners in tests with Event::fake()
3. Refactor listeners to not access relationships during transaction

### Recommended Approach

1. Add `Event::fake()` to all test beforeEach() blocks
2. Fix Group::admins() to return a proper relationship
3. Test with events enabled to ensure listeners work

### Sub-Agent Prompt (Optional)

If delegating to sub-agent:
```
Read tests/Feature/Feature/GroupServiceTest.php
Read app/Services/GroupService.php
Read app/Models/Group.php
Read app/Listeners/SendGroupNotification.php

Fix the following issues in GroupServiceTest:
1. Add Event::fake() to beforeEach() to disable listeners
2. Fix Group::admins() relationship (currently returns Collection, should return relationship)
3. Ensure all 16 tests pass

Output: Fixed test file and any model/listener changes needed
```

---

## Phase 2b: Backend Policy Tests

### Tests to Fix

1. **GroupPolicyTest** (20 tests, ~15 failing)
   - Location: `tests/Feature/Feature/GroupPolicyTest.php`
   - Issue: GroupPolicy::create() doesn't accept null User
   - Fix: Change `create(User $user)` to `create(?User $user)`

2. **PostPolicyTest** (18 tests, ~9 failing)
   - Location: `tests/Feature/Feature/PostPolicyTest.php`
   - Issue: PostPolicy::create() doesn't accept null User
   - Fix: Change `create(User $user)` to `create(?User $user)`

### Quick Fixes

**GroupPolicy.php** (line ~35):
```php
// Before
public function create(User $user): bool

// After
public function create(?User $user): bool
{
    return $user !== null;
}
```

**PostPolicy.php** (line ~36):
```php
// Before
public function create(User $user): bool

// After
public function create(?User $user): bool
{
    return $user !== null;
}
```

### Verification

After fixes, run:
```bash
php artisan test tests/Feature/Feature/GroupPolicyTest.php
php artisan test tests/Feature/Feature/PostPolicyTest.php
```

---

## Phase 3: Integration & Bug Fixes

### Tasks

1. **Run full backend test suite**:
   ```bash
   php artisan test tests/Feature/Feature/
   ```

2. **Fix any remaining issues**:
   - Document errors in dev-logs
   - Prioritize by impact
   - Fix systematically

3. **Verify all tests pass**:
   - Target: 100% of backend tests passing
   - Minimum: 95% passing (document exceptions)

4. **Document architectural decisions**:
   - How events are handled in tests
   - How relationships are structured
   - Any deviations from standard patterns

### Success Criteria

✅ All service tests passing
✅ All policy tests passing
✅ All integration tests passing
✅ No transaction failures
✅ Backend ready for frontend integration

---

## Key Files

**Models**:
- `app/Models/Group.php` - Group model with relationships
- `app/Models/GroupMember.php` - Pivot model for group membership

**Services**:
- `app/Services/GroupService.php` - Main group service
- `app/Services/GroupChatService.php` - Chat service
- `app/Services/GroupContentService.php` - Content service

**Policies**:
- `app/Policies/GroupPolicy.php` - Group authorization
- `app/Policies/PostPolicy.php` - Post authorization

**Listeners**:
- `app/Listeners/SendGroupNotification.php` - Event listener

**Tests**:
- `tests/Feature/Feature/GroupServiceTest.php`
- `tests/Feature/Feature/GroupChatServiceTest.php`
- `tests/Feature/Feature/GroupContentServiceTest.php`
- `tests/Feature/Feature/GroupPolicyTest.php`
- `tests/Feature/Feature/PostPolicyTest.php`

---

## Execution Plan

### Step 1: Fix Event Listener Issues (1-2 hours)
1. Add Event::fake() to all test beforeEach() blocks
2. Fix Group::admins() relationship
3. Run GroupServiceTest - target 14/16 passing

### Step 2: Fix Policy Tests (1-2 hours)
1. Update GroupPolicy::create() to accept nullable User
2. Update PostPolicy::create() to accept nullable User
3. Run both policy tests - target 100% passing

### Step 3: Full Backend Test Run (1 hour)
1. Run full test suite
2. Document any remaining failures
3. Fix critical issues

### Step 4: Documentation (30 min)
1. Update dev-logs with final status
2. Document any architectural decisions
3. Prepare handoff to Agent 2

---

## Communication

- Check `dev-logs/2025-12-01-17.md` for current status
- Update dev-logs after each phase
- Coordinate with Agent 2 on integration points
- Escalate blockers immediately

---

## Success Criteria

✅ Phase 1: All ActivityPolicyTest passing (COMPLETE)
⏳ Phase 2a: All service tests passing
⏳ Phase 2b: All policy tests passing
⏳ Phase 3: Full backend test suite passing
⏳ Ready for frontend integration

