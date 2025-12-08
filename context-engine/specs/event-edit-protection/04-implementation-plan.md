# Event Edit Protection - Implementation Plan

## Phase Overview

| Phase | Focus | Estimate | Dependencies |
|-------|-------|----------|--------------|
| **Phase 1** | Database & Models | 2-3 hrs | None |
| **Phase 2** | Core Services | 4-5 hrs | Phase 1 |
| **Phase 3** | Edit Flow Integration | 3-4 hrs | Phase 2 |
| **Phase 4** | Attendee UI & Notifications | 3-4 hrs | Phase 2 |
| **Phase 5** | Background Jobs & Cleanup | 1-2 hrs | Phase 2 |
| **Phase 6** | Testing | 3-4 hrs | All |

**Total Estimate: 16-22 hours**

---

## Phase 1: Database & Models (2-3 hrs)

### T1.1: Create Migrations
```bash
php artisan make:migration create_activity_edit_logs_table
php artisan make:migration create_activity_refund_windows_table
php artisan make:migration create_rsvp_change_responses_table
php artisan make:migration add_edit_protection_to_activities_table
```

**Files:**
- `database/migrations/xxxx_create_activity_edit_logs_table.php`
- `database/migrations/xxxx_create_activity_refund_windows_table.php`
- `database/migrations/xxxx_create_rsvp_change_responses_table.php`
- `database/migrations/xxxx_add_edit_protection_to_activities_table.php`

### T1.2: Create Models
```bash
php artisan make:model ActivityEditLog
php artisan make:model ActivityRefundWindow
php artisan make:model RsvpChangeResponse
```

**Files:**
- `app/Models/ActivityEditLog.php`
- `app/Models/ActivityRefundWindow.php`
- `app/Models/RsvpChangeResponse.php`

### T1.3: Update Existing Models
- `app/Models/Activity.php` - Add relationships, `isEditLocked()` helper
- `app/Models/Rsvp.php` - Add `changeResponses()` relationship

### T1.4: Create Factories
```bash
php artisan make:factory ActivityEditLogFactory
php artisan make:factory ActivityRefundWindowFactory
php artisan make:factory RsvpChangeResponseFactory
```

**Acceptance Criteria:**
- [ ] All migrations run without errors
- [ ] Models have proper relationships defined
- [ ] Factories can generate valid test data

---

## Phase 2: Core Services (4-5 hrs)

### T2.1: ChangeDetectorService
**File:** `app/Services/ChangeDetectorService.php`

Methods:
- `detectChanges(Activity, array): array`
- `classifyChange(string, mixed, mixed): string`
- `isTitleChangeSignificant(string, string): bool`
- `isTimeChangeSignificant(Carbon, Carbon): bool`
- `isLocationChangeSignificant(...): bool`

### T2.2: RefundWindowService
**File:** `app/Services/RefundWindowService.php`

Methods:
- `create(Activity, ActivityEditLog, array): ActivityRefundWindow`
- `getActive(Activity): ?ActivityRefundWindow`
- `processResponse(Rsvp, ActivityRefundWindow, string): RsvpChangeResponse`
- `expireWindows(): int`
- `hasPendingResponse(Rsvp): bool`

### T2.3: EditNotificationService
**File:** `app/Services/EditNotificationService.php`

Methods:
- `notifyAttendeesOfChange(Activity, ActivityRefundWindow): void`
- `notifyChangeAccepted(Rsvp): void`
- `notifyRefundIssued(Rsvp): void`

### T2.4: ActivityEditService (Orchestrator)
**File:** `app/Services/ActivityEditService.php`

Methods:
- `isEditLocked(Activity): bool`
- `getFieldPermissions(Activity): array`
- `validateEdit(Activity, array): EditValidationResult`
- `executeEdit(Activity, array, ?string): EditResult`
- `lockActivity(Activity): void`

### T2.5: Value Objects
**Files:**
- `app/ValueObjects/EditValidationResult.php`
- `app/ValueObjects/EditResult.php`

**Acceptance Criteria:**
- [ ] ChangeDetector correctly classifies all field types
- [ ] Title similarity detection works (test with various examples)
- [ ] Time threshold (2hr) correctly identified
- [ ] RefundWindow creates records and links to RSVPs
- [ ] Services are properly dependency-injected

