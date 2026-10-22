<?php

namespace App\Services;

use App\Models\Activity;
use App\Models\Post;
use App\Models\PostReaction;
use App\Models\User;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use MatanYadaev\EloquentSpatial\Objects\Point;

class FeedService
{
    /**
     * Posts always use this radius. Not a parameter — callers cannot widen it
     * (spec 001, 001-O2).
     */
    public const POST_RADIUS_KM = 10;

    public const EVENT_RADIUS_MIN_KM = 10;

    public const EVENT_RADIUS_MAX_KM = 50;

    private const EVENT_RADIUS_DEFAULT_KM = 50;

    private const ITEM_CAP = 200;

    /**
     * Get the merged nearby feed (posts + events), newest first, cursor-paginated.
     *
     * - Posts: fixed 10km radius.
     * - Events: 10–50km, clamped server-side regardless of client input.
     * - Optional keyword search via PostgreSQL ILIKE.
     * - Items are plain arrays (never Eloquent models) so Livewire never
     *   rehydrates them from the database on every interaction.
     *
     * @return array{items: list<array<string, mixed>>, nextCursor: ?string, hasMore: bool}
     */
    public function getNearbyFeed(
        User $user,
        ?string $cursor = null,
        string $contentType = 'all',
        string $timeFilter = 'all',
        string $searchQuery = '',
        int $eventRadiusKm = self::EVENT_RADIUS_DEFAULT_KM,
        int $perPage = 30
    ): array {
        $userLocation = $user->location_coordinates;

        if (! $userLocation instanceof Point) {
            return $this->getFallbackNearbyFeed($user, $contentType, $timeFilter, $searchQuery, $cursor, $perPage);
        }

        $eventRadiusKm = max(self::EVENT_RADIUS_MIN_KM, min(self::EVENT_RADIUS_MAX_KM, $eventRadiusKm));
        $cursorData = $this->decodeCursor($cursor);

        $postQuery = $contentType === 'events' ? null : $this->applySpatialFilter(
            $this->basePostQuery($timeFilter, $searchQuery),
            $userLocation,
            self::POST_RADIUS_KM * 1000
        );

        $eventQuery = $contentType === 'posts' ? null : $this->applySpatialFilter(
            $this->baseEventQuery($timeFilter, $searchQuery),
            $userLocation,
            $eventRadiusKm * 1000
        );

        return $this->paginateMerged($user, $postQuery, $eventQuery, $cursorData, $perPage);
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
                ->whereDistanceSphere('location_coordinates', $userLocation, '<=', 10000)
                ->get();

            $events = Activity::query()
                ->discoverable()
                ->where('status', 'published')
                ->where('start_time', '>', now())
                ->whereDistanceSphere('location_coordinates', $userLocation, '<=', 50000)
                ->get();
        } else {
            // Fallback: no spatial filter
            $posts = Post::active()->limit(50)->get();
            $events = Activity::query()
                ->discoverable()
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
            ->whereDistanceSphere('location_coordinates', $userLocation, '<=', $radiusKm * 1000);

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

        $posts = $query->orderByDistanceSphere('location_coordinates', $userLocation, 'asc')
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
            ->discoverable()
            ->with(['host', 'group'])
            ->where('status', 'published')
            ->where('start_time', '>', now())
            ->whereDistanceSphere('location_coordinates', $userLocation, '<=', $radiusKm * 1000);

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

        $events = $query->orderByDistanceSphere('location_coordinates', $userLocation, 'asc')
            ->skip($offset)
            ->take($perPage)
            ->get();

        return [
            'items' => $events,
            'total' => $total,
        ];
    }

    /**
     * Fallback feed for a viewer with no saved location: plain recency lists,
     * same cursor/array contract as the spatial path, just no radius filter.
     *
     * @return array{items: list<array<string, mixed>>, nextCursor: ?string, hasMore: bool}
     */
    protected function getFallbackNearbyFeed(User $user, string $contentType, string $timeFilter, string $searchQuery = '', ?string $cursor = null, int $perPage = 30): array
    {
        $postQuery = $contentType === 'events' ? null : $this->basePostQuery($timeFilter, $searchQuery);
        $eventQuery = $contentType === 'posts' ? null : $this->baseEventQuery($timeFilter, $searchQuery);

        return $this->paginateMerged($user, $postQuery, $eventQuery, $this->decodeCursor($cursor), $perPage);
    }

    /**
     * Posts matching status/time/search filters — no radius, no order, no limit.
     *
     * @return Builder<Post>
     */
    private function basePostQuery(string $timeFilter, string $searchQuery): Builder
    {
        $query = Post::query()
            ->with(['user', 'group'])
            ->active();

        $query->when($timeFilter !== 'all', function (Builder $q) use ($timeFilter) {
            $now = now();

            return match ($timeFilter) {
                'today' => $q->where('created_at', '>=', $now->copy()->startOfDay()),
                'week' => $q->where('created_at', '>=', $now->copy()->subWeek()),
                'month' => $q->where('created_at', '>=', $now->copy()->subMonth()),
                default => $q,
            };
        });

        if ($searchQuery !== '') {
            $query->where(function (Builder $q) use ($searchQuery) {
                $q->where('title', 'ILIKE', "%{$searchQuery}%")
                    ->orWhere('description', 'ILIKE', "%{$searchQuery}%")
                    ->orWhereRaw('tags::text ILIKE ?', ["%{$searchQuery}%"]);
            });
        }

        return $query;
    }

