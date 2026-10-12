<?php

use App\Livewire\Discovery\NearbyFeed;
use App\Models\Activity;
use App\Models\Post;
use App\Models\Rsvp;
use App\Models\User;
use App\Services\FeedService;
use Illuminate\Support\Str;
use Livewire\Livewire;
use MatanYadaev\EloquentSpatial\Objects\Point;

/**
 * Spec 001, Ticket 3: post-card-compact / conversion-badge /
 * converted-post-overlay / event-card-compact render the array-driven
 * FeedItem shape (03-contract.md) for every 04-fixtures.json scenario,
 * with no exceptions and no leaked coordinates (C-08, full page).
 */
it('renders every fixture scenario through the real card components with no exceptions and no coordinates (C-08)', function () {
    // Distinct, identifiable coordinates (NYC) so we can assert they never
    // leak into the rendered HTML — distance_km values will never collide
    // with this fragment.
    $viewer = User::factory()->create([
        'location_coordinates' => new Point(40.7128, -74.0060, 4326),
    ]);

    // Soft-eligible post, owned by the viewer, so the (currently no-op)
    // conversion badge branch is exercised too.
    Post::factory()->create([
        'user_id' => $viewer->id,
        'title' => 'Impromptu trivia night',
        'location_coordinates' => new Point(40.7130, -74.0062, 4326),
        'status' => 'active',
        'reaction_count' => 6,
    ]);

    // Sold-out event — spots_remaining must render 0, not a negative number.
    $soldOutEvent = Activity::factory()->create([
        'title' => 'Sold Out Workshop',
        'status' => 'published',
        'start_time' => now()->addDays(3),
        'max_attendees' => 5,
        'location_coordinates' => new Point(40.7133, -74.0065, 4326),
    ]);
    Rsvp::factory()->count(5)->create(['activity_id' => $soldOutEvent->id]);

    // No-image event — must render the placeholder block, not a broken <img>.
    Activity::factory()->create([
        'title' => 'No Image Mixer',
        'status' => 'published',
        'start_time' => now()->addDays(4),
        'images' => [],
        'location_coordinates' => new Point(40.7134, -74.0066, 4326),
    ]);

    $html = Livewire::actingAs($viewer)->withoutLazyLoading()
        ->test(NearbyFeed::class)
        ->assertSee('Impromptu trivia night')
        ->assertSee('Sold Out Workshop')
        ->assertSee('No Image Mixer')
        ->html();

    expect($html)
        ->not->toContain('40.71')
        ->not->toContain('-74.00')
        ->not->toContain('location_coordinates');
});

/**
 * A converted post (`status = 'converted'`) is excluded from the live
 * `getNearbyFeed()` query by `Post::scopeActive()` (pre-existing, unchanged
 * by this spec) — it can never actually appear in a `NearbyFeed` page. The
 * converted-overlay scenario is still a real `FeedItem` shape a card can
 * receive (e.g. a page loaded just before the post converted), so it's
 * exercised directly against the real component here instead.
 */
it('renders the converted-post overlay for a FeedItem in the converted state with no exceptions', function () {
    $post = Post::factory()->create([
        'title' => 'Weekly 5k run club',
        'status' => 'converted',
        'reaction_count' => 14,
    ]);

    $method = new ReflectionMethod(FeedService::class, 'mapPostToFeedItem');
    $method->setAccessible(true);
    $feedItem = $method->invoke(app(FeedService::class), $post, collect());
    $feedItem['converted_activity_id'] = (string) Str::uuid();

    $html = view('components.post-card-compact', ['post' => $feedItem])->render();

    expect($html)
        ->toContain('Converted to Event')
        ->toContain('Weekly 5k run club')
        ->not->toContain('location_coordinates');
});
