<?php

use App\Models\Activity;
use App\Models\Post;
use App\Models\User;
use App\Services\FeedService;
use App\Services\RecommendationEngine;
use Illuminate\Support\Facades\App;
use MatanYadaev\EloquentSpatial\Objects\Point;

describe('FeedService', function () {
    it('returns nearby posts and events around a user', function () {
        $user = User::factory()->create([
            'location_coordinates' => new Point(37.7749, -122.4194, 4326),
        ]);

        // Nearby post (within 5km)
        $nearPost = Post::factory()->create([
            'user_id' => $user->id,
            'location_coordinates' => new Point(37.7750, -122.4195, 4326),
            'status' => 'active',
        ]);

        // Far post (~25km away) to ensure the fixed 10km post radius excludes it
        // regardless of the event radius passed below.
        $farPost = Post::factory()->create([
            'user_id' => $user->id,
            'location_coordinates' => new Point(37.95, -122.60, 4326),
            'status' => 'active',
        ]);

        // Nearby event (within 25km)
        $nearEvent = Activity::factory()->create([
            'location_coordinates' => new Point(37.7751, -122.4196, 4326),
            'status' => 'published',
            'start_time' => now()->addDay(),
        ]);

        $service = app(FeedService::class);

        $items = collect($service->getNearbyFeed($user, eventRadiusKm: 25, contentType: 'all', timeFilter: 'all')['items']);

        $types = $items->pluck('type');
        $ids = $items->pluck('id');

        expect($types)->toContain('post')->toContain('event');
        expect($ids)->toContain($nearPost->id)->toContain($nearEvent->id);
        expect($ids)->not->toContain($farPost->id);
    });

    it('uses RecommendationEngine for For You feed', function () {
        $user = User::factory()->create([
            'location_coordinates' => new Point(37.7749, -122.4194, 4326),
            'interests' => ['sports'],
        ]);

        $post = Post::factory()->create([
            'user_id' => $user->id,
            'tags' => ['sports'],
            'location_coordinates' => new Point(37.7750, -122.4195, 4326),
        ]);

        $event = Activity::factory()->create([
            'location_coordinates' => new Point(37.7751, -122.4196, 4326),
            'status' => 'published',
            'start_time' => now()->addDay(),
        ]);

        // Ensure the real RecommendationEngine is bound
        App::forgetInstance(RecommendationEngine::class);

        $service = app(FeedService::class);
        $items = $service->getForYouFeed($user);

        expect($items)->not->toBeEmpty();
        $types = $items->pluck('type');
        expect($types)->toContain('post')->toContain('event');

        $first = $items->first();
        expect($first)->toHaveKeys(['type', 'data', 'score', 'reason']);
    });

    it('returns map markers and center coordinates', function () {
        $user = User::factory()->create([
            'location_coordinates' => new Point(37.7749, -122.4194, 4326),
        ]);

        $post = Post::factory()->create([
            'user_id' => $user->id,
            'location_coordinates' => new Point(37.7750, -122.4195, 4326),
        ]);

        $event = Activity::factory()->create([
            'location_coordinates' => new Point(37.7751, -122.4196, 4326),
            'status' => 'published',
            'start_time' => now()->addDay(),
        ]);

        $service = app(FeedService::class);
        $data = $service->getMapData($user, radius: 10, contentType: 'all');

        expect($data)->toHaveKeys(['markers', 'center']);
        expect($data['center']['lat'])->toBeFloat();
        expect($data['center']['lng'])->toBeFloat();

        $markerTypes = collect($data['markers'])->pluck('type');
        $markerIds = collect($data['markers'])->pluck('id');

        expect($markerTypes)->toContain('post')->toContain('event');
        expect($markerIds)->toContain($post->id)->toContain($event->id);
    });

    it('respects the fixed post radius and the clamped event radius, excluding expired posts (C-07)', function () {
        $user = User::factory()->create([
            'location_coordinates' => new Point(37.7749, -122.4194, 4326),
        ]);

        // Within 10km post radius.
        $nearPost = Post::factory()->create([
            'user_id' => $user->id,
            'location_coordinates' => new Point(37.7750, -122.4195, 4326),
            'status' => 'active',
            'expires_at' => now()->addDay(),
        ]);

        // ~42km away — beyond the fixed 10km post radius, must be excluded
        // even though it's within the event radius ceiling.
        $farPost = Post::factory()->create([
            'user_id' => $user->id,
            'location_coordinates' => new Point(38.10, -122.80, 4326),
            'status' => 'active',
            'expires_at' => now()->addDay(),
        ]);

        // Expired post, otherwise within radius — must be excluded.
        $expiredPost = Post::factory()->create([
            'user_id' => $user->id,
            'location_coordinates' => new Point(37.7750, -122.4195, 4326),
            'status' => 'expired',
            'expires_at' => now()->subHour(),
        ]);

        // Within requested 20km event radius.
        $nearEvent = Activity::factory()->create([
            'location_coordinates' => new Point(37.80, -122.45, 4326),
            'status' => 'published',
            'start_time' => now()->addDay(),
        ]);

        // ~61km away — beyond the 50km event radius ceiling, must be
        // excluded even if a caller requests eventRadiusKm > 50 (clamped).
        $farEvent = Activity::factory()->create([
            'location_coordinates' => new Point(38.30, -122.90, 4326),
            'status' => 'published',
            'start_time' => now()->addDay(),
        ]);

        $service = app(FeedService::class);

        $result = $service->getNearbyFeed($user, eventRadiusKm: 999, contentType: 'all', timeFilter: 'all');
        $ids = collect($result['items'])->pluck('id');

        expect($ids)->toContain($nearPost->id)
            ->toContain($nearEvent->id)
            ->not->toContain($farPost->id)
            ->not->toContain($expiredPost->id)
            ->not->toContain($farEvent->id);
    });

    it('pages a seeded dataset with no duplicates or gaps, newest first (C-06)', function () {
        $user = User::factory()->create([
            'location_coordinates' => new Point(37.7749, -122.4194, 4326),
        ]);

        $posts = Post::factory()->count(12)->sequence(fn ($sequence) => [
            'created_at' => now()->subMinutes(12 - $sequence->index),
        ])->create([
            'user_id' => $user->id,
            'location_coordinates' => new Point(37.7750, -122.4195, 4326),
            'status' => 'active',
            'expires_at' => now()->addDay(),
        ]);

        $service = app(FeedService::class);

        $seen = collect();
        $cursor = null;
        $pages = 0;

        do {
            $result = $service->getNearbyFeed($user, cursor: $cursor, contentType: 'posts', perPage: 5);
            $seen = $seen->merge(collect($result['items'])->pluck('id'));
            $cursor = $result['nextCursor'];
            $pages++;
        } while ($result['hasMore'] && $pages < 10);

        expect($seen->unique())->toHaveCount($posts->count());
        expect($seen->sort()->values())->toEqual($posts->pluck('id')->sort()->values());

        $expectedOrder = $posts->sortByDesc('created_at')->pluck('id')->values();
        expect($seen->values())->toEqual($expectedOrder);
    });

    it('never exposes raw coordinates in the feed payload (C-08)', function () {
        $user = User::factory()->create([
            'location_coordinates' => new Point(37.7749, -122.4194, 4326),
        ]);

        Post::factory()->create([
            'user_id' => $user->id,
            'location_coordinates' => new Point(37.7750, -122.4195, 4326),
            'status' => 'active',
            'expires_at' => now()->addDay(),
        ]);

        Activity::factory()->create([
            'location_coordinates' => new Point(37.7751, -122.4196, 4326),
            'status' => 'published',
            'start_time' => now()->addDay(),
        ]);

        $service = app(FeedService::class);
        $result = $service->getNearbyFeed($user, contentType: 'all');

        foreach ($result['items'] as $item) {
            expect($item)->not->toHaveKeys(['latitude', 'longitude', 'lat', 'lng', 'location_coordinates']);
        }
    });
});
