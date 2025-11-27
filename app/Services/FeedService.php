<?php

namespace App\Services;

use App\Models\Activity;
use App\Models\Post;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Collection;
use MatanYadaev\EloquentSpatial\Objects\Point;

class FeedService
{
    /**
     * Get nearby feed mixing posts and events around the user.
     *
     * - Posts: capped at 10km radius
     * - Events: up to provided $radius (typically 25–50km)
     * - Optional keyword search via Meilisearch
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
     * Internal: query nearby active posts using Meilisearch.
     */
    protected function queryNearbyPosts(
        Point $userLocation,
        int $radiusKm,
        string $timeFilter,
        string $searchQuery = '',
        int $page = 1,
        int $perPage = 20
    ): array {
        $radiusMeters = $radiusKm * 1000;
        $offset = ($page - 1) * $perPage;

        // Build Meilisearch filters
        $filters = ['status = active'];

        // Add geo filter
        $filters[] = "_geoRadius({$userLocation->latitude}, {$userLocation->longitude}, {$radiusMeters})";

        // Add time filter if needed
        if ($timeFilter !== 'all') {
            $now = now();
            $timestamp = match ($timeFilter) {
                'today' => $now->copy()->startOfDay()->timestamp,
                'week' => $now->copy()->subWeek()->timestamp,
                'month' => $now->copy()->subMonth()->timestamp,
                default => null,
            };

            if ($timestamp) {
                $filters[] = "created_at >= {$timestamp}";
            }
        }

        // Search using Meilisearch with optional keyword
        $results = Post::search($searchQuery, function ($meilisearch, $query, $options) use ($filters, $userLocation, $offset, $perPage) {
            $options['filter'] = implode(' AND ', $filters);
            $options['sort'] = ['_geoPoint('.$userLocation->latitude.', '.$userLocation->longitude.'):asc'];
            $options['limit'] = $perPage;
            $options['offset'] = $offset;

            return $meilisearch->search($query, $options);
        })->raw();

        // Extract IDs and load from database to get full Eloquent models with user relationship
        $ids = collect($results['hits'] ?? [])->pluck('id')->toArray();

        if (empty($ids)) {
            return [
                'items' => new EloquentCollection,
                'total' => 0,
            ];
        }

        $posts = Post::with('user')->whereIn('id', $ids)
            ->get()
            ->sortBy(function ($post) use ($ids) {
                return array_search($post->id, $ids);
            })
            ->values();

        return [
            'items' => $posts,
            'total' => $results['estimatedTotalHits'] ?? 0,
        ];
    }

    /**
     * Internal: query nearby upcoming events using Meilisearch.
     */
    protected function queryNearbyEvents(
        Point $userLocation,
        int $radiusKm,
        string $timeFilter,
        string $searchQuery = '',
        int $page = 1,
        int $perPage = 20
    ): array {
        $radiusMeters = $radiusKm * 1000;
        $offset = ($page - 1) * $perPage;

        // Build Meilisearch filters
        $filters = [
            'status = published',
            'start_time > '.now()->timestamp,
        ];

        // Add geo filter
        $filters[] = "_geoRadius({$userLocation->latitude}, {$userLocation->longitude}, {$radiusMeters})";

        // Add time filter if needed
        if ($timeFilter !== 'all') {
            $now = now();
            $timestamp = match ($timeFilter) {
                'today' => $now->copy()->startOfDay()->timestamp,
                'week' => $now->copy()->subWeek()->timestamp,
                'month' => $now->copy()->subMonth()->timestamp,
                default => null,
            };

            if ($timestamp) {
                $filters[] = "start_time >= {$timestamp}";
            }
        }

        // Search using Meilisearch with optional keyword
        $results = Activity::search($searchQuery, function ($meilisearch, $query, $options) use ($filters, $userLocation, $offset, $perPage) {
            $options['filter'] = implode(' AND ', $filters);
            $options['sort'] = ['_geoPoint('.$userLocation->latitude.', '.$userLocation->longitude.'):asc'];
            $options['limit'] = $perPage;
            $options['offset'] = $offset;

            return $meilisearch->search($query, $options);
        })->raw();

        // Extract IDs and load from database to get full Eloquent models with user relationship
        $ids = collect($results['hits'] ?? [])->pluck('id')->toArray();

        if (empty($ids)) {
            return [
                'items' => new EloquentCollection,
                'total' => 0,
            ];
        }

        $events = Activity::with('host')->whereIn('id', $ids)
            ->get()
            ->sortBy(function ($activity) use ($ids) {
                return array_search($activity->id, $ids);
            })
            ->values();

        return [
            'items' => $events,
            'total' => $results['estimatedTotalHits'] ?? 0,
        ];
    }