---

## Phase 3: Edit Flow Integration (3-4 hrs)

### T3.1: Modify EditActivity Livewire Component
**File:** `app/Livewire/Activities/EditActivity.php`

Changes:
- Add `isEditLocked` computed property
- Add `fieldPermissions` computed property
- Add `validateChangesBeforeSubmit()` method
- Add `showWarningModal` state
- Modify `save()` to use ActivityEditService

### T3.2: Update Edit View
**File:** `resources/views/livewire/activities/edit-activity.blade.php`

Changes:
- Add warning modal (pre-edit)
- Add field permission indicators
- Add change preview before submit
- Add reason input for significant changes

### T3.3: Lock Activity on First Payment
**File:** `app/Services/RsvpService.php` (or wherever RSVP creation happens)

Changes:
- After successful paid RSVP, call `ActivityEditService::lockActivity()`

**Acceptance Criteria:**
- [ ] Warning modal shows for paid events
- [ ] Field indicators display correctly
- [ ] Blocked fields (price increase) are enforced
- [ ] Significant changes show confirmation dialog
- [ ] Edit logs are created for all changes
- [ ] Refund window is created for significant changes

---

## Phase 4: Attendee UI & Notifications (3-4 hrs)

### T4.1: Create Notification Type
**File:** `app/Notifications/EventChangedNotification.php`

### T4.2: Refund Window Banner Component
**File:** `app/Livewire/Activities/RefundWindowBanner.php`
**View:** `resources/views/livewire/activities/refund-window-banner.blade.php`

### T4.3: Event Audit Trail Component
**File:** `app/Livewire/Activities/EventAuditTrail.php`
**View:** `resources/views/livewire/activities/event-audit-trail.blade.php`

### T4.4: Integrate into ActivityDetail
**File:** `resources/views/livewire/activities/activity-detail.blade.php`

Changes:
- Include RefundWindowBanner component
- Add "View History" link if changes exist
- Show audit trail modal

### T4.5: Process Refund Response Actions
**File:** `app/Livewire/Activities/RefundWindowBanner.php`

Methods:
- `acceptChanges()` - Mark as accepted
- `requestRefund()` - Trigger refund + cancel RSVP

**Acceptance Criteria:**
- [ ] Attendees receive notification when event changes
- [ ] Banner shows on event page with countdown
- [ ] Accept/Refund buttons work correctly
- [ ] Audit trail shows all past changes
- [ ] Refund processes through existing RefundService

---

## Phase 5: Background Jobs (1-2 hrs)

### T5.1: Expire Refund Windows Job
**File:** `app/Jobs/ExpireRefundWindowsJob.php`

- Run hourly via scheduler
- Calls `RefundWindowService::expireWindows()`
- Marks expired windows, implicitly accepts pending responses

### T5.2: Schedule the Job
**File:** `routes/console.php` or `app/Console/Kernel.php`

```php
Schedule::job(new ExpireRefundWindowsJob)->hourly();
```

**Acceptance Criteria:**
- [ ] Job runs on schedule
- [ ] Expired windows are marked correctly
- [ ] No orphaned pending responses

---

## Phase 6: Testing (3-4 hrs)

### T6.1: Unit Tests
- `tests/Unit/Services/ChangeDetectorServiceTest.php`
- `tests/Unit/Services/RefundWindowServiceTest.php`
- `tests/Unit/Services/ActivityEditServiceTest.php`

### T6.2: Feature Tests
- `tests/Feature/ActivityEditProtectionTest.php`
  - Test edit locked after payment
  - Test field permission enforcement
  - Test refund window creation
  - Test attendee response flow
  - Test audit trail

### T6.3: Edge Cases to Test
- Edit before any RSVPs (should be unrestricted)
- Multiple significant edits (window extension)
- Attendee requests refund after window expires
- Host tries to increase price (should be blocked)
- Free event edits (should be unrestricted for v1)

**Acceptance Criteria:**
- [ ] All unit tests pass
- [ ] All feature tests pass
- [ ] Edge cases handled gracefully

