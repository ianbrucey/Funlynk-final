<?php

namespace App\Livewire\Groups;

use App\Models\Group;
use App\Services\RecurringScheduleService;
use Livewire\Attributes\On;
use Livewire\Component;
use MatanYadaev\EloquentSpatial\Objects\Point;

class ManageRecurringSchedules extends Component
{
    public Group $group;

    // Modal states
    public bool $showCreateModal = false;
    public bool $showEditModal = false;
    public bool $showDeleteModal = false;
    public bool $showEventsModal = false;

    // Form fields
    public string $title = '';
    public string $description = '';
    public string $locationName = '';
    public ?float $latitude = null;
    public ?float $longitude = null;
    public string $frequency = 'weekly';
    public array $daysOfWeek = [];
    public ?int $dayOfMonth = null;
    public string $startTimeHour = '6';
    public string $startTimeMinute = '00';
    public string $startTimePeriod = 'PM';
    public ?string $endTimeHour = null;
    public ?string $endTimeMinute = null;
    public ?string $endTimePeriod = null;
    public int $generateWeeksAhead = 4;

    // For editing
    public ?string $editingScheduleId = null;
    public ?string $deletingScheduleId = null;
    public ?string $viewingScheduleId = null;

    public function mount(Group $group): void
    {
        $this->group = $group;
    }

    #[On('open-create-schedule-modal')]
    public function handleOpenCreateModal(): void
    {
        $this->openCreateModal();
    }

    #[On('open-edit-schedule-modal')]
    public function handleOpenEditModal(string $scheduleId): void
    {
        $this->openEditModal($scheduleId);
    }

    #[On('open-delete-schedule-modal')]
    public function handleOpenDeleteModal(string $scheduleId): void
    {
        $this->openDeleteModal($scheduleId);
    }

    #[On('open-events-modal')]
    public function handleOpenEventsModal(string $scheduleId): void
    {
        $this->openEventsModal($scheduleId);
    }

    #[On('toggle-schedule-pause')]
    public function handleTogglePause(string $scheduleId): void
    {
        $this->togglePause($scheduleId);
    }

