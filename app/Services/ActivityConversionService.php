<?php

namespace App\Services;

use App\Events\PostConvertedToEvent;
use App\Models\Activity;
use App\Models\Post;
use App\Models\PostConversion;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ActivityConversionService
{
    /**
     * Create an activity from a post conversion
     *
     *
     * @throws \Exception
     */
    public function createFromPost(Post $post, array $eventData, User $host): Activity
    {
        return DB::transaction(function () use ($post, $eventData, $host) {
            // Validate post is eligible
            if (! $post->isEligibleForConversion()) {
                throw new \Exception('Post is not eligible for conversion');
            }

            // Create activity with pre-filled data
            $activity = Activity::create([
                'id' => Str::uuid(),
                'user_id' => $host->id,
                'title' => $eventData['title'] ?? $post->title,
                'description' => $eventData['description'] ?? $post->description,
                'location_name' => $eventData['location_name'] ?? $post->location_name,
                'location_coordinates' => $eventData['location_coordinates'] ?? $post->location_coordinates,
                'start_time' => $eventData['start_time'],
                'end_time' => $eventData['end_time'],
                'max_attendees' => $eventData['max_attendees'],
                'price' => $eventData['price'] ?? 0,
                'is_paid' => ($eventData['price'] ?? 0) > 0,
                'status' => 'published',
                'originated_from_post_id' => $post->id,
            ]);

            // Sync tags
            if (isset($eventData['tags'])) {
                $activity->tags()->sync($eventData['tags']);
            } elseif ($post->tags && count($post->tags) > 0) {
                // Get tag IDs from tag names
                $tagIds = \App\Models\Tag::whereIn('name', $post->tags)->pluck('id')->toArray();
                if (! empty($tagIds)) {
                    $activity->tags()->sync($tagIds);
                }
            }

            // Create conversion record
            $conversion = PostConversion::create([
                'post_id' => $post->id,
                'event_id' => $activity->id,
                'converted_by' => $host->id,
                'reactions_at_conversion' => $post->reaction_count,
                'trigger_type' => 'manual',
            ]);

            // Update post status
            $post->update(['status' => 'converted']);

            // Dispatch event for notifications
            event(new PostConvertedToEvent($post, $activity, $conversion));

            return $activity;
        });
    }

    /**
     * Preview conversion data without creating the event
     */
    public function previewConversion(Post $post, array $eventData = []): array
    {
        // Get interested users count
        $interestedCount = $post->reactions()
            ->where('reaction_type', 'im_down')
            ->count();

        // Get invited users count (pending invitations)
        $invitedCount = $post->invitations()
            ->where('status', 'pending')
            ->count();

        // Calculate suggested capacity
        $suggestedCapacity = (int) ceil($interestedCount * 1.5);

        return [
            'interested_users_count' => $interestedCount,
            'invited_users_count' => $invitedCount,
            'total_potential_attendees' => $interestedCount + $invitedCount,
            'suggested_capacity' => max($suggestedCapacity, 10), // Min 10
            'event_preview' => [
                'title' => $eventData['title'] ?? $post->title,
                'description' => $eventData['description'] ?? $post->description,
                'location' => $eventData['location_name'] ?? $post->location_name,
                'start_time' => $eventData['start_time'] ?? null,
                'price' => $eventData['price'] ?? 0,
            ],
        ];
    }
}
