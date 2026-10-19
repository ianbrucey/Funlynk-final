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

    /**
     * Newest-first merged timeline of posts and events, pinned posts first.
     * Each source is limited in the database, so the cost is bounded by $limit.
     */
    public function getGroupTimeline(Group $group, int $limit = 20): Collection
    {
        $posts = $group->posts()
            ->with(['user', 'pinnedBy', 'reactions'])
            ->orderByDesc('is_pinned')
            ->orderByDesc('pinned_at')
            ->orderByDesc('created_at')
            ->limit($limit)
            ->get();
        $events = $group->activities()
            ->with(['host', 'tags', 'rsvps.user'])
            ->orderByDesc('created_at')
            ->limit($limit)
            ->get();

        return $posts->concat($events)->sortBy([
            ['is_pinned', 'desc'],
            ['pinned_at', 'desc'],
            ['created_at', 'desc'],
        ])->take($limit)->values();
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
