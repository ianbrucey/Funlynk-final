<?php

use App\Models\Activity;
use App\Models\Post;
use App\Models\User;
use App\Services\FeedService;
use App\Services\RecommendationEngine;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\DB;
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

        // Far post (~8km away) to ensure spatial filter is applied but still out of 5km bucket
        $farPost = Post::factory()->create([
            'user_id' => $user->id,
            'location_coordinates' => new Point(37.84, -122.45, 4326),
            'status' => 'active',
        ]);

        // Nearby event (within 25km)
        $nearEvent = Activity::factory()->create([
            'location_coordinates' => new Point(37.7751, -122.4196, 4326),
            'status' => 'published',
            'start_time' => now()->addDay(),
        ]);

        $service = app(FeedService::class);

        $items = $service->getNearbyFeed($user, radius: 25, contentType: 'all', timeFilter: 'all');

        $types = $items->pluck('type');
        $ids = $items->pluck('data.id');

        expect($types)->toContain('post')->toContain('event');
        expect($ids)->toContain($nearPost->id)->toContain($nearEvent->id);
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
});