    /**
     * Published, upcoming events matching time/search filters — no radius,
     * no order, no limit.
     *
     * @return Builder<Activity>
     */
    private function baseEventQuery(string $timeFilter, string $searchQuery): Builder
    {
        $query = Activity::query()
            ->discoverable()
            ->with(['host', 'group'])
            ->where('status', 'published')
            ->where('start_time', '>', now());

        $query->when($timeFilter !== 'all', function (Builder $q) use ($timeFilter) {
            $now = now();

            return match ($timeFilter) {
                'today' => $q->where('start_time', '>=', $now->copy()->startOfDay()),
                'week' => $q->where('start_time', '>=', $now->copy()->subWeek()),
                'month' => $q->where('start_time', '>=', $now->copy()->subMonth()),
                default => $q,
            };
        });

        if ($searchQuery !== '') {
            $query->where(function (Builder $q) use ($searchQuery) {
                $q->where('title', 'ILIKE', "%{$searchQuery}%")
                    ->orWhere('description', 'ILIKE', "%{$searchQuery}%")
                    ->orWhereHas('tags', fn ($tagQuery) => $tagQuery->where('name', 'ILIKE', "%{$searchQuery}%"));
            });
        }

        return $query;
    }

    /**
     * Add an ST_DWithin radius filter plus a selected ST_Distance column
     * (exposed to the caller as `distance_km`). ST_DWithin can use the
     * existing GIST index on `location_coordinates`; whereDistanceSphere
     * cannot (spec 001 archaeology).
     *
     * @template TModel of \Illuminate\Database\Eloquent\Model
     *
     * @param  Builder<TModel>  $query
     * @return Builder<TModel>
     */
    private function applySpatialFilter(Builder $query, Point $point, int $meters): Builder
    {
        $grammar = DB::connection()->getQueryGrammar();
        $pointSql = $point->toSqlExpression(DB::connection())->getValue($grammar);

        return $query
            ->addSelect(['*', DB::raw("ST_Distance(location_coordinates, {$pointSql}) / 1000 AS distance_km")])
            ->whereRaw("ST_DWithin(location_coordinates, {$pointSql}, ?)", [$meters]);
    }

    /**
     * Keyset-paginate two independently filtered queries (posts, events) as
     * one merged, newest-first list. Fetches perPage+1 from each source so
     * `hasMore` is exact with no extra COUNT(*) query.
     *
     * @param  Builder<Post>|null  $postQuery
     * @param  Builder<Activity>|null  $eventQuery
     * @param  array{t: string, id: string}|null  $cursorData
     * @return array{items: list<array<string, mixed>>, nextCursor: ?string, hasMore: bool}
     */
    private function paginateMerged(?User $user, ?Builder $postQuery, ?Builder $eventQuery, ?array $cursorData, int $perPage): array
    {
        $fetchLimit = $perPage + 1;

        $posts = $postQuery
            ? $this->applyCursor($postQuery, $cursorData)->orderByDesc('created_at')->orderByDesc('id')->limit($fetchLimit)->get()
            : collect();

        $events = $eventQuery
            ? $this->applyCursor($eventQuery, $cursorData)->withCount('rsvps')->orderByDesc('created_at')->orderByDesc('id')->limit($fetchLimit)->get()
            : collect();

        $viewerReactedIds = $this->viewerReactedPostIds($user, $posts);

        $merged = $posts->toBase()->map(fn (Post $p) => $this->mapPostToFeedItem($p, $viewerReactedIds))
            ->merge($events->toBase()->map(fn (Activity $e) => $this->mapEventToFeedItem($e)))
            ->sort(fn (array $a, array $b) => $b['created_at'] <=> $a['created_at'] ?: strcmp($b['id'], $a['id']))
            ->values();

        $poolSize = $merged->count();
        $page = $merged->take(min($perPage, self::ITEM_CAP))->values();

        return [
            'items' => array_values($page->all()),
            'nextCursor' => $page->isNotEmpty() ? $this->encodeCursor($page->last()) : null,
            'hasMore' => $poolSize > $page->count(),
        ];
    }

    /**
     * @template TModel of \Illuminate\Database\Eloquent\Model
     *
     * @param  Builder<TModel>  $query
     * @param  array{t: string, id: string}|null  $cursorData
     * @return Builder<TModel>
     */
    private function applyCursor(Builder $query, ?array $cursorData): Builder
    {
        if ($cursorData === null) {
            return $query;
        }

        $cursorTime = Carbon::parse($cursorData['t']);

        return $query->where(function (Builder $q) use ($cursorTime, $cursorData) {
            $q->where('created_at', '<', $cursorTime)
                ->orWhere(function (Builder $q2) use ($cursorTime, $cursorData) {
                    $q2->where('created_at', '=', $cursorTime)->where('id', '<', $cursorData['id']);
                });
        });
    }

