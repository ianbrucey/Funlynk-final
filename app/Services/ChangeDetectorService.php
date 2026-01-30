<?php

namespace App\Services;

use App\Models\Activity;
use Carbon\Carbon;
use MatanYadaev\EloquentSpatial\Objects\Point;

class ChangeDetectorService
{
    // Fields that can always be edited without restrictions
    public const ALWAYS_EDITABLE = [
        'description',
        'cover_image',
        'images',
        'additional_notes',
        'what_to_bring',
    ];

    // Fields that trigger notifications but not refund windows
    public const NOTIFY_ONLY = [
        'end_time',
        'max_attendees',
    ];

    // Fields that can trigger refund windows for significant changes
    public const REFUND_WINDOW_FIELDS = [
        'title',
        'start_time',
        'location_name',
        'location_address',
        'location_coordinates',
    ];

    // Fields that are locked after first payment
    public const LOCKED_FIELDS = [
        'price_cents',
        'payment_type',
    ];

    // Thresholds
    public const TITLE_CHANGE_THRESHOLD = 0.30; // 30% difference = significant
    public const TIME_CHANGE_HOURS_THRESHOLD = 2; // >2 hours = significant
    public const COSMETIC_TITLE_THRESHOLD = 0.05; // <5% = cosmetic (no notification)

    /**
     * Detect and classify all changes between current activity and new values
     */
    public function detectChanges(Activity $activity, array $newValues): array
    {
        $changes = [];

        foreach ($newValues as $field => $newValue) {
            $oldValue = $activity->getOriginal($field) ?? $activity->getAttribute($field);

            // Skip if no actual change
            if ($this->valuesAreEqual($field, $oldValue, $newValue)) {
                continue;
            }

            $changes[] = [
                'field' => $field,
                'old_value' => $oldValue,
                'new_value' => $newValue,
                'category' => $this->classifyChange($field, $oldValue, $newValue, $activity),
            ];
        }

        return $changes;
    }

    /**
     * Classify a single field change
     */
    public function classifyChange(string $field, mixed $oldValue, mixed $newValue, ?Activity $activity = null): string
    {
        // Always editable fields
        if (in_array($field, self::ALWAYS_EDITABLE)) {
            return 'cosmetic';
        }

        // Locked fields (check if edit is allowed at all)
        if (in_array($field, self::LOCKED_FIELDS)) {
            return $this->classifyLockedFieldChange($field, $oldValue, $newValue, $activity);
        }

        // Notify-only fields
        if (in_array($field, self::NOTIFY_ONLY)) {
            return 'minor';
        }

        // Refund window fields - check significance
        if (in_array($field, self::REFUND_WINDOW_FIELDS)) {
            return $this->classifyRefundWindowFieldChange($field, $oldValue, $newValue);
        }

        // Unknown field - default to cosmetic
        return 'cosmetic';
    }

    /**
     * Check if values are equal (handles different types)
     */
    protected function valuesAreEqual(string $field, mixed $oldValue, mixed $newValue): bool
    {
        // Handle datetime comparison
        if (in_array($field, ['start_time', 'end_time'])) {
            $oldCarbon = $oldValue instanceof Carbon ? $oldValue : Carbon::parse($oldValue);
            $newCarbon = $newValue instanceof Carbon ? $newValue : Carbon::parse($newValue);
            return $oldCarbon->equalTo($newCarbon);
        }

        // Handle Point comparison
        if ($oldValue instanceof Point || $newValue instanceof Point) {
            return $this->pointsAreEqual($oldValue, $newValue);
        }

        // Standard comparison
        return $oldValue === $newValue;
    }

    protected function pointsAreEqual(?Point $old, ?Point $new): bool
    {
        if ($old === null && $new === null) return true;
        if ($old === null || $new === null) return false;
        
        return abs($old->latitude - $new->latitude) < 0.0001 
            && abs($old->longitude - $new->longitude) < 0.0001;
    }

    protected function classifyLockedFieldChange(string $field, mixed $oldValue, mixed $newValue, ?Activity $activity): string
    {
        // Price changes
        if ($field === 'price_cents') {
            // Increase is blocked
            if ($newValue > $oldValue) {
                return 'blocked';
            }
            // Decrease is allowed (minor)
            return 'minor';
        }

        // Paid → Free is blocked (online/at_door to free)
        if ($field === 'payment_type' && in_array($oldValue, ['online', 'at_door']) && $newValue === 'free') {
            return 'blocked';
        }

        return 'minor';
    }

    protected function classifyRefundWindowFieldChange(string $field, mixed $oldValue, mixed $newValue): string
    {
        return match ($field) {
            'title' => $this->classifyTitleChange($oldValue, $newValue),
            'start_time' => $this->classifyTimeChange($oldValue, $newValue),
            'location_name', 'location_address' => $this->classifyLocationChange($oldValue, $newValue),
            'location_coordinates' => $this->classifyCoordinateChange($oldValue, $newValue),
            default => 'minor',
        };
    }

