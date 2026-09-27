<?php

namespace App\Services;

use App\Models\Activity;
use App\Models\Post;
use App\Models\User;
use Illuminate\Support\Collection;
use MatanYadaev\EloquentSpatial\Objects\Point;

class FeedService
{
    /**
     * Get nearby feed mixing posts and events around the user.
     *
     * - Posts: capped at 10km radius
     * - Events: up to provided $radius (typically 25–50km)
     * - Optional keyword search via PostgreSQL ILIKE
     * - Supports pagination for infinite scroll
     */
    public function getNearbyFeed(
        User $user,
        int $radius = 10,
        string $contentType = 'all',
        string $timeFilter = 'all',
        string $searchQuery = '',
        int $page = 1,
        int $perPage = 20
    ): array {
        $userLocation = $user->location_coordinates;

        // If user has no coordinates, just fall back to simple recency-based lists
        if (! $userLocation instanceof Point) {
            return $this->getFallbackNearbyFeed($contentType, $timeFilter, $searchQuery, $page, $perPage);
        }

        $items = collect();
        $totalPosts = 0;
        $totalEvents = 0;

        // Calculate how many of each type to fetch based on content type
        // For 'all', we want a mix, so fetch equal amounts and merge
        $postsPerPage = $contentType === 'events' ? 0 : ($contentType === 'all' ? (int) ceil($perPage / 2) : $perPage);
        $eventsPerPage = $contentType === 'posts' ? 0 : ($contentType === 'all' ? (int) floor($perPage / 2) : $perPage);

        // Posts within 5–10km
        if ($contentType !== 'events') {
            $postsResult = $this->queryNearbyPosts($userLocation, $radius, $timeFilter, $searchQuery, $page, $postsPerPage);
            $posts = $postsResult['items']->map(fn (Post $p) => ['type' => 'post', 'data' => $p]);
            $totalPosts = $postsResult['total'];

            $items = $items->merge($posts);
        }

        // Events within desired radius
        if ($contentType !== 'posts') {
            $eventsResult = $this->queryNearbyEvents($userLocation, $radius, $timeFilter, $searchQuery, $page, $eventsPerPage);
            $events = $eventsResult['items']->map(fn (Activity $e) => ['type' => 'event', 'data' => $e]);
            $totalEvents = $eventsResult['total'];

            $items = $items->merge($events);
        }

        // Sort by temporal relevance – posts get a boost so they show higher
        $sortedItems = $items->sortByDesc(function (array $item) {
            if ($item['type'] === 'post') {
                return $item['data']->created_at->timestamp + 100000; // boost posts
            }

            return $item['data']->start_time->timestamp;
        })->values();

        // Calculate if there are more items
        $totalItems = $totalPosts + $totalEvents;
        $currentlyLoaded = ($page - 1) * $perPage + $sortedItems->count();
        $hasMore = $currentlyLoaded < $totalItems && $currentlyLoaded < 200; // Cap at 200 items

        return [
            'items' => $sortedItems,
            'hasMore' => $hasMore,
            'total' => $totalItems,
            'page' => $page,
        ];
    }

    /**
     * Personalized "For You" feed using RecommendationEngine.
     */
    public function getForYouFeed(User $user): Collection
    {
        $engine = app(RecommendationEngine::class);

        // Candidate sets within fixed radii (10km for posts, 50km for events)
        $userLocation = $user->location_coordinates;

        if ($userLocation instanceof Point) {
            $posts = Post::active()
                ->whereDistance('location_coordinates', $userLocation, '<=', 10000)
                ->get();

            $events = Activity::query()
                ->where('status', 'published')
                ->where('start_time', '>', now())
                ->whereDistance('location_coordinates', $userLocation, '<=', 50000)
                ->get();
        } else {
            // Fallback: no spatial filter
            $posts = Post::active()->limit(50)->get();
            $events = Activity::query()
                ->where('status', 'published')
                ->where('start_time', '>', now())
                ->limit(50)
                ->get();
        }

        $scored = collect();

        /** @var Post $post */
        foreach ($posts as $post) {
            $score = $engine->scoreContent($user, $post);
            $scored->push([
                'type' => 'post',
                'data' => $post,
                'score' => $score,
                'reason' => $engine->getReasonForScore($user, $post),
            ]);
        }

        /** @var Activity $event */
        foreach ($events as $event) {
            $score = $engine->scoreContent($user, $event);
            $scored->push([
                'type' => 'event',
                'data' => $event,
                'score' => $score,
                'reason' => $engine->getReasonForScore($user, $event),
            ]);
        }

        return $scored
            ->sortByDesc('score')
            ->values()
            ->take(50);
    }