    protected function getFallbackNearbyFeed(string $contentType, string $timeFilter, string $searchQuery = '', int $page = 1, int $perPage = 20): array
    {
        $items = collect();

        if ($contentType !== 'events') {
            // Use Meilisearch for keyword search even in fallback mode
            if (! empty($searchQuery)) {
                $results = Post::search($searchQuery, function ($meilisearch, $query, $options) use ($timeFilter) {
                    $filters = ['status = active'];

                    if ($timeFilter !== 'all') {
                        $now = now();
                        $timestamp = match ($timeFilter) {
                            'today' => $now->copy()->startOfDay()->timestamp,
                            'week' => $now->copy()->subWeek()->timestamp,
                            'month' => $now->copy()->subMonth()->timestamp,
                            default => null,
                        };
                        if ($timestamp) {
                            $filters[] = "created_at >= {$timestamp}";
                        }
                    }

                    $options['filter'] = implode(' AND ', $filters);
                    $options['limit'] = 20;

                    return $meilisearch->search($query, $options);
                })->raw();

                $ids = collect($results['hits'] ?? [])->pluck('id')->toArray();
                $posts = Post::with('user')->whereIn('id', $ids)->get()
                    ->sortBy(fn ($post) => array_search($post->id, $ids))
                    ->values()
                    ->map(fn (Post $p) => ['type' => 'post', 'data' => $p]);
            } else {
                $posts = Post::with('user')->active()
                    ->when($timeFilter !== 'all', function ($q) use ($timeFilter) {
                        $now = now();

                        return match ($timeFilter) {
                            'today' => $q->whereDate('created_at', $now->toDateString()),
                            'week' => $q->where('created_at', '>=', $now->copy()->subWeek()),
                            'month' => $q->where('created_at', '>=', $now->copy()->subMonth()),
                            default => $q,
                        };
                    })
                    ->latest()
                    ->limit(20)
                    ->get()
                    ->map(fn (Post $p) => ['type' => 'post', 'data' => $p]);
            }

            $items = $items->merge($posts);
        }

        if ($contentType !== 'posts') {
            // Use Meilisearch for keyword search even in fallback mode
            if (! empty($searchQuery)) {
                $results = Activity::search($searchQuery, function ($meilisearch, $query, $options) use ($timeFilter) {
                    $filters = [
                        'status = published',
                        'start_time > '.now()->timestamp,
                    ];

                    if ($timeFilter !== 'all') {
                        $now = now();
                        $timestamp = match ($timeFilter) {
                            'today' => $now->copy()->startOfDay()->timestamp,
                            'week' => $now->copy()->subWeek()->timestamp,
                            'month' => $now->copy()->subMonth()->timestamp,
                            default => null,
                        };
                        if ($timestamp) {
                            $filters[] = "start_time >= {$timestamp}";
                        }
                    }

                    $options['filter'] = implode(' AND ', $filters);
                    $options['limit'] = 20;

                    return $meilisearch->search($query, $options);
                })->raw();

                $ids = collect($results['hits'] ?? [])->pluck('id')->toArray();
                $events = Activity::with('host')->whereIn('id', $ids)->get()
                    ->sortBy(fn ($event) => array_search($event->id, $ids))
                    ->values()
                    ->map(fn (Activity $e) => ['type' => 'event', 'data' => $e]);
            } else {
                $events = Activity::with('host')
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
                    })
                    ->latest('start_time')
                    ->limit(20)
                    ->get()
                    ->map(fn (Activity $e) => ['type' => 'event', 'data' => $e]);
            }

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
