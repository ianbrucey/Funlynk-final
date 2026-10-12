<?php

use App\Livewire\Discovery\NearbyFeed;
use App\Models\Post;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use MatanYadaev\EloquentSpatial\Objects\Point;

describe('NearbyFeed Livewire component (Spec 001, Ticket 2)', function () {
    it('renders the skeleton shell with no feed query before the lazy follow-up request (C-04)', function () {
        $user = User::factory()->create([
            'location_coordinates' => new Point(37.7749, -122.4194, 4326),
        ]);

        Post::factory()->create([
            'user_id' => $user->id,
            'title' => 'Unique Skeleton Probe Post',
            'location_coordinates' => new Point(37.7750, -122.4195, 4326),
            'status' => 'active',
        ]);

        DB::flushQueryLog();
        DB::enableQueryLog();

        $response = $this->actingAs($user)->get(route('feed.nearby'));

        $queries = collect(DB::getQueryLog())->pluck('query');
        DB::disableQueryLog();

        $response->assertOk();
        $response->assertSee('nearby-feed-skeleton', false);
        $response->assertDontSee('Unique Skeleton Probe Post');

        expect($queries->contains(fn ($sql) => str_contains($sql, '"posts"') || str_contains($sql, '"activities"')))
            ->toBeFalse();

        // The follow-up Livewire request (simulated here via a direct
        // component test) is where the feed query actually runs.
        Livewire::actingAs($user)->withoutLazyLoading()
            ->test(NearbyFeed::class)
            ->assertSee('Unique Skeleton Probe Post');
    });

    it('runs a constant query count regardless of loaded item count (C-02)', function () {
        $user = User::factory()->create([
            'location_coordinates' => new Point(37.7749, -122.4194, 4326),
        ]);

        Post::factory()->count(10)->create([
            'location_coordinates' => new Point(37.7750, -122.4195, 4326),
            'status' => 'active',
        ]);

        DB::flushQueryLog();
        DB::enableQueryLog();
        Livewire::actingAs($user)->withoutLazyLoading()->test(NearbyFeed::class);
        $smallCount = count(DB::getQueryLog());
        DB::disableQueryLog();
        DB::flushQueryLog();

        Post::factory()->count(40)->create([
            'location_coordinates' => new Point(37.7751, -122.4196, 4326),
            'status' => 'active',
        ]);

        DB::enableQueryLog();
        Livewire::actingAs($user)->withoutLazyLoading()->test(NearbyFeed::class);
        $largeCount = count(DB::getQueryLog());
        DB::disableQueryLog();

        expect($largeCount)->toBe($smallCount);
    });

    it('runs the same reactToPost query count regardless of loaded item count (C-03)', function () {
        $otherUser = User::factory()->create();

        $reactToOnePost = function (int $itemCount) use ($otherUser): int {
            $user = User::factory()->create([
                'location_coordinates' => new Point(37.7749, -122.4194, 4326),
            ]);

            $posts = Post::factory()->count($itemCount)->create([
                'user_id' => $otherUser->id,
                'location_coordinates' => new Point(37.7750, -122.4195, 4326),
                'status' => 'active',
            ]);

            $component = Livewire::actingAs($user)->withoutLazyLoading()->test(NearbyFeed::class);

            DB::flushQueryLog();
            DB::enableQueryLog();
            $component->call('reactToPost', $posts->first()->id, 'im_down');
            $queryCount = count(DB::getQueryLog());
            DB::disableQueryLog();

            return $queryCount;
        };

        $smallCount = $reactToOnePost(5);
        $largeCount = $reactToOnePost(30);

        // 001-D10: the pre-existing, untouched-by-this-spec cost of
        // PostService::toggleReaction + the PostReacted broadcast/notification
        // listeners is 14 queries. Ticket 2's job is only to keep this flat
        // regardless of how many items are loaded in $items (array mutation,
        // no model reload) — not to lower the absolute number.
        expect($largeCount)->toBe($smallCount);
        expect($largeCount)->toBeLessThanOrEqual(15);
    });
});