    /**
     * Map markers for posts and events around user.
     */
    public function getMapData(
        User $user,
        int $radius = 10,
        string $contentType = 'all'
    ): array {
        $userLocation = $user->location_coordinates;

        if (! $userLocation instanceof Point) {
            // Fallback center (San Francisco-ish) if user has no location
            $userLocation = new Point(37.7749, -122.4194, 4326);
        }

        $markers = [];

        if ($contentType !== 'events') {
            $postsResult = $this->queryNearbyPosts($userLocation, $radius, 'all', '', 1, 100);

            foreach ($postsResult['items'] as $post) {
                $markers[] = [
                    'type' => 'post',
                    'id' => $post->id,
                    'lat' => $post->location_coordinates->latitude,
                    'lng' => $post->location_coordinates->longitude,
                    'title' => $post->title,
                    'timeHint' => $post->time_hint,
                    'reactionCount' => $post->reaction_count,
                    'expiresAt' => optional($post->expires_at)->toIso8601String(),
                ];
            }
        }

        if ($contentType !== 'posts') {
            $eventsResult = $this->queryNearbyEvents($userLocation, $radius, 'all', '', 1, 100);

            foreach ($eventsResult['items'] as $event) {
                $markers[] = [
                    'type' => 'event',
                    'id' => $event->id,
                    'lat' => $event->location_coordinates->latitude,
                    'lng' => $event->location_coordinates->longitude,
                    'title' => $event->title,
                    'startTime' => optional($event->start_time)->toIso8601String(),
                    'priceCents' => $event->price_cents,
                    'spotsRemaining' => $event->max_attendees
                        ? max(0, $event->max_attendees - $event->rsvps()->count())
                        : null,
                    'convertedFromPost' => $event->originated_from_post_id !== null,
                ];
            }
        }

        return [
            'markers' => $markers,
            'center' => [
                'lat' => $userLocation->latitude,
                'lng' => $userLocation->longitude,
            ],
        ];
    }

    /**
     * Internal: query nearby active posts using PostgreSQL spatial queries.
     */
    protected function queryNearbyPosts(
        Point $userLocation,
        int $radiusKm,
        string $timeFilter,
        string $searchQuery = '',
        int $page = 1,
        int $perPage = 20
    ): array {
        $offset = ($page - 1) * $perPage;

        $query = Post::query()
            ->with('user')
            ->where('status', 'active')
            ->whereDistance('location_coordinates', $userLocation, '<=', $radiusKm * 1000);

        $query->when($timeFilter !== 'all', function ($q) use ($timeFilter) {
            $now = now();

            return match ($timeFilter) {
                'today' => $q->where('created_at', '>=', $now->copy()->startOfDay()),
                'week' => $q->where('created_at', '>=', $now->copy()->subWeek()),
                'month' => $q->where('created_at', '>=', $now->copy()->subMonth()),
                default => $q,
            };
        });

        if ($searchQuery !== '') {
            $query->where(function ($q) use ($searchQuery) {
                $q->where('title', 'ILIKE', "%{$searchQuery}%")
                    ->orWhere('description', 'ILIKE', "%{$searchQuery}%")
                    ->orWhereRaw('tags::text ILIKE ?', ["%{$searchQuery}%"]);
            });
        }

        // Count before adding the distance ordering – Postgres rejects ORDER BY
        // on non-aggregated expressions inside a count() query.
        $total = (clone $query)->toBase()->count();

        $posts = $query->orderByDistance('location_coordinates', $userLocation, 'asc')
            ->skip($offset)
            ->take($perPage)
            ->get();

        return [
            'items' => $posts,
            'total' => $total,
        ];
    }

