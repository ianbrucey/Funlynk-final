<?php

namespace App\Services;

use App\Events\GroupEventCreated;
use App\Events\GroupPostCreated;
use App\Models\Activity;
use App\Models\Group;
use App\Models\Post;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class GroupContentService
{
    public function createGroupPost(Group $group, User $user, array $data): Post
    {
        return DB::transaction(function () use ($group, $user, $data) {
            $post = $group->posts()->create([
                'user_id' => $user->id,
                'title' => $data['title'],
                'description' => $data['description'] ?? null,
                'location_name' => $data['location_name'] ?? null,
                'location_coordinates' => $data['location_coordinates'] ?? null,
                // Default expires_at to 48 hours if not provided (posts are ephemeral by design)
                'expires_at' => $data['expires_at'] ?? now()->addHours(48),
                'tags' => $data['tags'] ?? null, // tags is a JSON column, not a relationship
            ]);

            GroupPostCreated::dispatch($group, $post, $user);

            return $post;
        });
    }

    public function createGroupEvent(Group $group, User $user, array $data): Activity
    {
        return DB::transaction(function () use ($group, $user, $data) {
            $activity = $group->activities()->create([
                'host_id' => $user->id, // Activities use host_id, not user_id
                'title' => $data['title'],
                'description' => $data['description'] ?? null,
                'activity_type' => $data['activity_type'] ?? 'group_event', // Required field
                'location_name' => $data['location_name'],
                'location_coordinates' => $data['location_coordinates'] ?? null,
                'start_time' => $data['start_time'],
                'end_time' => $data['end_time'] ?? null,
                'max_attendees' => $data['max_attendees'] ?? null,
                'status' => $data['status'] ?? 'active',
                'is_public' => $data['is_public'] ?? false, // Group events are not public by default
            ]);

            if (isset($data['tags'])) {
                $activity->tags()->sync($data['tags']);
            }

            GroupEventCreated::dispatch($group, $activity, $user);

            return $activity;
        });
    }

    public function getGroupTimeline(Group $group, int $page = 1, int $perPage = 20): Collection
    {
        $posts = $group->posts()->with(['user', 'pinnedBy', 'reactions'])->get(); // tags is a JSON column, not a relationship
        $events = $group->activities()->with(['host', 'tags', 'rsvps.user'])->get(); // Activities use 'host' not 'user'

        // Sort: pinned posts first (by pinned_at desc), then all items by created_at desc
        $timeline = $posts->concat($events)->sortBy([
            ['is_pinned', 'desc'],      // Pinned items first
            ['pinned_at', 'desc'],      // Most recently pinned first among pinned
            ['created_at', 'desc'],     // Then by creation date
        ]);

        // Manual pagination for merged collection
        $offset = ($page - 1) * $perPage;

        return $timeline->slice($offset, $perPage)->values();
    }

    public function getGroupPosts(Group $group): Collection
    {
        return $group->posts()->with(['user'])->get(); // tags is a JSON column, not a relationship
    }

    public function getGroupEvents(Group $group): Collection
    {
        return $group->activities()->with(['host', 'tags'])->get(); // Activities use 'host' not 'user'
    }
}
