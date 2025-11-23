<?php

namespace App\Services;

use App\Models\Activity;
use App\Models\Post;
use App\Models\PostConversion;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use MatanYadaev\EloquentSpatial\Objects\Point;

class ActivityService
{
    /**
     * Create an activity from a Post (Post-to-Event conversion)
     * 
     * @param Post $post
     * @param User|null $host Override host (defaults to post creator)
     * @return Activity
     */
    public function createFromPost(Post $post, ?User $host = null): Activity
    {
        // Check if conversion already exists (idempotency)
        $existingConversion = PostConversion::where('post_id', $post->id)->first();
        
        if ($existingConversion && $existingConversion->event_id) {
            return Activity::find($existingConversion->event_id);
        }

        return DB::transaction(function () use ($post, $host) {
            // Create activity with data from post
            $activity = Activity::create([
                'host_id' => $host?->id ?? $post->user_id,
                'title' => Str::limit($post->content, 50) ?? 'Activity from Post',
                'description' => $post->content,
                'activity_type' => $post->activity_type ?? 'social',
                'location_name' => $post->location_name,
                'location_coordinates' => $post->location_coordinates,
                'start_time' => $post->time_hint ?? now()->addDay(),
                'end_time' => null,
                'max_attendees' => null,
                'current_attendees' => 0,
                'is_public' => true,
                'requires_approval' => false,
                'is_paid' => false,
                'price_cents' => null,
                'status' => 'draft', // Host needs to finalize
                'originated_from_post_id' => $post->id,
                'conversion_date' => now(),
            ]);

            // Record conversion
            PostConversion::updateOrCreate(
                ['post_id' => $post->id],
                [
                    'event_id' => $activity->id,
                    'reactions_at_conversion' => $post->reaction_count ?? 0,
                    'comments_at_conversion' => 0, // Default
                    'views_at_conversion' => $post->view_count ?? 0,
                    'trigger_type' => 'manual', // Default to manual
                    'created_at' => now(),
                ]
            );

            return $activity;
        });
    }

    /**
     * Validate activity capacity
     * 
     * @param Activity $activity
     * @param int $additionalAttendees
     * @return array ['valid' => bool, 'message' => string|null]
     */
    public function validateCapacity(Activity $activity, int $additionalAttendees = 1): array
    {
        if (!$activity->max_attendees) {
            return ['valid' => true, 'message' => null];
        }

        $newTotal = $activity->current_attendees + $additionalAttendees;

        if ($newTotal > $activity->max_attendees) {
            $available = $activity->max_attendees - $activity->current_attendees;
            return [
                'valid' => false,
                'message' => "Only {$available} spot(s) remaining. Cannot add {$additionalAttendees} attendee(s)."
            ];
        }

        return ['valid' => true, 'message' => null];
    }

    /**
     * Check if activity is full
     * 
     * @param Activity $activity
     * @return bool
     */
    public function isFull(Activity $activity): bool
    {
        if (!$activity->max_attendees) {
            return false;
        }

        return $activity->current_attendees >= $activity->max_attendees;
    }

    /**
     * Get available spots
     * 
     * @param Activity $activity
     * @return int|null Null if unlimited
     */
    public function getAvailableSpots(Activity $activity): ?int
    {
        if (!$activity->max_attendees) {
            return null;
        }

        return max(0, $activity->max_attendees - $activity->current_attendees);
    }

    /**
     * Update activity status
     * 
     * @param Activity $activity
     * @param string $newStatus
     * @return bool
     */
    public function updateStatus(Activity $activity, string $newStatus): bool
    {
        $validTransitions = $this->getValidStatusTransitions($activity->status);

        if (!in_array($newStatus, $validTransitions)) {
            return false;
        }

        $activity->update(['status' => $newStatus]);
        return true;
    }