    protected function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:100'],
            'description' => ['nullable', 'string', 'max:500'],
            'locationName' => ['nullable', 'string', 'max:255'],
            'frequency' => ['required', 'in:daily,weekly,monthly'],
            'daysOfWeek' => ['required_if:frequency,weekly', 'array'],
            'daysOfWeek.*' => ['in:monday,tuesday,wednesday,thursday,friday,saturday,sunday'],
            'dayOfMonth' => ['required_if:frequency,monthly', 'nullable', 'integer', 'min:1', 'max:31'],
            'startTimeHour' => ['required', 'in:1,2,3,4,5,6,7,8,9,10,11,12'],
            'startTimeMinute' => ['required', 'in:00,15,30,45'],
            'startTimePeriod' => ['required', 'in:AM,PM'],
            'generateWeeksAhead' => ['required', 'integer', 'min:1', 'max:12'],
        ];
    }

    public function openCreateModal(): void
    {
        $this->resetForm();
        $this->showCreateModal = true;
    }

    public function openEditModal(string $scheduleId): void
    {
        $schedule = $this->group->recurringSchedules()->findOrFail($scheduleId);
        $this->editingScheduleId = $scheduleId;

        $this->title = $schedule->title;
        $this->description = $schedule->description ?? '';
        $this->locationName = $schedule->location_name ?? '';
        $this->frequency = $schedule->frequency;
        $this->daysOfWeek = $schedule->days_of_week ?? [];
        $this->dayOfMonth = $schedule->day_of_month;
        $this->generateWeeksAhead = $schedule->generate_weeks_ahead;

        // Parse start time
        if ($schedule->start_time) {
            $hour = (int) $schedule->start_time->format('g');
            $this->startTimeHour = (string) $hour;
            $this->startTimeMinute = $schedule->start_time->format('i');
            $this->startTimePeriod = $schedule->start_time->format('A');
        }

        // Parse end time
        if ($schedule->end_time) {
            $hour = (int) $schedule->end_time->format('g');
            $this->endTimeHour = (string) $hour;
            $this->endTimeMinute = $schedule->end_time->format('i');
            $this->endTimePeriod = $schedule->end_time->format('A');
        }

        if ($schedule->location_coordinates) {
            $this->latitude = $schedule->location_coordinates->latitude;
            $this->longitude = $schedule->location_coordinates->longitude;
        }

        $this->showEditModal = true;
    }

    public function openDeleteModal(string $scheduleId): void
    {
        $this->deletingScheduleId = $scheduleId;
        $this->showDeleteModal = true;
    }

    public function openEventsModal(string $scheduleId): void
    {
        $this->viewingScheduleId = $scheduleId;
        $this->showEventsModal = true;
    }

    public function closeModals(): void
    {
        $this->showCreateModal = false;
        $this->showEditModal = false;
        $this->showDeleteModal = false;
        $this->showEventsModal = false;
        $this->editingScheduleId = null;
        $this->deletingScheduleId = null;
        $this->viewingScheduleId = null;
    }

    protected function resetForm(): void
    {
        $this->title = '';
        $this->description = '';
        $this->locationName = '';
        $this->latitude = null;
        $this->longitude = null;
        $this->frequency = 'weekly';
        $this->daysOfWeek = [];
        $this->dayOfMonth = null;
        $this->startTimeHour = '6';
        $this->startTimeMinute = '00';
        $this->startTimePeriod = 'PM';
        $this->endTimeHour = null;
        $this->endTimeMinute = null;
        $this->endTimePeriod = null;
        $this->generateWeeksAhead = 4;
        $this->editingScheduleId = null;
    }

    public function setLocationData($name, $lat, $lng): void
    {
        $this->locationName = $name;
        $this->latitude = $lat ? (float) $lat : null;
        $this->longitude = $lng ? (float) $lng : null;
    }

    public function toggleDay(string $day): void
    {
        if (in_array($day, $this->daysOfWeek)) {
            $this->daysOfWeek = array_values(array_diff($this->daysOfWeek, [$day]));
        } else {
            $this->daysOfWeek[] = $day;
        }
    }

    protected function buildTimeString(string $hour, string $minute, string $period): string
    {
        $h = (int) $hour;
        if ($period === 'PM' && $h !== 12) {
            $h += 12;
        } elseif ($period === 'AM' && $h === 12) {
            $h = 0;
        }
        return sprintf('%02d:%s:00', $h, $minute);
    }

    public function createSchedule(RecurringScheduleService $service): void
    {
        $this->validate();

        $locationPoint = null;
        if ($this->latitude && $this->longitude) {
            $locationPoint = new Point((float) $this->latitude, (float) $this->longitude, 4326);
        }

        $startTime = $this->buildTimeString($this->startTimeHour, $this->startTimeMinute, $this->startTimePeriod);

        $endTime = null;
        if ($this->endTimeHour && $this->endTimeMinute && $this->endTimePeriod) {
            $endTime = $this->buildTimeString($this->endTimeHour, $this->endTimeMinute, $this->endTimePeriod);
        }

        $service->createSchedule($this->group, auth()->user(), [
            'title' => $this->title,
            'description' => $this->description ?: null,
            'location_name' => $this->locationName ?: null,
            'location_coordinates' => $locationPoint,
            'frequency' => $this->frequency,
            'days_of_week' => $this->frequency === 'weekly' ? $this->daysOfWeek : null,
            'day_of_month' => $this->frequency === 'monthly' ? $this->dayOfMonth : null,
            'start_time' => $startTime,
            'end_time' => $endTime,
            'generate_weeks_ahead' => $this->generateWeeksAhead,
        ]);

        $this->closeModals();
        $this->resetForm();

        // Dispatch event to refresh the settings page
        $this->dispatch('schedule-created');

        session()->flash('success', 'Recurring schedule created and events generated!');
    }

    public function updateSchedule(RecurringScheduleService $service): void
    {
        $this->validate();

        $schedule = $this->group->recurringSchedules()->findOrFail($this->editingScheduleId);

        $locationPoint = null;
        if ($this->latitude && $this->longitude) {
            $locationPoint = new Point((float) $this->latitude, (float) $this->longitude, 4326);
        }

        $startTime = $this->buildTimeString($this->startTimeHour, $this->startTimeMinute, $this->startTimePeriod);

        $endTime = null;
        if ($this->endTimeHour && $this->endTimeMinute && $this->endTimePeriod) {
            $endTime = $this->buildTimeString($this->endTimeHour, $this->endTimeMinute, $this->endTimePeriod);
        }

        $service->updateSchedule($schedule, [
            'title' => $this->title,
            'description' => $this->description ?: null,
            'location_name' => $this->locationName ?: null,
            'location_coordinates' => $locationPoint,
            'frequency' => $this->frequency,
            'days_of_week' => $this->frequency === 'weekly' ? $this->daysOfWeek : null,
            'day_of_month' => $this->frequency === 'monthly' ? $this->dayOfMonth : null,
            'start_time' => $startTime,
            'end_time' => $endTime,
            'generate_weeks_ahead' => $this->generateWeeksAhead,
        ]);

        $this->closeModals();
        $this->resetForm();
        session()->flash('success', 'Schedule updated successfully!');
    }

    public function togglePause(string $scheduleId): void
    {
        $schedule = $this->group->recurringSchedules()->findOrFail($scheduleId);

        if ($schedule->is_active) {
            $schedule->pause();
            session()->flash('success', 'Schedule paused. No new events will be generated.');
        } else {
            $schedule->resume();
            session()->flash('success', 'Schedule resumed. Events will be generated again.');
        }
    }

    public function deleteSchedule(RecurringScheduleService $service): void
    {
        $schedule = $this->group->recurringSchedules()->findOrFail($this->deletingScheduleId);
        $service->deleteSchedule($schedule, deleteFutureEvents: true);

        $this->closeModals();
        session()->flash('success', 'Schedule and future events deleted.');
    }

    public function cancelEvent(string $activityId): void
    {
        $activity = $this->group->activities()->findOrFail($activityId);
        $activity->update(['status' => 'cancelled']);
        session()->flash('success', 'Event cancelled.');
    }

    public function render()
    {
        $schedules = $this->group->recurringSchedules()
            ->with(['creator'])
            ->withCount('upcomingActivities')
            ->orderBy('created_at', 'desc')
            ->get();

        $viewingSchedule = null;
        $upcomingEvents = collect();
        if ($this->viewingScheduleId) {
            $viewingSchedule = $this->group->recurringSchedules()->find($this->viewingScheduleId);
            if ($viewingSchedule) {
                $upcomingEvents = $viewingSchedule->activities()
                    ->where('start_time', '>', now())
                    ->orderBy('start_time', 'asc')
                    ->limit(20)
                    ->get();
            }
        }

        return view('livewire.groups.manage-recurring-schedules', [
            'schedules' => $schedules,
            'viewingSchedule' => $viewingSchedule,
            'upcomingEvents' => $upcomingEvents,
        ]);
    }
}
