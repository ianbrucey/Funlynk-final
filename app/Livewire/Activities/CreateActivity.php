<?php

namespace App\Livewire\Activities;

use App\Models\Activity;
use App\Services\ActivityService;
use Livewire\Component;
use Livewire\WithFileUploads;
use MatanYadaev\EloquentSpatial\Objects\Point;

class CreateActivity extends Component
{
    use WithFileUploads;

    // Form fields
    public $title = '';
    public $description = '';
    public $activity_type = 'social';
    public $location_name = '';
    public $latitude = '';
    public $longitude = '';
    public $start_time = '';
    public $end_time = '';
    public $max_attendees = '';
    public $is_paid = false;
    public $price = '';
    public $is_public = true;
    public $requires_approval = false;
    public $selectedTags = [];
    public $newTag = '';
    public $images = [];

    protected ActivityService $activityService;

    public function boot(ActivityService $activityService)
    {
        $this->activityService = $activityService;
    }

    public function mount()
    {
        // Set default start time to tomorrow
        $this->start_time = now()->addDay()->format('Y-m-d\TH:i');
    }

    protected function rules()
    {
        return [
            'title' => 'required|min:3|max:255',
            'description' => 'required|min:10',
            'activity_type' => 'required|in:sports,music,food,social,outdoor,arts,wellness,tech,education,other',
            'location_name' => 'required|min:3',
            'latitude' => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180',
            'start_time' => 'required|date|after:now',
            'end_time' => 'nullable|date|after:start_time',
            'max_attendees' => 'nullable|integer|min:1',
            'max_attendees' => 'nullable|integer|min:1',
            'price' => 'required_if:is_paid,true|nullable|numeric|min:0.01',
            'images.*' => 'nullable|image|max:2048',
        ];
    }

    protected function messages()
    {
        return [
            'title.required' => 'Please give your activity a title',
            'title.min' => 'Title must be at least 3 characters',
            'description.required' => 'Please describe your activity',
            'description.min' => 'Description must be at least 10 characters',
            'location_name.required' => 'Please specify where this activity will take place',
            'latitude.required' => 'Please provide location coordinates',
            'longitude.required' => 'Please provide location coordinates',
            'start_time.required' => 'Please specify when the activity starts',
            'start_time.after' => 'Activity must start in the future',
            'end_time.after' => 'End time must be after start time',
            'end_time.after' => 'End time must be after start time',
            'price.required_if' => 'Please specify a price for paid activities',
        ];
    }

    public function updatedIsPaid($value)
    {
        if (!$value) {
        if (!$value) {
            $this->price = '';
        }
        }
    }

    public function createActivity()
    {
        $this->validate();

        try {
            // Create location point
            $locationPoint = new Point((float)$this->latitude, (float)$this->longitude);

            // Upload images
            $imagePaths = [];
            if ($this->images) {
                foreach ($this->images as $image) {
                    $imagePaths[] = $image->store('activities', 'public');
                }
            }

            // Create activity
            $activity = Activity::create([
                'host_id' => auth()->id(),
                'title' => $this->title,
                'description' => $this->description,
                'activity_type' => $this->activity_type,
                'location_name' => $this->location_name,
                'location_coordinates' => $locationPoint,
                'start_time' => $this->start_time,
                'end_time' => $this->end_time ?: null,
                'max_attendees' => $this->max_attendees ?: null,
                'current_attendees' => 0,
                'is_paid' => $this->is_paid,
                'price_cents' => $this->is_paid ? (int) round($this->price * 100) : null,
                'currency' => 'USD',
                'is_public' => $this->is_public,
                'requires_approval' => $this->requires_approval,
                'status' => 'published',
                'images' => $imagePaths,
            ]);

            // Attach tags
            if (!empty($this->selectedTags)) {
                $tagIds = array_column($this->selectedTags, 'id');
                $activity->tags()->sync($tagIds);
            }

            session()->flash('success', 'Activity created successfully!');
            
            return redirect()->route('activities.show', $activity->id);
        } catch (\Exception $e) {
            session()->flash('error', 'Error creating activity: ' . $e->getMessage());
        }
    }

    public function useCurrentLocation()
    {
        // This will be triggered by JavaScript to get user's location
        $this->dispatch('getCurrentLocation');
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

        if (count($this->selectedTags) >= 10) {
            $this->addError('selectedTags', 'Maximum 10 tags allowed.');
            return;
        }

        $tagName = trim($this->newTag);

        // Check if tag already selected
        foreach ($this->selectedTags as $tag) {
            if (strcasecmp($tag['name'], $tagName) === 0) {
                $this->reset('newTag');
                return;
            }
        }

        // Find or create tag
        $tag = \App\Models\Tag::firstOrCreate(['name' => $tagName]);
        if ($tag) {
            $this->selectedTags[] = [
                'id' => $tag->id,
                'name' => $tag->name
            ];
            $this->newTag = '';
        }
    }

    public function removeTag($index)
    {
        unset($this->selectedTags[$index]);
        $this->selectedTags = array_values($this->selectedTags);
    }

    public function render()
    {
        return view('livewire.activities.create-activity')
            ->layout('layouts.app');
    }
}