    /**
     * Get valid status transitions from current status
     * 
     * @param string $currentStatus
     * @return array
     */
    public function getValidStatusTransitions(string $currentStatus): array
    {
        return match ($currentStatus) {
            'draft' => ['published', 'cancelled'],
            'published' => ['active', 'cancelled'],
            'active' => ['completed', 'cancelled'],
            'completed' => [],
            'cancelled' => [],
            default => [],
        };
    }

    /**
     * Validate activity data
     * 
     * @param array $data
     * @return array ['valid' => bool, 'errors' => array]
     */
    public function validateActivityData(array $data): array
    {
        $errors = [];

        // Title validation
        if (empty($data['title']) || strlen($data['title']) < 3) {
            $errors['title'] = 'Title must be at least 3 characters';
        }

        // Description validation
        if (empty($data['description']) || strlen($data['description']) < 10) {
            $errors['description'] = 'Description must be at least 10 characters';
        }

        // Time validation
        if (isset($data['start_time']) && isset($data['end_time'])) {
            $start = is_string($data['start_time']) ? strtotime($data['start_time']) : $data['start_time']->timestamp;
            $end = is_string($data['end_time']) ? strtotime($data['end_time']) : $data['end_time']->timestamp;

            if ($end <= $start) {
                $errors['end_time'] = 'End time must be after start time';
            }
        }

        // Capacity validation
        if (isset($data['max_attendees']) && $data['max_attendees'] < 1) {
            $errors['max_attendees'] = 'Maximum attendees must be at least 1';
        }

        // Price validation
        if (isset($data['is_paid']) && $data['is_paid']) {
            if (!isset($data['price_cents']) || $data['price_cents'] < 1) {
                $errors['price_cents'] = 'Paid activities must have a price greater than 0';
            }
        }

        // Location validation
        if (empty($data['location_name'])) {
            $errors['location_name'] = 'Location name is required';
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors,
        ];
    }

    /**
     * Get activities near a location
     * 
     * @param Point $location
     * @param int $radiusMeters
     * @param int $limit
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getActivitiesNearLocation(Point $location, int $radiusMeters = 50000, int $limit = 20)
    {
        return Activity::query()
            ->whereDistance('location_coordinates', $location, '<=', $radiusMeters)
            ->where('status', 'active')
            ->where('is_public', true)
            ->orderByDistance('location_coordinates', $location)
            ->limit($limit)
            ->get();
    }

    /**
     * Get activities hosted by user
     * 
     * @param User $user
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getHostedActivities(User $user)
    {
        return Activity::query()
            ->where('host_id', $user->id)
            ->orderBy('start_time', 'desc')
            ->get();
    }

    /**
     * Check if user can edit activity
     * 
     * @param Activity $activity
     * @param User $user
     * @return bool
     */
    public function canEdit(Activity $activity, User $user): bool
    {
        // Host can always edit
        if ($activity->host_id === $user->id) {
            return true;
        }

        // TODO: Add admin check when role system is implemented
        
        return false;
    }

    /**
     * Check if user can delete activity
     * 
     * @param Activity $activity
     * @param User $user
     * @return bool
     */
    public function canDelete(Activity $activity, User $user): bool
    {
        // Only host can delete, and only if no attendees
        if ($activity->host_id === $user->id && $activity->current_attendees === 0) {
            return true;
        }

        // TODO: Add admin check when role system is implemented
        
        return false;
    }

    /**
     * Duplicate an activity (for recurring events)
     * 
     * @param Activity $activity
     * @param array $overrides
     * @return Activity
     */
    public function duplicate(Activity $activity, array $overrides = []): Activity
    {
        $data = $activity->toArray();
        
        // Remove unique fields
        unset($data['id'], $data['created_at'], $data['updated_at']);
        
        // Reset attendee count
        $data['current_attendees'] = 0;
        $data['status'] = 'draft';
        
        // Apply overrides
        $data = array_merge($data, $overrides);
        
        $newActivity = Activity::create($data);
        
        // Copy tags
        $newActivity->tags()->sync($activity->tags->pluck('id'));
        
        return $newActivity;
    }
}