    /**
     * Classify title change based on similarity
     * <5% change = cosmetic
     * <30% change = minor
     * >=30% change = significant
     */
    protected function classifyTitleChange(?string $oldTitle, ?string $newTitle): string
    {
        if (empty($oldTitle) || empty($newTitle)) {
            return 'minor';
        }

        $similarity = $this->calculateStringSimilarity($oldTitle, $newTitle);

        if ($similarity >= (1 - self::COSMETIC_TITLE_THRESHOLD)) {
            return 'cosmetic'; // <5% change
        }

        if ($similarity >= (1 - self::TITLE_CHANGE_THRESHOLD)) {
            return 'minor'; // 5-30% change
        }

        return 'significant'; // >30% change
    }

    /**
     * Calculate similarity between two strings (0 to 1)
     */
    public function calculateStringSimilarity(string $str1, string $str2): float
    {
        $str1 = strtolower(trim($str1));
        $str2 = strtolower(trim($str2));

        if ($str1 === $str2) {
            return 1.0;
        }

        $maxLen = max(strlen($str1), strlen($str2));
        if ($maxLen === 0) {
            return 1.0;
        }

        $distance = levenshtein($str1, $str2);
        return 1 - ($distance / $maxLen);
    }

    /**
     * Classify time change based on difference
     * <=2 hours = minor
     * >2 hours = significant
     */
    protected function classifyTimeChange(mixed $oldTime, mixed $newTime): string
    {
        if (empty($oldTime) || empty($newTime)) {
            return 'minor';
        }

        $oldCarbon = $oldTime instanceof Carbon ? $oldTime : Carbon::parse($oldTime);
        $newCarbon = $newTime instanceof Carbon ? $newTime : Carbon::parse($newTime);

        $hoursDiff = abs($oldCarbon->diffInHours($newCarbon));

        if ($hoursDiff <= self::TIME_CHANGE_HOURS_THRESHOLD) {
            return 'minor';
        }

        return 'significant';
    }

    /**
     * Classify location string change
     */
    protected function classifyLocationChange(?string $oldLocation, ?string $newLocation): string
    {
        // Adding location to empty = minor
        if (empty($oldLocation) && !empty($newLocation)) {
            return 'minor';
        }

        // Removing location = significant
        if (!empty($oldLocation) && empty($newLocation)) {
            return 'significant';
        }

        // Compare strings
        $similarity = $this->calculateStringSimilarity($oldLocation ?? '', $newLocation ?? '');

        // If very similar (same address, minor formatting) = minor
        if ($similarity >= 0.8) {
            return 'minor';
        }

        return 'significant';
    }

    /**
     * Classify coordinate change
     */
    protected function classifyCoordinateChange(mixed $oldCoords, mixed $newCoords): string
    {
        // If no coordinates involved, minor change
        if (!$oldCoords instanceof Point && !$newCoords instanceof Point) {
            return 'minor';
        }

        // Adding coordinates = minor
        if (!$oldCoords instanceof Point && $newCoords instanceof Point) {
            return 'minor';
        }

        // Removing coordinates = significant
        if ($oldCoords instanceof Point && !$newCoords instanceof Point) {
            return 'significant';
        }

        // Both are Points - check distance
        // Using approximate conversion: 1 degree ≈ 111km
        $latDiff = abs($oldCoords->latitude - $newCoords->latitude);
        $lonDiff = abs($oldCoords->longitude - $newCoords->longitude);
        $approxKm = sqrt(pow($latDiff * 111, 2) + pow($lonDiff * 111, 2));

        // If moved more than 5km, significant
        if ($approxKm > 5) {
            return 'significant';
        }

        return 'minor';
    }

    /**
     * Check if a specific change type is significant
     */
    public function isSignificant(string $category): bool
    {
        return $category === 'significant';
    }

    /**
     * Check if any changes are significant
     */
    public function hasSignificantChanges(array $changes): bool
    {
        foreach ($changes as $change) {
            if ($change['category'] === 'significant') {
                return true;
            }
        }
        return false;
    }

    /**
     * Check if any changes are blocked
     */
    public function hasBlockedChanges(array $changes): bool
    {
        foreach ($changes as $change) {
            if ($change['category'] === 'blocked') {
                return true;
            }
        }
        return false;
    }

    /**
     * Get only significant changes from a list
     */
    public function getSignificantChanges(array $changes): array
    {
        return array_filter($changes, fn($c) => $c['category'] === 'significant');
    }

    /**
     * Get only blocked changes from a list
     */
    public function getBlockedChanges(array $changes): array
    {
        return array_filter($changes, fn($c) => $c['category'] === 'blocked');
    }
}