    /**
     * @param  array<string, mixed>  $item
     */
    private function encodeCursor(array $item): string
    {
        return base64_encode((string) json_encode(['t' => $item['created_at'], 'id' => $item['id']]));
    }

    /**
     * @return ?array{t: string, id: string}
     */
    private function decodeCursor(?string $cursor): ?array
    {
        if ($cursor === null || $cursor === '') {
            return null;
        }

        $decoded = base64_decode($cursor, true);
        if ($decoded === false) {
            return null;
        }

        $data = json_decode($decoded, true);
        if (! is_array($data) || ! isset($data['t'], $data['id']) || ! is_string($data['t']) || ! is_string($data['id'])) {
            return null;
        }

        return ['t' => $data['t'], 'id' => $data['id']];
    }

    /**
     * One query for every loaded post's reaction state — never one per card.
     *
     * @param  Collection<int, Post>  $posts
     * @return Collection<int, string>
     */
    private function viewerReactedPostIds(?User $user, Collection $posts): Collection
    {
        if (! $user || $posts->isEmpty()) {
            return collect();
        }

        return PostReaction::query()
            ->where('user_id', $user->id)
            ->where('reaction_type', 'im_down')
            ->whereIn('post_id', $posts->pluck('id'))
            ->pluck('post_id');
    }

    /**
     * @param  Collection<int, string>  $viewerReactedIds
     * @return array<string, mixed>
     */
    private function mapPostToFeedItem(Post $post, Collection $viewerReactedIds): array
    {
        $group = $post->posted_as_group ? $post->group : null;
        $isGroup = $group !== null;
        $user = $post->user;

        return [
            'type' => 'post',
            'id' => $post->id,
            'user_id' => $post->user_id,
            'title' => $post->title,
            'description' => $post->description,
            'author_name' => $group !== null ? $group->name : ($user->display_name ?? $user->username ?? 'Unknown User'),
            'author_image_url' => $group !== null ? $group->avatar_url : $user->profile_image_url,
            'author_url' => $group !== null ? route('groups.show', $group) : route('profile.view', $user->username),
            'is_group_post' => $isGroup,
            'location_name' => $post->location_name,
            'distance_km' => isset($post->distance_km) ? round((float) $post->distance_km, 1) : null,
            'created_at' => $post->created_at->toIso8601String(),
            'created_at_human' => $post->created_at->diffForHumans(syntax: CarbonInterface::DIFF_ABSOLUTE).' ago',
            'approximate_time_human' => optional($post->approximate_time)->format('M j, g:i A'),
            'mood' => $post->mood,
            'tags' => $post->tags ?? [],
            'time_hint' => $post->time_hint,
            'expires_at' => optional($post->expires_at)->toIso8601String(),
            'expires_in_human' => $post->expires_at->diffForHumans(syntax: CarbonInterface::DIFF_ABSOLUTE),
            'status' => $post->status,
            'reaction_count' => $post->reaction_count,
            'viewer_reacted' => $viewerReactedIds->contains($post->id),
            'conversion_eligible' => $post->isEligibleForConversion(),
            'converted_activity_id' => $post->converted_to_activity_id,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function mapEventToFeedItem(Activity $event): array
    {
        $rsvpCount = (int) ($event->rsvps_count ?? 0);
        $spotsRemaining = $event->max_attendees !== null ? max(0, $event->max_attendees - $rsvpCount) : null;

        return [
            'type' => 'event',
            'id' => $event->id,
            'title' => $event->title,
            'author_name' => $event->host->display_name ?? $event->host->username ?? 'Unknown Host',
            'author_image_url' => $event->host->profile_image_url,
            'location_name' => $event->location_name,
            'distance_km' => isset($event->distance_km) ? round((float) $event->distance_km, 1) : null,
            'created_at' => $event->created_at->toIso8601String(),
            'created_at_human' => $event->created_at->diffForHumans(syntax: CarbonInterface::DIFF_ABSOLUTE).' ago',
            'images' => $event->images ?? [],
            'start_time' => optional($event->start_time)->toIso8601String(),
            'start_time_human' => optional($event->start_time)->format('M j, g:i A'),
            'is_paid' => $event->is_paid,
            'price' => $event->is_paid ? round(($event->price_cents ?? 0) / 100, 2) : null,
            'rsvp_count' => $rsvpCount,
            'max_attendees' => $event->max_attendees,
            'spots_remaining' => $spotsRemaining,
            'converted_from_post' => $event->originated_from_post_id !== null,
            'group_name' => $event->group?->name,
            'group_url' => $event->group ? route('groups.show', $event->group) : null,
        ];
    }
}
