<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use MatanYadaev\EloquentSpatial\Traits\HasSpatial;

class RecurringSchedule extends Model
{
    use HasFactory;
    use HasSpatial;
    use HasUuids;

    public $incrementing = false;

    protected $keyType = 'string';

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'location_coordinates' => \MatanYadaev\EloquentSpatial\Objects\Point::class,
            'days_of_week' => 'array',
            'start_time' => 'datetime:H:i:s',
            'end_time' => 'datetime:H:i:s',
            'last_generated_until' => 'date',
            'is_active' => 'boolean',
            'paused_at' => 'datetime',
        ];
    }

    // ==================
    // Relationships
    // ==================

    public function group(): BelongsTo
    {
        return $this->belongsTo(Group::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function activities(): HasMany
    {
        return $this->hasMany(Activity::class);
    }

    public function upcomingActivities(): HasMany
    {
        return $this->activities()
            ->where('start_time', '>', now())
            ->orderBy('start_time', 'asc');
    }

    // ==================
    // Accessors
    // ==================

    /**
     * Get a human-readable schedule description.
     */
    public function getScheduleDescriptionAttribute(): string
    {
        $days = $this->days_of_week ?? [];

        if (empty($days)) {
            return 'No days selected';
        }

        // Capitalize and format days
        $formattedDays = array_map(fn ($day) => ucfirst($day), $days);

        if (count($formattedDays) === 7) {
            return 'Every day';
        }

        if (count($formattedDays) === 1) {
            return "Every {$formattedDays[0]}";
        }

        $lastDay = array_pop($formattedDays);

        return implode(', ', $formattedDays).' & '.$lastDay;
    }

    /**
     * Get formatted time range.
     */
    public function getTimeRangeAttribute(): string
    {
        $start = $this->start_time?->format('g:i A') ?? '';
        $end = $this->end_time?->format('g:i A') ?? '';

        if ($end) {
            return "{$start} - {$end}";
        }

        return $start;
    }

    // ==================
    // Scopes
    // ==================

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeForGroup($query, $groupId)
    {
        return $query->where('group_id', $groupId);
    }

    // ==================
    // Methods
    // ==================

    /**
     * Pause the schedule (stop generating new events).
     */
    public function pause(): void
    {
        $this->update([
            'is_active' => false,
            'paused_at' => now(),
        ]);
    }

    /**
     * Resume the schedule.
     */
    public function resume(): void
    {
        $this->update([
            'is_active' => true,
            'paused_at' => null,
        ]);
    }

    /**
     * Check if the schedule needs to generate more events.
     */
    public function needsGeneration(): bool
    {
        if (! $this->is_active) {
            return false;
        }

        $targetDate = now()->addWeeks($this->generate_weeks_ahead);

        return $this->last_generated_until === null
            || $this->last_generated_until < $targetDate;
    }
}
