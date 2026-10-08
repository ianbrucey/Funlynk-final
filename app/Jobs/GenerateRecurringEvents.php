<?php

namespace App\Jobs;

use App\Models\RecurringSchedule;
use App\Services\RecurringScheduleService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class GenerateRecurringEvents implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new job instance.
     */
    public function __construct()
    {
        //
    }

    /**
     * Execute the job.
     *
     * This job runs weekly to ensure recurring schedules have events generated
     * for the upcoming weeks based on each schedule's generate_weeks_ahead setting.
     */
    public function handle(RecurringScheduleService $service): void
    {
        $schedules = RecurringSchedule::where('is_active', true)
            ->with('group')
            ->get();

        Log::info('GenerateRecurringEvents: Starting generation run', [
            'active_schedules' => $schedules->count(),
        ]);

        $totalCreated = 0;

        foreach ($schedules as $schedule) {
            // Check if schedule needs generation
            if (! $schedule->needsGeneration()) {
                Log::debug('GenerateRecurringEvents: Schedule up to date', [
                    'schedule_id' => $schedule->id,
                    'last_generated_until' => $schedule->last_generated_until?->toDateString(),
                ]);

                continue;
            }

            try {
                $createdActivities = $service->generateEventsFromSchedule($schedule);
                $count = $createdActivities->count();
                $totalCreated += $count;

                Log::info('GenerateRecurringEvents: Generated events for schedule', [
                    'schedule_id' => $schedule->id,
                    'schedule_title' => $schedule->title,
                    'group_id' => $schedule->group_id,
                    'events_created' => $count,
                ]);
            } catch (\Exception $e) {
                Log::error('GenerateRecurringEvents: Failed to generate events', [
                    'schedule_id' => $schedule->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        Log::info('GenerateRecurringEvents: Generation run complete', [
            'schedules_processed' => $schedules->count(),
            'total_events_created' => $totalCreated,
        ]);
    }
}
