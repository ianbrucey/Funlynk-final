<?php

namespace App\Services;

use App\Jobs\NotifyAttendeesOfChanges;
use App\Models\Activity;
use App\Models\ActivityEditLog;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class ActivityEditService
{
    public function __construct(
        private ChangeDetectorService $changeDetector,
        private RefundWindowService $refundWindowService,
    ) {}

    /**
     * Validate and process activity edits
     * Returns array with 'success', 'blocked_changes', 'significant_changes', 'window'
     */
    public function processEdit(Activity $activity, array $newValues, User $editor): array
    {
        // Detect all changes
        $changes = $this->changeDetector->detectChanges($activity, $newValues);

        if (empty($changes)) {
            return [
                'success' => true,
                'message' => 'No changes detected.',
                'changes' => [],
                'blocked_changes' => [],
                'significant_changes' => [],
                'window' => null,
            ];
        }

        // Check for blocked changes (only if activity has paid attendees)
        $blockedChanges = [];
        if ($activity->isEditLocked()) {
            $blockedChanges = $this->changeDetector->getBlockedChanges($changes);
            
            if (!empty($blockedChanges)) {
                return [
                    'success' => false,
                    'message' => 'Some changes are not allowed after receiving payments.',
                    'changes' => $changes,
                    'blocked_changes' => $blockedChanges,
                    'significant_changes' => [],
                    'window' => null,
                ];
            }
        }

        // Process the edit
        $result = DB::transaction(function () use ($activity, $newValues, $changes, $editor) {
            $significantChanges = $this->changeDetector->getSignificantChanges($changes);
            $window = null;

            // Log all changes
            foreach ($changes as $change) {
                $log = $this->logChange($activity, $change, $editor);

                // If significant and activity has paid attendees, trigger refund window
                if ($change['category'] === 'significant' && $activity->isEditLocked()) {
                    $log->update(['triggered_refund_window' => true]);
                }
            }

            // Create refund window if there are significant changes and paid attendees
            if (!empty($significantChanges) && $activity->isEditLocked()) {
                $triggerLog = ActivityEditLog::where('activity_id', $activity->id)
                    ->where('change_category', 'significant')
                    ->latest()
                    ->first();

                $window = $this->refundWindowService->create(
                    $activity,
                    $triggerLog,
                    $this->formatChangesSummary($significantChanges)
                );
            }

            // Apply the changes
            $activity->update($newValues);

            return [
                'success' => true,
                'message' => $this->buildSuccessMessage($changes, $window),
                'changes' => $changes,
                'blocked_changes' => [],
                'significant_changes' => $significantChanges,
                'window' => $window,
            ];
        });

        // Dispatch notification job after transaction commits
        if ($result['window']) {
            NotifyAttendeesOfChanges::dispatch($activity, $result['window']);
        }

        return $result;
    }

    /**
     * Log a single change
     */
    protected function logChange(Activity $activity, array $change, User $editor): ActivityEditLog
    {
        return ActivityEditLog::create([
            'activity_id' => $activity->id,
            'editor_id' => $editor->id,
            'field_name' => $change['field'],
            'old_value' => $this->serializeValue($change['old_value']),
            'new_value' => $this->serializeValue($change['new_value']),
            'change_category' => $change['category'],
            'triggered_refund_window' => false,
            'paid_attendee_count' => $activity->getPaidAttendeeCount(),
        ]);
    }

    /**
     * Serialize value for storage
     */
    protected function serializeValue(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        if ($value instanceof \DateTimeInterface) {
            return $value->format('Y-m-d H:i:s');
        }

        if (is_object($value)) {
            return json_encode($value);
        }

        if (is_array($value)) {
            return json_encode($value);
        }

        return (string) $value;
    }

    /**
     * Format changes for display in refund window
     */
    protected function formatChangesSummary(array $changes): array
    {
        return array_map(function ($change) {
            return [
                'field' => $change['field'],
                'old_display' => $this->formatDisplayValue($change['field'], $change['old_value']),
                'new_display' => $this->formatDisplayValue($change['field'], $change['new_value']),
            ];
        }, $changes);
    }

    /**
     * Format value for human-readable display
     */
    protected function formatDisplayValue(string $field, mixed $value): string
    {
        if ($value === null) {
            return 'Not set';
        }

        if ($value instanceof \DateTimeInterface) {
            return $value->format('F j, Y \a\t g:i A');
        }

        return (string) $value;
    }

    protected function buildSuccessMessage(array $changes, $window): string
    {
        if ($window) {
            return 'Event updated. Attendees have been notified and have 72 hours to request a refund.';
        }

        return 'Event updated successfully.';
    }
}
