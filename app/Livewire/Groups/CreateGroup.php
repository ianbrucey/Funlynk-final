<?php

namespace App\Livewire\Groups;

use App\Services\GroupService;
use App\Services\RecurringScheduleService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Livewire\Component;
use Livewire\WithFileUploads;
use MatanYadaev\EloquentSpatial\Objects\Point;

class CreateGroup extends Component
{
    use WithFileUploads;

    public string $name = '';

    public string $description = '';

    public string $privacy = 'public';

    // Dynamic tags (type-and-enter pattern)
    public array $tags = [];

    public string $newTag = '';

    // Location with geocoding
    public string $location_name = '';

    public ?float $latitude = null;

    public ?float $longitude = null;

    // Marketing / Landing Page Fields
    public string $emoji = '';

    public string $schedule_text = '';

    public string $meetup_label = 'Session';

    // Image uploads
    public $avatarImage = null;

    public $coverImage = null;

    // Recurring Schedule Fields
    public bool $hasRecurringSchedule = false;

    public string $scheduleTitle = '';

    public array $scheduleDays = [];

    public string $scheduleStartHour = '6';

    public string $scheduleStartMinute = '00';

    public string $scheduleStartPeriod = 'PM';

    public ?string $scheduleEndHour = null;

    public ?string $scheduleEndMinute = null;

    public ?string $scheduleEndPeriod = null;

    protected GroupService $groupService;

    public function boot(GroupService $groupService)
    {
        $this->groupService = $groupService;
    }

    public function dehydrate()
    {
        // Ensure coordinates are always primitives, never objects
        if ($this->latitude !== null) {
            $this->latitude = (float) $this->latitude;
        }
        if ($this->longitude !== null) {
            $this->longitude = (float) $this->longitude;
        }
    }

    protected function rules(): array
    {
        $rules = [
            'name' => ['required', 'string', 'max:100'],
            'description' => ['required', 'string', 'max:500'],
            'privacy' => ['required', 'string', Rule::in(['public', 'private'])],
            'tags' => ['nullable', 'array', 'max:10'],
            'tags.*' => ['string', 'max:50'],
            'location_name' => ['required', 'string', 'max:255'],
            'latitude' => ['required', 'numeric', 'between:-90,90'],
            'longitude' => ['required', 'numeric', 'between:-180,180'],
            'emoji' => ['nullable', 'string', 'max:8'],
            'schedule_text' => ['nullable', 'string', 'max:100'],
            'meetup_label' => ['nullable', 'string', 'max:50'],
            'avatarImage' => ['nullable', 'image', 'max:6144'],
            'coverImage' => ['nullable', 'image', 'max:6144'],
            'hasRecurringSchedule' => ['boolean'],
        ];

        // Add schedule validation rules only when recurring schedule is enabled
        if ($this->hasRecurringSchedule) {
            $rules['scheduleTitle'] = ['required', 'string', 'max:100'];
            $rules['scheduleDays'] = ['required', 'array', 'min:1'];
            $rules['scheduleDays.*'] = ['in:monday,tuesday,wednesday,thursday,friday,saturday,sunday'];
            $rules['scheduleStartHour'] = ['required', 'in:1,2,3,4,5,6,7,8,9,10,11,12'];
            $rules['scheduleStartMinute'] = ['required', 'in:00,15,30,45'];
            $rules['scheduleStartPeriod'] = ['required', 'in:AM,PM'];
        }

        return $rules;
    }

    protected function messages(): array
    {
        return [
            'name.required' => 'A group name is required.',
            'name.max' => 'The group name cannot exceed 100 characters.',
            'description.required' => 'A description is required for the group.',
            'description.max' => 'The description cannot exceed 500 characters.',
            'privacy.required' => 'Group privacy setting is required.',
            'privacy.in' => 'Invalid privacy setting.',
            'location_name.required' => 'Location is required. Start typing to search for a city.',
            'latitude.required' => 'Please select a location from the suggestions.',
            'longitude.required' => 'Please select a location from the suggestions.',
            'tags.max' => 'Maximum 10 tags allowed.',
            'emoji.max' => 'Emoji should be a single character or icon.',
        ];
    }