    /**
     * Internal: query nearby upcoming events using PostgreSQL spatial queries.
     */
    protected function queryNearbyEvents(
        Point $userLocation,
        int $radiusKm,
        string $timeFilter,
        string $searchQuery = '',
        int $page = 1,
        int $perPage = 20
    ): array {
        $offset = ($page - 1) * $perPage;

        $query = Activity::query()
            ->with('host')
            ->where('status', 'published')
            ->where('start_time', '>', now())
            ->whereDistance('location_coordinates', $userLocation, '<=', $radiusKm * 1000);

        $query->when($timeFilter !== 'all', function ($q) use ($timeFilter) {
            $now = now();

            return match ($timeFilter) {
                'today' => $q->where('start_time', '>=', $now->copy()->startOfDay()),
                'week' => $q->where('start_time', '>=', $now->copy()->subWeek()),
                'month' => $q->where('start_time', '>=', $now->copy()->subMonth()),
                default => $q,
            };
        });

        if ($searchQuery !== '') {
            $query->where(function ($q) use ($searchQuery) {
                $q->where('title', 'ILIKE', "%{$searchQuery}%")
                    ->orWhere('description', 'ILIKE', "%{$searchQuery}%")
                    ->orWhereHas('tags', fn ($tagQuery) => $tagQuery->where('name', 'ILIKE', "%{$searchQuery}%"));
            });
        }

        // Count before adding the distance ordering – Postgres rejects ORDER BY
        // on non-aggregated expressions inside a count() query.
        $total = (clone $query)->toBase()->count();

        $events = $query->orderByDistance('location_coordinates', $userLocation, 'asc')
            ->skip($offset)
            ->take($perPage)
            ->get();

        return [
            'items' => $events,
            'total' => $total,
        ];
    }

    protected function getFallbackNearbyFeed(string $contentType, string $timeFilter, string $searchQuery = '', int $page = 1, int $perPage = 20): array
    {
        $items = collect();

        if ($contentType !== 'events') {
            $postsQuery = Post::with('user')->active()
                ->when($timeFilter !== 'all', function ($q) use ($timeFilter) {
                    $now = now();

                    return match ($timeFilter) {
                        'today' => $q->whereDate('created_at', $now->toDateString()),
                        'week' => $q->where('created_at', '>=', $now->copy()->subWeek()),
                        'month' => $q->where('created_at', '>=', $now->copy()->subMonth()),
                        default => $q,
                    };
                });

            if (! empty($searchQuery)) {
                $postsQuery->where(function ($q) use ($searchQuery) {
                    $q->where('title', 'ILIKE', "%{$searchQuery}%")
                        ->orWhere('description', 'ILIKE', "%{$searchQuery}%")
                        ->orWhereRaw('tags::text ILIKE ?', ["%{$searchQuery}%"]);
                });
            }

            $posts = $postsQuery->latest()
                ->limit(20)
                ->get()
                ->map(fn (Post $p) => ['type' => 'post', 'data' => $p]);

            $items = $items->merge($posts);
        }

        if ($contentType !== 'posts') {
            $eventsQuery = Activity::with('host')
                ->where('status', 'published')
                ->where('start_time', '>', now())
                ->when($timeFilter !== 'all', function ($q) use ($timeFilter) {
                    $now = now();

                    return match ($timeFilter) {
                        'today' => $q->whereDate('start_time', $now->toDateString()),
                        'week' => $q->where('start_time', '>=', $now->copy()->subWeek()),
                        'month' => $q->where('start_time', '>=', $now->copy()->subMonth()),
                        default => $q,
                    };
                });

            if (! empty($searchQuery)) {
                $eventsQuery->where(function ($q) use ($searchQuery) {
                    $q->where('title', 'ILIKE', "%{$searchQuery}%")
                        ->orWhere('description', 'ILIKE', "%{$searchQuery}%")
                        ->orWhereHas('tags', fn ($tagQuery) => $tagQuery->where('name', 'ILIKE', "%{$searchQuery}%"));
                });
            }

            $events = $eventsQuery->latest('start_time')
                ->limit(20)
                ->get()
                ->map(fn (Activity $e) => ['type' => 'event', 'data' => $e]);

            $items = $items->merge($events);
        }

        // For fallback, we don't have accurate totals, so just return what we have
        // and assume there might be more if we got a full page
        $hasMore = $items->count() >= $perPage && $page < 10; // Cap at 10 pages for fallback

        return [
            'items' => $items->values(),
            'hasMore' => $hasMore,
            'total' => $items->count(), // Approximate
            'page' => $page,
        ];
    }
}
