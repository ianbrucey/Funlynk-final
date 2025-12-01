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
                'description' => $data['description'],
                'location_name' => $data['location_name'] ?? null,
                'location_coordinates' => $data['location_coordinates'] ?? null,
                'expires_at' => $data['expires_at'] ?? null,
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
                'user_id' => $user->id,
                'title' => $data['title'],
                'description' => $data['description'],
                'location_name' => $data['location_name'],
                'location_coordinates' => $data['location_coordinates'],
                'start_time' => $data['start_time'],
                'end_time' => $data['end_time'],
                'max_attendees' => $data['max_attendees'] ?? null,
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
        $posts = $group->posts()->with(['user'])->get(); // tags is a JSON column, not a relationship
        $events = $group->activities()->with(['user', 'tags'])->get();

        $timeline = $posts->concat($events)->sortByDesc('created_at');

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
        return $group->activities()->with(['user', 'tags'])->get();
    }
}