    public function updated($propertyName)
    {
        $this->validateOnly($propertyName);
    }

    public function updatedLatitude($value)
    {
        $this->latitude = $value ? (float) $value : null;
    }

    public function updatedLongitude($value)
    {
        $this->longitude = $value ? (float) $value : null;
    }

    public function setLocationData($name, $lat, $lng)
    {
        $this->location_name = $name;
        $this->latitude = $lat ? (float) $lat : null;
        $this->longitude = $lng ? (float) $lng : null;
    }

    public function addTag()
    {
        if (empty($this->newTag)) {
            return;
        }

        if (count($this->tags) >= 10) {
            $this->addError('tags', 'Maximum 10 tags allowed.');

            return;
        }

        $tag = trim($this->newTag);
        if (! in_array($tag, $this->tags)) {
            $this->tags[] = $tag;
        }

        $this->reset('newTag');
    }

    public function removeTag($index)
    {
        unset($this->tags[$index]);
        $this->tags = array_values($this->tags);
    }

    public function toggleScheduleDay(string $day): void
    {
        if (in_array($day, $this->scheduleDays)) {
            $this->scheduleDays = array_values(array_diff($this->scheduleDays, [$day]));
        } else {
            $this->scheduleDays[] = $day;
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

    public function createGroup()
    {
        $this->validate();

        try {
            $data = [
                'name' => $this->name,
                'description' => $this->description,
                'privacy' => $this->privacy,
                'tags' => $this->tags,
                'location_name' => $this->location_name,
                'latitude' => $this->latitude,
                'longitude' => $this->longitude,
                'emoji' => $this->emoji,
                'schedule_text' => $this->schedule_text,
                'meetup_label' => $this->meetup_label ?: 'Session',
            ];

            // Handle avatar upload
            if ($this->avatarImage) {
                $avatarPath = $this->avatarImage->store('groups/avatars', 'public');
                $data['avatar_url'] = '/storage/'.$avatarPath;
            }

            // Handle cover image upload
            if ($this->coverImage) {
                $coverPath = $this->coverImage->store('groups/covers', 'public');
                $data['cover_image_url'] = '/storage/'.$coverPath;
            }

            $group = $this->groupService->createGroup(Auth::user(), $data);

            // Create recurring schedule if enabled
            if ($this->hasRecurringSchedule && ! empty($this->scheduleDays)) {
                $scheduleService = app(RecurringScheduleService::class);

                $locationPoint = null;
                if ($this->latitude && $this->longitude) {
                    $locationPoint = new Point((float) $this->latitude, (float) $this->longitude, 4326);
                }

                $startTime = $this->buildTimeString($this->scheduleStartHour, $this->scheduleStartMinute, $this->scheduleStartPeriod);

                $endTime = null;
                if ($this->scheduleEndHour && $this->scheduleEndMinute && $this->scheduleEndPeriod) {
                    $endTime = $this->buildTimeString($this->scheduleEndHour, $this->scheduleEndMinute, $this->scheduleEndPeriod);
                }

                $scheduleService->createSchedule($group, Auth::user(), [
                    'title' => $this->scheduleTitle,
                    'location_name' => $this->location_name,
                    'location_coordinates' => $locationPoint,
                    'frequency' => 'weekly',
                    'days_of_week' => $this->scheduleDays,
                    'start_time' => $startTime,
                    'end_time' => $endTime,
                    'generate_weeks_ahead' => 4,
                ]);
            }

            session()->flash('message', 'Group created successfully!');

            // Redirect to the member dashboard initially
            return redirect()->to('/groups/'.$group->slug);
        } catch (\Exception $e) {
            session()->flash('error', 'Failed to create group: '.$e->getMessage());
        }
    }

    public function render()
    {
        return view('livewire.groups.create-group')
            ->layout('layouts.app');
    }
}
