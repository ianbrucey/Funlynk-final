# Event Edit Protection - Service Architecture

## Service Overview

```
┌─────────────────────────────────────────────────────────────────┐
│                    ActivityEditService                          │
│  (Orchestrator - validates, classifies, executes edits)        │
├─────────────────────────────────────────────────────────────────┤
│  validateEdit(Activity, array $changes): EditValidationResult   │
│  executeEdit(Activity, array $changes, ?string $reason): void   │
│  getFieldPermissions(Activity): array                           │
│  isEditLocked(Activity): bool                                   │
└──────────────────────┬──────────────────────────────────────────┘
                       │ uses
        ┌──────────────┼──────────────┐
        ▼              ▼              ▼
┌───────────────┐ ┌──────────────┐ ┌─────────────────────┐
│ChangeDetector │ │ RefundWindow │ │ EditNotification    │
│    Service    │ │   Service    │ │     Service         │
├───────────────┤ ├──────────────┤ ├─────────────────────┤
│ classifyChange│ │ create()     │ │ notifyAttendees()   │
│ detectChanges │ │ expire()     │ │ notifyChangeAccept()│
│ isSignificant │ │ getActive()  │ │ notifyRefundIssued()│
└───────────────┘ └──────────────┘ └─────────────────────┘
                         │
                         ▼
              ┌────────────────────┐
              │   RefundService    │
              │    (existing)      │
              └────────────────────┘
```

## Service Definitions

### 1. ActivityEditService (Orchestrator)

```php
namespace App\Services;

class ActivityEditService
{
    public function __construct(
        private ChangeDetectorService $changeDetector,
        private RefundWindowService $refundWindow,
        private EditNotificationService $notifications,
    ) {}
    
    /**
     * Check if activity has edit restrictions (paid attendees exist)
     */
    public function isEditLocked(Activity $activity): bool;
    
    /**
     * Get permission level for each editable field
     * Returns: ['title' => 'refund_window', 'description' => 'always', 'price' => 'locked']
     */
    public function getFieldPermissions(Activity $activity): array;
    
    /**
     * Validate proposed changes without executing
     * Returns validation result with warnings/errors per field
     */
    public function validateEdit(Activity $activity, array $changes): EditValidationResult;
    
    /**
     * Execute the edit with all side effects
     * - Validates changes
     * - Creates audit logs
     * - Triggers refund window if needed
     * - Sends notifications
     */
    public function executeEdit(Activity $activity, array $changes, ?string $reason = null): EditResult;
    
    /**
     * Snapshot original values when first paid RSVP occurs
     */
    public function lockActivity(Activity $activity): void;
}
```

### 2. ChangeDetectorService

```php
namespace App\Services;

class ChangeDetectorService
{
    /**
     * Compare old vs new values and classify each change
     */
    public function detectChanges(Activity $activity, array $newValues): array;
    
    /**
     * Classify a single field change
     * Returns: 'cosmetic' | 'minor' | 'significant' | 'blocked'
     */
    public function classifyChange(string $field, mixed $oldValue, mixed $newValue): string;
    
    /**
     * Title change detection using Levenshtein distance
     * <30% change = minor, ≥30% = significant
     */
    public function isTitleChangeSignificant(string $old, string $new): bool;
    
    /**
     * Time change detection
     * ≤2 hours = minor, >2 hours = significant
     */
    public function isTimeChangeSignificant(Carbon $old, Carbon $new): bool;
    
    /**
     * Location change detection
     * Compares address strings, coordinates if available
     */
    public function isLocationChangeSignificant(
        ?string $oldName, ?string $newName,
        ?Point $oldCoords, ?Point $newCoords
    ): bool;
}
```

### 3. RefundWindowService

```php
namespace App\Services;

class RefundWindowService
{
    public function __construct(
        private RefundService $refundService,
    ) {}
    
    /**
     * Create a new refund window for significant changes
     */
    public function create(
        Activity $activity,
        ActivityEditLog $triggerLog,
        array $changesSummary
    ): ActivityRefundWindow;
    
    /**
     * Get active refund window for an activity (if any)
     */
    public function getActive(Activity $activity): ?ActivityRefundWindow;
    
    /**
     * Process attendee response (accept or refund)
     */
    public function processResponse(
        Rsvp $rsvp,
        ActivityRefundWindow $window,
        string $response  // 'accepted' | 'refunded'
    ): RsvpChangeResponse;
    
    /**
     * Expire old windows (called by scheduled job)
     */
    public function expireWindows(): int;
    
    /**
     * Check if RSVP has pending response for any window
     */
    public function hasPendingResponse(Rsvp $rsvp): bool;
}
```

