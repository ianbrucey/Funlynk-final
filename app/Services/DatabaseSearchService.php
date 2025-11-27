<?php

namespace App\Services;

use App\Contracts\SearchServiceInterface;
use App\Models\Activity;
use App\Models\Post;
use App\Models\User;
use Illuminate\Support\Collection;
use MatanYadaev\EloquentSpatial\Objects\Point;

class DatabaseSearchService implements SearchServiceInterface
{
    /**
     * Search posts and events using PostgreSQL full-text search
     */
    public function search(
        string $query,
        User $user,
        ?int $radius = null,
        string $contentType = 'all'
    ): Collection {
        $results = collect();

        // Search Posts
        if ($contentType !== 'events') {
            $posts = $this->searchPosts($query, $user, $radius);
            $results = $results->merge($posts);
        }

        // Search Activities/Events
        if ($contentType !== 'posts') {
            $events = $this->searchActivities($query, $user, $radius);
            $results = $results->merge($events);
        }

        return $results;
    }

    /**
     * Search posts with PostgreSQL ILIKE
     */
    protected function searchPosts(string $query, User $user, ?int $radius): Collection
    {
        $searchQuery = Post::with(['user', 'reactions'])
            ->where('status', 'active')
            ->where('expires_at', '>', now());

        // Add geo-proximity filter BEFORE text search if radius provided
        if ($radius && $user->latitude && $user->longitude) {
            $userLocation = new Point($user->latitude, $user->longitude, 4326);
            // Posts are capped at 10km
            $maxRadius = min($radius, 10) * 1000; // Convert to meters
            $searchQuery->whereDistance('location_coordinates', $userLocation, '<=', $maxRadius);
        }

        // Add text search
        $searchQuery->where(function ($q) use ($query) {
            $q->where('title', 'ILIKE', "%{$query}%")
                ->orWhere('description', 'ILIKE', "%{$query}%")
                ->orWhereRaw("tags::text ILIKE ?", ["%{$query}%"]);
        });

        return $searchQuery
            ->latest()
            ->limit(config('search.limits.posts', 50))
            ->get()
            ->map(fn ($post) => [
                'type' => 'post',
                'data' => $post,
            ]);
    }

    /**
     * Search activities with PostgreSQL ILIKE
     */
    protected function searchActivities(string $query, User $user, ?int $radius): Collection
    {
        $searchQuery = Activity::with(['host', 'tags'])
            ->where('status', 'published')
            ->where('start_time', '>', now())
            ->where(function ($q) use ($query) {
                $q->where('title', 'ILIKE', "%{$query}%")
                    ->orWhere('description', 'ILIKE', "%{$query}%")
                    ->orWhereHas('tags', function ($tagQuery) use ($query) {
                        $tagQuery->where('name', 'ILIKE', "%{$query}%");
                    });
            });

        // Add geo-proximity filter if radius provided
        if ($radius && $user->latitude && $user->longitude) {
            $userLocation = new Point($user->latitude, $user->longitude, 4326);
            $radiusMeters = $radius * 1000; // Convert to meters
            $searchQuery->whereDistance('location_coordinates', $userLocation, '<=', $radiusMeters);
        }

        return $searchQuery
            ->latest('start_time')
            ->limit(config('search.limits.events', 50))
            ->get()
            ->map(fn ($activity) => [
                'type' => 'event',
                'data' => $activity,
            ]);
    }
}

