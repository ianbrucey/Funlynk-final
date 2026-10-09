<?php

namespace App\Livewire\Activities;

use App\Models\Activity;
use App\Services\ActivityEditService;
use App\Services\ActivityService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Component;
use Livewire\WithFileUploads;
use MatanYadaev\EloquentSpatial\Objects\Point;

class EditActivity extends Component
{
    use AuthorizesRequests;
    use WithFileUploads;

    public Activity $activity;

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

    public $payment_type = 'free';

    public $price = '';

    public $is_public = true;

    public $requires_approval = false;

    public $status = '';

    public $selectedTags = [];

    public $newTag = '';

    // Image handling
    public $newImages = [];

    public $existingImages = [];

    public $imagesToDelete = [];

    // Edit protection state
    public bool $showWarningModal = false;

    public array $pendingChanges = [];

    public array $blockedChanges = [];

    public array $significantChanges = [];

    public bool $isEditLocked = false;

    public int $paidAttendeeCount = 0;

    protected ActivityService $activityService;

    protected ActivityEditService $activityEditService;

    public function boot(ActivityService $activityService, ActivityEditService $activityEditService)
    {
        $this->activityService = $activityService;
        $this->activityEditService = $activityEditService;
    }

    public function mount(Activity $activity)
    {
        $this->activity = $activity;
        $this->authorize('update', $activity);

        // Edit protection state
        $this->isEditLocked = $activity->isEditLocked();
        $this->paidAttendeeCount = $activity->getPaidAttendeeCount();

        // Populate fields
        $this->title = $activity->title;
        $this->description = $activity->description;
        $this->activity_type = $activity->activity_type;
        $this->location_name = $activity->location_name;

        if ($activity->location_coordinates) {
            if ($activity->location_coordinates instanceof Point) {
                $this->latitude = $activity->location_coordinates->latitude;
                $this->longitude = $activity->location_coordinates->longitude;
            } elseif (is_array($activity->location_coordinates)) {
                // Handle array case (e.g. from some test factories)
                $this->latitude = $activity->location_coordinates['latitude'] ?? null;
                $this->longitude = $activity->location_coordinates['longitude'] ?? null;
            }
        }

        $this->start_time = $activity->start_time->format('Y-m-d\TH:i');
        $this->end_time = $activity->end_time ? $activity->end_time->format('Y-m-d\TH:i') : '';

        $this->max_attendees = $activity->max_attendees;
        $this->payment_type = $activity->payment_type ?? 'free';
        $this->price = $activity->price_cents ? $activity->price_cents / 100 : '';
        $this->is_public = $activity->is_public;
        $this->requires_approval = $activity->requires_approval;
        $this->status = $activity->status;

        $this->existingImages = $activity->images ?? [];

        // Load tags
        $this->selectedTags = $activity->tags->map(function ($tag) {
            return [
                'id' => $tag->id,
                'name' => $tag->name,
                'category' => $tag->category,
            ];
        })->toArray();
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
            'start_time' => 'required|date',
            'end_time' => 'nullable|date|after:start_time',
            'max_attendees' => 'nullable|integer|min:1',
            'price' => 'required_unless:payment_type,free|nullable|numeric|min:0.01',
            'newImages.*' => 'nullable|image|max:2048',
            'status' => 'required|in:draft,published,active,completed,cancelled',
        ];
    }

    protected function messages()
    {
        return [
            'end_time.after' => 'End time must be after start time',
            'price.required_if' => 'Please specify a price for paid activities',
        ];
    }

    public function updatedPaymentType($value)
    {
        if ($value === 'free') {
            $this->price = '';
        }
    }

    public function removeExistingImage($index)
    {
        if (isset($this->existingImages[$index])) {
            $this->imagesToDelete[] = $this->existingImages[$index];
            unset($this->existingImages[$index]);
            $this->existingImages = array_values($this->existingImages);
        }
    }

    public function updateActivity()
    {
        $this->authorize('update', $this->activity);
        $this->validate();

        try {
            // Create location point
            $locationPoint = new Point((float) $this->latitude, (float) $this->longitude);

            // Handle images
            $finalImages = $this->existingImages;

            // Add new images to S3
            if ($this->newImages) {
                foreach ($this->newImages as $image) {
                    $finalImages[] = $image->store('activities', 's3');
                }
            }

            // Prepare new values
            $newValues = [
                'title' => $this->title,
                'description' => $this->description,
                'activity_type' => $this->activity_type,
                'location_name' => $this->location_name,
                'location_coordinates' => $locationPoint,
                'start_time' => $this->start_time,
                'end_time' => $this->end_time ?: null,
                'max_attendees' => $this->max_attendees ?: null,
                'payment_type' => $this->payment_type,
                'price_cents' => $this->payment_type !== 'free' ? (int) round($this->price * 100) : null,
                'is_public' => $this->is_public,
                'requires_approval' => $this->requires_approval,
                'status' => $this->status,
                'images' => $finalImages,
            ];

            // If activity has paid attendees, use edit protection service
            if ($this->isEditLocked) {
                $result = $this->activityEditService->processEdit(
                    $this->activity,
                    $newValues,
                    auth()->user()
                );

                // Check for blocked changes
                if (! $result['success'] && ! empty($result['blocked_changes'])) {
                    $this->blockedChanges = $result['blocked_changes'];
                    session()->flash('error', $result['message']);

                    return;
                }

                // Check for significant changes - show warning modal
                if (! empty($result['significant_changes']) && ! $this->showWarningModal) {
                    $this->significantChanges = $result['significant_changes'];
                    $this->pendingChanges = $newValues;
                    $this->showWarningModal = true;

                    return;
                }
            } else {
                // No paid attendees - just update directly
                $this->activity->update($newValues);
            }

            // Sync tags
            if (! empty($this->selectedTags)) {
                $tagIds = array_column($this->selectedTags, 'id');
                $this->activity->tags()->sync($tagIds);
            } else {
                $this->activity->tags()->detach();
            }

            session()->flash('success', 'Activity updated successfully!');

            return redirect()->route('events.show', $this->activity->id);
        } catch (\Exception $e) {
            session()->flash('error', 'Failed to update activity: '.$e->getMessage());
        }
    }

    /**
     * Confirm update after warning modal
     */
    public function confirmUpdate()
    {
        $this->showWarningModal = false;

        try {
            // Process the edit with confirmation
            $result = $this->activityEditService->processEdit(
                $this->activity,
                $this->pendingChanges,
                auth()->user()
            );

            if (! $result['success']) {
                session()->flash('error', $result['message']);

                return;
            }

            // Sync tags
            if (! empty($this->selectedTags)) {
                $tagIds = array_column($this->selectedTags, 'id');
                $this->activity->tags()->sync($tagIds);
            } else {
                $this->activity->tags()->detach();
            }

            session()->flash('success', $result['message']);

            return redirect()->route('events.show', $this->activity->id);
        } catch (\Exception $e) {
            session()->flash('error', 'Failed to update activity: '.$e->getMessage());
        }
    }

    /**
     * Cancel update from warning modal
     */
    public function cancelUpdate()
    {
        $this->showWarningModal = false;
        $this->pendingChanges = [];
        $this->significantChanges = [];
    }

    public function useCurrentLocation()
    {
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
                'name' => $tag->name,
                'category' => $tag->category,
            ];
            $this->reset('newTag');
        }
    }

    public function removeTag($index)
    {
        unset($this->selectedTags[$index]);
        $this->selectedTags = array_values($this->selectedTags);
    }

    public function render()
    {
        return view('livewire.activities.edit-activity')
            ->layout('layouts.app');
    }
}
