<?php

use App\Models\Activity;
use App\Models\Group;
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

    it('shows public-group events in discovery but never private-group events', function () {
        $user = User::factory()->create([
            'location_coordinates' => new Point(37.7749, -122.4194, 4326),
        ]);
        $make = fn (Group $group) => Activity::factory()->create([
            'group_id' => $group->id,
            'location_coordinates' => new Point(37.7751, -122.4196, 4326),
            'status' => 'published',
            'start_time' => now()->addDay(),
        ]);

        $publicEvent = $make(Group::factory()->create(['privacy' => 'public']));
        $privateEvent = $make(Group::factory()->create(['privacy' => 'private']));

        $feed = app(FeedService::class)->getNearbyFeed($user, eventRadiusKm: 25, contentType: 'all', timeFilter: 'all');
        $items = collect($feed['items']);

        expect($items->pluck('id'))->toContain($publicEvent->id)->not->toContain($privateEvent->id);
        expect($items->firstWhere('id', $publicEvent->id)['group_name'])->toBe($publicEvent->group->name);

        $forYou = app(FeedService::class)->getForYouFeed($user)->pluck('data.id');
        expect($forYou)->toContain($publicEvent->id)->not->toContain($privateEvent->id);

        $publicEvent->group->update(['privacy' => 'private']);
        $after = collect(app(FeedService::class)->getNearbyFeed($user, eventRadiusKm: 25, contentType: 'all', timeFilter: 'all')['items']);
        expect($after->pluck('id'))->not->toContain($publicEvent->id);
    });

    it('never shows group posts (public or private) in the nearby feed, For You, map or search', function () {
        $viewer = User::factory()->create(['location_coordinates' => new Point(37.7749, -122.4194, 4326)]);
        $author = User::factory()->create();
        $make = fn (?Group $group, string $title) => Post::factory()->create([
            'user_id' => $author->id, 'group_id' => $group?->id, 'title' => $title, 'status' => 'active',
            'expires_at' => now()->addDay(), 'location_coordinates' => new Point(37.7750, -122.4195, 4326),
        ]);
        $plain = $make(null, 'Zed plain post');
        $public = $make(Group::factory()->create(['privacy' => 'public']), 'Zed public group post');
        $private = $make(Group::factory()->create(['privacy' => 'private']), 'Zed private group post');

        $service = app(FeedService::class);
        $nearby = collect($service->getNearbyFeed($viewer, contentType: 'posts')['items'])->pluck('id');
        $forYou = $service->getForYouFeed($viewer)->pluck('data.id');
        $markers = collect($service->getMapData($viewer, radius: 10, contentType: 'posts')['markers'])->pluck('id');
        $search = app(\App\Services\DatabaseSearchService::class)->search('zed', $viewer)->pluck('data.id');

        foreach ([$nearby, $forYou, $markers, $search] as $ids) {
            expect($ids)->toContain($plain->id)->not->toContain($public->id)->not->toContain($private->id);
        }
        expect($private->shouldBeSearchable())->toBeFalse()->and($public->shouldBeSearchable())->toBeFalse()->and($plain->shouldBeSearchable())->toBeTrue();
    });

    describe('following feed (spec 010)', function () {
        function followingFixture(array $viewerAttrs = []): array
        {
            $viewer = User::factory()->create($viewerAttrs + ['onboarding_completed_at' => now(), 'location_coordinates' => new Point(37.7749, -122.4194, 4326)]);
            $followed = User::factory()->create();
            \App\Models\Follow::create(['follower_id' => $viewer->id, 'following_id' => $followed->id]);

            return [$viewer, $followed, User::factory()->create()];
        }

        function followingPost(User $author, array $attrs = []): Post
        {
            return Post::factory()->create($attrs + ['user_id' => $author->id, 'status' => 'active', 'expires_at' => now()->addDay(), 'location_coordinates' => new Point(37.7750, -122.4195, 4326)]);
        }

        function followingEvent(User $host, array $attrs = []): Activity
        {
            return Activity::factory()->create($attrs + ['host_id' => $host->id, 'status' => 'published', 'is_public' => true, 'start_time' => now()->addDay(), 'location_coordinates' => new Point(37.7751, -122.4196, 4326)]);
        }

        function followingIds(User $viewer, array $args = []): \Illuminate\Support\Collection
        {
            return collect(app(FeedService::class)->getFollowingFeed($viewer, ...$args)['items'])->pluck('id');
        }

        it('only includes posts and events from followed authors', function () {
            [$viewer, $followed, $stranger] = followingFixture();
            $post = followingPost($followed);
            $event = followingEvent($followed);
            $strangerPost = followingPost($stranger);
            $strangerEvent = followingEvent($stranger);

            $ids = followingIds($viewer);

            expect($ids)->toContain($post->id)->toContain($event->id)->not->toContain($strangerPost->id)->not->toContain($strangerEvent->id);
        });

        it('respects group privacy', function () {
            [$viewer, $followed] = followingFixture();
            $public = Group::factory()->create(['privacy' => 'public']);
            $private = Group::factory()->create(['privacy' => 'private']);
            $groupPost = followingPost($followed, ['group_id' => $public->id]);
            $asGroup = followingPost($followed, ['group_id' => $public->id, 'posted_as_group' => true]);
            $publicEvent = followingEvent($followed, ['group_id' => $public->id, 'is_public' => false]);
            $privateEvent = followingEvent($followed, ['group_id' => $private->id, 'is_public' => false]);

            $ids = followingIds($viewer);

            expect($ids)->toContain($publicEvent->id)->not->toContain($privateEvent->id)->not->toContain($groupPost->id)->not->toContain($asGroup->id);
        });

        it('applies a 50 km radius, and none without a saved location', function () {
            [$viewer, $followed] = followingFixture();
            $near = followingPost($followed, ['location_coordinates' => new Point(37.9449, -122.4194, 4326)]);
            $far = followingPost($followed, ['location_coordinates' => new Point(38.4949, -122.4194, 4326)]);

            expect(followingIds($viewer))->toContain($near->id)->not->toContain($far->id);

            [$noLocation, $followedToo] = followingFixture(['location_coordinates' => null]);
            $farToo = followingPost($followedToo, ['location_coordinates' => new Point(48.85, 2.35, 4326)]);

            expect(followingIds($noLocation))->toContain($farToo->id);
        });

        it('excludes expired posts and past or cancelled events', function () {
            [$viewer, $followed] = followingFixture();
            $expired = followingPost($followed, ['status' => 'expired', 'expires_at' => now()->subDay()]);
            $past = followingEvent($followed, ['start_time' => now()->subDay()]);
            $cancelled = followingEvent($followed, ['status' => 'cancelled']);
            $live = followingPost($followed);

            expect(followingIds($viewer))->toContain($live->id)->not->toContain($expired->id)->not->toContain($past->id)->not->toContain($cancelled->id);
        });

        it('supports content type, and pages without gaps or duplicates', function () {
            [$viewer, $followed] = followingFixture();
            $created = collect(range(1, 4))->map(fn () => followingPost($followed))->merge(collect(range(1, 3))->map(fn () => followingEvent($followed)));
            $service = app(FeedService::class);

            $seen = collect();
            $cursor = null;
            do {
                $page = $service->getFollowingFeed($viewer, cursor: $cursor, perPage: 3);
                $seen = $seen->merge(collect($page['items'])->pluck('id'));
                $cursor = $page['nextCursor'];
            } while ($page['hasMore']);

            expect($seen)->toHaveCount(7)->and($seen->unique())->toHaveCount(7);
            expect(collect($service->getFollowingFeed($viewer, contentType: 'events')['items'])->pluck('type')->unique()->all())->toBe(['event']);
        });

        it('uses a constant number of queries however many authors are followed', function () {
            $count = function (int $authors) {
                [$viewer] = followingFixture();
                foreach (range(1, $authors) as $i) {
                    $author = User::factory()->create();
                    \App\Models\Follow::create(['follower_id' => $viewer->id, 'following_id' => $author->id]);
                    followingPost($author);
                    followingEvent($author);
                }
                \Illuminate\Support\Facades\DB::flushQueryLog();
                \Illuminate\Support\Facades\DB::enableQueryLog();
                app(FeedService::class)->getFollowingFeed($viewer);
                $n = count(\Illuminate\Support\Facades\DB::getQueryLog());
                \Illuminate\Support\Facades\DB::disableQueryLog();

                return $n;
            };

            expect($count(10))->toBe($count(2));
        });
    });

    it('merges cleanly when events match but no posts do (and vice versa)', function () {
        $user = User::factory()->create([
            'location_coordinates' => new Point(37.7749, -122.4194, 4326),
        ]);

        $event = Activity::factory()->create([
            'location_coordinates' => new Point(37.7751, -122.4196, 4326),
            'status' => 'published',
            'start_time' => now()->addDay(),
        ]);

        $service = app(FeedService::class);

        $eventsOnly = $service->getNearbyFeed($user, eventRadiusKm: 25, contentType: 'all', timeFilter: 'all');
        expect(collect($eventsOnly['items'])->pluck('id'))->toContain($event->id);

        Activity::query()->delete();
        $post = Post::factory()->create([
            'user_id' => $user->id,
            'location_coordinates' => new Point(37.7750, -122.4195, 4326),
            'status' => 'active',
        ]);

        $postsOnly = $service->getNearbyFeed($user, eventRadiusKm: 25, contentType: 'all', timeFilter: 'all');
        expect(collect($postsOnly['items'])->pluck('id'))->toContain($post->id);
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
