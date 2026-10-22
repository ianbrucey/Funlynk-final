<?php

namespace App\Services;

use App\Models\Activity;
use App\Models\Group;
use App\Models\RecurringSchedule;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class RecurringScheduleService
{
    /**
     * Create a new recurring schedule and generate initial events.
     */
    public function createSchedule(Group $group, User $creator, array $data): RecurringSchedule
    {
        return DB::transaction(function () use ($group, $creator, $data) {
            $schedule = RecurringSchedule::create([
                'group_id' => $group->id,
                'created_by' => $creator->id,
                'title' => $data['title'],
                'description' => $data['description'] ?? null,
                'location_name' => $data['location_name'] ?? null,
                'location_coordinates' => $data['location_coordinates'] ?? null,
                'frequency' => $data['frequency'] ?? 'weekly',
                'interval' => $data['interval'] ?? 1,
                'days_of_week' => $data['days_of_week'] ?? [],
                'day_of_month' => $data['day_of_month'] ?? null,
                'start_time' => $data['start_time'],
                'end_time' => $data['end_time'] ?? null,
                'duration_minutes' => $data['duration_minutes'] ?? null,
                'generate_weeks_ahead' => $data['generate_weeks_ahead'] ?? 4,
                'is_active' => true,
            ]);

            // Generate initial events
            $this->generateEventsFromSchedule($schedule);

            return $schedule->fresh();
        });
    }

    /**
     * Generate events from a recurring schedule.
     */
    public function generateEventsFromSchedule(RecurringSchedule $schedule): Collection
    {
        if (! $schedule->is_active) {
            return collect();
        }

        $startDate = $schedule->last_generated_until
            ? Carbon::parse($schedule->last_generated_until)->addDay()
            : now()->startOfDay();

        $endDate = now()->addWeeks($schedule->generate_weeks_ahead)->endOfDay();

        // Don't generate if we're already up to date
        if ($startDate > $endDate) {
            return collect();
        }

        $dates = $this->calculateOccurrences($schedule, $startDate, $endDate);
        $createdActivities = collect();

        Log::info('RecurringScheduleService: Generating events', [
            'schedule_id' => $schedule->id,
            'start_date' => $startDate->toDateString(),
            'end_date' => $endDate->toDateString(),
            'occurrences' => count($dates),
        ]);

        foreach ($dates as $date) {
            // Check if event already exists for this date
            $exists = Activity::where('recurring_schedule_id', $schedule->id)
                ->where('recurrence_date', $date->toDateString())
                ->exists();

            if ($exists) {
                continue;
            }

            $activity = $this->createActivityFromSchedule($schedule, $date);
            $createdActivities->push($activity);
        }

        // Update last generated date
        $schedule->update(['last_generated_until' => $endDate->toDateString()]);

        return $createdActivities;
    }

    /**
     * Calculate all occurrence dates for a schedule within a date range.
     */
    protected function calculateOccurrences(RecurringSchedule $schedule, Carbon $start, Carbon $end): array
    {
        $dates = [];
        $current = $start->copy();

        while ($current <= $end) {
            if ($this->isOccurrenceDate($schedule, $current)) {
                $dates[] = $current->copy();
            }
            $current->addDay();
        }

        return $dates;
    }

    /**
     * Check if a given date is an occurrence date for the schedule.
     */
    protected function isOccurrenceDate(RecurringSchedule $schedule, Carbon $date): bool
    {
        $interval = max(1, (int) $schedule->interval);
        $anchor = Carbon::parse($schedule->created_at ?? now())->startOfDay();
        $day = $date->copy()->startOfDay();

        switch ($schedule->frequency) {
            case 'daily':
                return (int) $anchor->diffInDays($day, false) % $interval === 0;

            case 'weekly':
                $weeksApart = (int) $anchor->copy()->startOfWeek()->diffInWeeks($day->copy()->startOfWeek(), false);
                if ($weeksApart % $interval !== 0) {
                    return false;
                }
                $dayName = strtolower($date->format('l'));

                return in_array($dayName, $schedule->days_of_week ?? []);

            case 'monthly':
                if (! $schedule->day_of_month) {
                    return false;
                }
                $monthsApart = ($day->year - $anchor->year) * 12 + $day->month - $anchor->month;
                if ($monthsApart % $interval !== 0) {
                    return false;
                }

                return $date->day === min((int) $schedule->day_of_month, $date->daysInMonth);

            default:
                return false;
        }
    }

    /**
     * Create a single activity from a schedule for a specific date.
     */
    protected function createActivityFromSchedule(RecurringSchedule $schedule, Carbon $date): Activity
    {
        return Activity::create([
            'group_id' => $schedule->group_id,
            'host_id' => $schedule->created_by,
            'recurring_schedule_id' => $schedule->id,
            'recurrence_date' => $date->toDateString(),
            'activity_type' => 'group_event',
            'status' => 'published',
            'is_public' => false, // Group events are not public by default
        ] + $this->occurrenceAttributes($schedule, $date));
    }

    /**
     * Schedule-driven fields for one occurrence on the given date.
     *
     * @return array<string, mixed>
     */
    protected function occurrenceAttributes(RecurringSchedule $schedule, Carbon $date): array
    {
        // Combine date with schedule time
        $startTime = $date->copy()->setTimeFromTimeString(
            $schedule->start_time instanceof Carbon
                ? $schedule->start_time->format('H:i:s')
                : $schedule->start_time
        );

        $endTime = null;
        if ($schedule->end_time) {
            $endTime = $date->copy()->setTimeFromTimeString(
                $schedule->end_time instanceof Carbon
                    ? $schedule->end_time->format('H:i:s')
                    : $schedule->end_time
            );
        } elseif ($schedule->duration_minutes) {
            $endTime = $startTime->copy()->addMinutes($schedule->duration_minutes);
        }

        // Use schedule's location, or fall back to group's location
        $locationCoordinates = $schedule->location_coordinates;
        $locationName = $schedule->location_name;

        if (! $locationCoordinates && $schedule->group) {
            $locationCoordinates = $schedule->group->location_coordinates;
            $locationName = $locationName ?: $schedule->group->location_name;
        }

        return [
            'title' => $schedule->title,
            'description' => $schedule->description ?? 'Recurring event: '.$schedule->title,
            'location_name' => $locationName,
            'location_coordinates' => $locationCoordinates,
            'start_time' => $startTime,
            'end_time' => $endTime,
        ];
    }

    /**
     * Update a recurring schedule.
     */
    public function updateSchedule(RecurringSchedule $schedule, array $data): RecurringSchedule
    {
        return DB::transaction(function () use ($schedule, $data) {
            $schedule->fill($data);
            $patternChanged = $schedule->isDirty(['frequency', 'interval', 'days_of_week', 'day_of_month']);
            $schedule->save();

            if ($patternChanged) {
                // Drop future, untouched occurrences (no RSVPs) and regenerate from today.
                $schedule->activities()
                    ->where('start_time', '>', now())
                    ->doesntHave('rsvps')
                    ->delete();
                $schedule->update(['last_generated_until' => null]);
            }

            if (! $patternChanged && $schedule->wasChanged(['title', 'description', 'location_name', 'location_coordinates', 'start_time', 'end_time', 'duration_minutes'])) {
                $this->syncFutureOccurrences($schedule);
            }

            if ($patternChanged || $schedule->wasChanged('is_active')) {
                $this->generateEventsFromSchedule($schedule->fresh());
            }

            return $schedule->fresh();
        });
    }

    /**
     * Copy title, description, location and times onto future, uncancelled occurrences.
     */
    protected function syncFutureOccurrences(RecurringSchedule $schedule): void
    {
        $activities = $schedule->activities()
            ->where('start_time', '>', now())
            ->where('status', '!=', 'cancelled')
            ->get();

        foreach ($activities as $activity) {
            $activity->update(
                $this->occurrenceAttributes($schedule, Carbon::parse($activity->getAttribute('recurrence_date')))
            );
        }
    }

    /**
     * Cancel a single occurrence (activity) from a recurring schedule.
     */
    public function cancelOccurrence(Activity $activity): void
    {
        $activity->update(['status' => 'cancelled']);
    }

    /**
     * Delete a recurring schedule and optionally its future events.
     */
    public function deleteSchedule(RecurringSchedule $schedule, bool $deleteFutureEvents = true): void
    {
        DB::transaction(function () use ($schedule, $deleteFutureEvents) {
            if ($deleteFutureEvents) {
                // Delete only future events (past events are kept for history)
                $schedule->activities()
                    ->where('start_time', '>', now())
                    ->delete();
            }

            $schedule->delete();
        });
    }

    /**
     * Get upcoming events for a schedule.
     */
    public function getUpcomingEvents(RecurringSchedule $schedule, int $limit = 10): Collection
    {
        return $schedule->activities()
            ->where('start_time', '>', now())
            ->where('status', '!=', 'cancelled')
            ->orderBy('start_time', 'asc')
            ->limit($limit)
            ->get();
    }
}
