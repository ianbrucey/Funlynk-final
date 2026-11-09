<?php

namespace Tests\Feature\Listings;

use App\Filament\Resources\Activities\Pages\ListActivities;
use App\Models\Activity;
use App\Models\ActivityInterest;
use App\Models\Follow;
use App\Models\Group;
use App\Models\Post;
use App\Models\RecurringSchedule;
use App\Models\User;
use App\Services\ChatService;
use App\Services\EventNeedService;
use App\Services\FeedService;
use App\Services\ListingInterestService;
use App\Services\RsvpService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use MatanYadaev\EloquentSpatial\Objects\Point;
use Tests\TestCase;

/**
 * Spec 019 claim tests (C-01 to C-15). Method names match the brief's
 * claims table exactly.
 */
class ExternalListingsTest extends TestCase
{
    use RefreshDatabase;

    private Point $center;

    protected function setUp(): void
    {
        parent::setUp();
        $this->center = new Point(33.7700, -118.1900, 4326);
    }

    private function nearPoint(): Point
    {
        return new Point(33.7710, -118.1910, 4326);
    }

    private function viewer(): User
    {
        return User::factory()->create(['location_coordinates' => $this->center]);
    }

    private function listing(array $overrides = []): Activity
    {
        return Activity::factory()->listing()->create(array_merge([
            'location_coordinates' => $this->nearPoint(),
            'start_time' => now()->addDays(2),
            'end_time' => now()->addDays(2)->addHours(2),
        ], $overrides));
    }

    private function admin(): User
    {
        config(['listings.admin_emails' => ['listings-admin@example.test']]);

        return User::factory()->create(['email' => 'listings-admin@example.test']);
    }

    /** @return array<int, array<string, mixed>> */
    private function feedItems(User $user, int $perPage = 30): array
    {
        return app(FeedService::class)->getNearbyFeed($user, perPage: $perPage)['items'];
    }

    public function test_listing_requires_no_host_and_hosted_requires_one(): void
    {
        $listing = $this->listing();
        $this->assertNull($listing->host_id);
        $this->assertTrue($listing->isListing());

        // Hosted with no host violates the database check.
        $this->expectException(QueryException::class);
        Activity::factory()->create(['host_id' => null]);
    }

    public function test_feed_renders_listings_without_host(): void
    {
        $viewer = $this->viewer();

        $post = Post::factory()->create([
            'user_id' => $viewer->id,
            'location_coordinates' => $this->nearPoint(),
            'status' => 'active',
        ]);
        $hosted = Activity::factory()->create([
            'location_coordinates' => $this->nearPoint(),
            'status' => 'published',
            'start_time' => now()->addDay(),
        ]);
        $listing = $this->listing();

        $items = collect($this->feedItems($viewer));
        $ids = $items->pluck('id');

        $this->assertContains($post->id, $ids);
        $this->assertContains($hosted->id, $ids);
        $this->assertContains($listing->id, $ids);

        $item = $items->firstWhere('id', $listing->id);
        $this->assertTrue($item['is_listing']);
        $this->assertSame($listing->source_name, $item['source_name']);
        $this->assertSame($listing->source_url, $item['source_url']);
        $this->assertSame(0, $item['interest_count']);
        $this->assertFalse($item['viewer_interested']);
    }

    public function test_listing_card_has_no_host_actions(): void
    {
        $viewer = $this->viewer();
        $listing = $this->listing();

        $item = collect($this->feedItems($viewer))->firstWhere('id', $listing->id);
        $this->assertNotNull($item);

        $html = Blade::render('<x-event-card-compact :event="$event" />', ['event' => $item]);

        $this->assertStringContainsString('AROUND TOWN', $html);
        $this->assertStringContainsString('Details', $html);
        $this->assertStringContainsString($listing->source_url, $html);
        $this->assertStringContainsString('target="_blank"', $html);
        $this->assertStringContainsString('rel="noopener noreferrer"', $html);
        $this->assertStringContainsString('aria-pressed', $html);
        $this->assertStringContainsString('I&#039;m interested', $html);
        $this->assertStringContainsString('Listed by FunLynk', $html);

        $this->assertStringNotContainsString('RSVP', $html);
        $this->assertStringNotContainsString('Invite', $html);
        $this->assertStringNotContainsString('Follow', $html);
        $this->assertStringNotContainsString('Group Chat', $html);
    }

    public function test_listing_cap_per_page(): void
    {
        $viewer = $this->viewer();

        // Posts first, listings after: the listings are the newest
        // items, so the cap (not recency) decides how many get in.
        for ($i = 0; $i < 12; $i++) {
            Post::factory()->create([
                'user_id' => $viewer->id,
                'location_coordinates' => $this->nearPoint(),
                'status' => 'active',
            ]);
        }
        for ($i = 0; $i < 6; $i++) {
            $this->listing(['title' => "Capped listing {$i}"]);
        }

        $items = collect($this->feedItems($viewer, perPage: 10));

        $this->assertCount(10, $items);
        $this->assertSame(2, $items->where('is_listing', true)->count());
    }

    public function test_draft_listings_are_hidden(): void
    {
        $viewer = $this->viewer();
        $draft = $this->listing(['status' => 'draft']);
        $published = $this->listing();

        // Feed and map exclude the draft.
        $this->assertNotContains($draft->id, collect($this->feedItems($viewer))->pluck('id'));
        $markers = collect(app(FeedService::class)->getMapData($viewer, 25)['markers']);
        $this->assertNotContains($draft->id, $markers->pluck('id'));
        $this->assertContains($published->id, $markers->pluck('id'));

        // Search indexing refuses drafts.
        $this->assertFalse($draft->shouldBeSearchable());

        // Detail 404s for a signed-in non-admin and on the public slug page.
        $this->actingAs($viewer)->get(route('events.show', $draft))->assertNotFound();
        $this->get(route('events.public', $draft->slug))->assertNotFound();

        // The published listing's detail renders with its notice.
        $this->actingAs($viewer)->get(route('events.show', $published))
            ->assertOk()
            ->assertSee('FunLynk does not run this event');

        // An admin may review the draft detail.
        $this->actingAs($this->admin())->get(route('events.show', $draft))->assertOk();
    }

    public function test_only_admin_approves_listings(): void
    {
        $admin = $this->admin();
        $regular = User::factory()->create();
        $listing = $this->listing(['status' => 'draft']);

        $this->assertFalse($regular->can('approveListing', $listing));
        $this->assertFalse($regular->can('unpublishListing', $listing));
        $this->assertFalse($regular->can('update', $listing));
        $this->assertTrue($admin->can('approveListing', $listing));
        $this->assertTrue($admin->can('unpublishListing', $listing));
        $this->assertTrue($admin->can('update', $listing));

        // The Filament table action approves and records who and when.
        Livewire::actingAs($admin);
        Livewire::test(ListActivities::class)
            ->callTableAction('approveListing', $listing);

        $listing->refresh();
        $this->assertSame('published', $listing->status);
        $this->assertSame($admin->id, $listing->approved_by);
        $this->assertNotNull($listing->approved_at);

        Livewire::test(ListActivities::class)
            ->callTableAction('unpublishListing', $listing);
        $this->assertSame('draft', $listing->refresh()->status);
    }

    public function test_import_is_idempotent(): void
    {
        $path = $this->writeImportFile([
            $this->importRow(['title' => 'Run Club Tuesday']),
            $this->importRow(['title' => 'Open Mat Saturday', 'source_url' => 'https://example.com/open-mat']),
        ]);

        $this->artisan('listings:import', ['path' => $path])->assertExitCode(0);
        $this->artisan('listings:import', ['path' => $path])->assertExitCode(0);

        $this->assertSame(2, Activity::listings()->count());
        $this->assertSame(2, Activity::listings()->where('status', 'draft')->count());
        $this->assertNull(Activity::listings()->first()->host_id);
    }

    public function test_source_url_rules(): void
    {
        $path = $this->writeImportFile([
            $this->importRow(['title' => 'Http one', 'source_url' => 'http://example.com/insecure']),
            $this->importRow(['title' => 'Script one', 'source_url' => 'javascript:alert(1)']),
            $this->importRow(['title' => 'Good one', 'source_url' => 'https://example.com/good']),
        ]);

        $this->artisan('listings:import', ['path' => $path])->assertExitCode(0);

        $this->assertSame(1, Activity::listings()->count());
        $this->assertSame('Good one', Activity::listings()->first()->title);
    }

    public function test_expired_listings_are_hidden(): void
    {
        $viewer = $this->viewer();

        $ended = $this->listing([
            'title' => 'Ended listing',
            'start_time' => now()->subDays(2),
            'end_time' => now()->subDay(),
        ]);
        $startedNoEnd = $this->listing([
            'title' => 'Started no end',
            'start_time' => now()->subHour(),
            'end_time' => null,
        ]);
        $ongoing = $this->listing([
            'title' => 'Ongoing listing',
            'start_time' => now()->subHour(),
            'end_time' => now()->addHours(3),
        ]);
        $upcoming = $this->listing(['title' => 'Upcoming listing']);

        $ids = collect($this->feedItems($viewer))->pluck('id');
        $this->assertNotContains($ended->id, $ids);
        $this->assertNotContains($startedNoEnd->id, $ids);
        $this->assertContains($ongoing->id, $ids);
        $this->assertContains($upcoming->id, $ids);

        $markers = collect(app(FeedService::class)->getMapData($viewer, 25)['markers'])->pluck('id');
        $this->assertNotContains($ended->id, $markers);
    }

    public function test_listing_respects_radius(): void
    {
        $viewer = $this->viewer();

        $inside = $this->listing(['title' => 'Inside radius']);
        $outside = $this->listing([
            'title' => 'Outside radius',
            'location_coordinates' => new Point(40.7128, -74.0060, 4326),
        ]);

        $ids = collect($this->feedItems($viewer))->pluck('id');
        $this->assertContains($inside->id, $ids);
        $this->assertNotContains($outside->id, $ids);
    }

    public function test_hosted_only_actions_reject_listings(): void
    {
        $user = User::factory()->create();
        $listing = $this->listing(['payment_type' => 'online', 'price_cents' => 1500]);

        $this->assertFalse($user->can('rsvp', $listing));
        $this->assertFalse($user->can('checkout', $listing));
        $this->assertFalse($user->can('chat', $listing));
        $this->assertFalse($user->can('manageNeeds', $listing));
        $this->assertFalse($user->can('claimNeeds', $listing));

        try {
            app(RsvpService::class)->createRsvp($listing, $user);
            $this->fail('RSVP on a listing should be refused.');
        } catch (AuthorizationException) {
            $this->addToAssertionCount(1);
        }

        try {
            app(ChatService::class)->getOrCreateConversation($listing);
            $this->fail('Chat on a listing should be refused.');
        } catch (AuthorizationException) {
            $this->addToAssertionCount(1);
        }

        try {
            app(EventNeedService::class)->addNeed($listing, $user, 'Chairs', null, 4);
            $this->fail('Needs on a listing should be refused.');
        } catch (\DomainException) {
            $this->addToAssertionCount(1);
        }

        $this->actingAs($user)->get(route('events.checkout', $listing))->assertForbidden();
    }

    public function test_recurring_listings_collapse(): void
    {
        $viewer = $this->viewer();
        $owner = User::factory()->create();
        $group = Group::factory()->create();
        $schedule = RecurringSchedule::create([
            'group_id' => $group->id,
            'created_by' => $owner->id,
            'title' => 'Weekly open mat',
            'start_time' => '10:00:00',
            'frequency' => 'weekly',
        ]);

        $next = $this->listing([
            'title' => 'Open mat next',
            'recurring_schedule_id' => $schedule->id,
            'start_time' => now()->addDays(1),
            'end_time' => now()->addDays(1)->addHours(2),
        ]);
        $this->listing([
            'title' => 'Open mat later',
            'recurring_schedule_id' => $schedule->id,
            'start_time' => now()->addDays(8),
            'end_time' => now()->addDays(8)->addHours(2),
        ]);
        $this->listing([
            'title' => 'Open mat latest',
            'recurring_schedule_id' => $schedule->id,
            'start_time' => now()->addDays(15),
            'end_time' => now()->addDays(15)->addHours(2),
        ]);

        $series = collect($this->feedItems($viewer))
            ->where('is_listing', true)
            ->filter(fn (array $item) => str_starts_with((string) $item['title'], 'Open mat'));

        $this->assertCount(1, $series);
        $this->assertSame($next->id, $series->first()['id']);
    }

    public function test_feed_query_count_constant_with_listings(): void
    {
        $viewer = $this->viewer();

        for ($i = 0; $i < 3; $i++) {
            Activity::factory()->create([
                'location_coordinates' => $this->nearPoint(),
                'status' => 'published',
                'start_time' => now()->addDay(),
            ]);
        }

        $countQueries = function () use ($viewer): int {
            $count = 0;
            DB::listen(function () use (&$count) {
                $count++;
            });
            app(FeedService::class)->getNearbyFeed($viewer);

            return $count;
        };

        $withoutListings = $countQueries();

        $fans = User::factory()->count(3)->create();
        for ($i = 0; $i < 4; $i++) {
            $listing = $this->listing(['title' => "Counted listing {$i}"]);
            foreach ($fans as $fan) {
                ActivityInterest::create(['activity_id' => $listing->id, 'user_id' => $fan->id]);
            }
        }

        $withListings = $countQueries();

        $this->assertSame($withoutListings, $withListings);
    }

    public function test_interest_toggle_rules(): void
    {
        $service = app(ListingInterestService::class);
        $user = User::factory()->create();
        $listing = $this->listing();

        $result = $service->toggle($user, $listing);
        $this->assertTrue($result['interested']);
        $this->assertSame(1, $result['count']);
        $this->assertSame(1, ActivityInterest::query()->where('activity_id', $listing->id)->count());

        $other = User::factory()->create();
        $result = $service->toggle($other, $listing);
        $this->assertSame(2, $result['count']);

        $result = $service->toggle($user, $listing);
        $this->assertFalse($result['interested']);
        $this->assertSame(1, $result['count']);
        $this->assertSame(1, ActivityInterest::query()->where('activity_id', $listing->id)->count());

        // A draft listing refuses interest.
        $draft = $this->listing(['status' => 'draft']);
        try {
            $service->toggle($user, $draft);
            $this->fail('Interest on a draft listing should be refused.');
        } catch (AuthorizationException) {
            $this->addToAssertionCount(1);
        }

        // An expired listing refuses interest.
        $expired = $this->listing([
            'start_time' => now()->subDays(2),
            'end_time' => now()->subDay(),
        ]);
        try {
            $service->toggle($user, $expired);
            $this->fail('Interest on an expired listing should be refused.');
        } catch (AuthorizationException) {
            $this->addToAssertionCount(1);
        }

        // A hosted event refuses listing interest.
        $hosted = Activity::factory()->create(['status' => 'published', 'start_time' => now()->addDay()]);
        try {
            $service->toggle($user, $hosted);
            $this->fail('Interest on a hosted event should be refused.');
        } catch (AuthorizationException) {
            $this->addToAssertionCount(1);
        }
    }

    public function test_interested_list_respects_privacy(): void
    {
        $service = app(ListingInterestService::class);
        $listing = $this->listing();

        $privateUser = User::factory()->create(['privacy_level' => 'private']);
        $publicUser = User::factory()->create(['privacy_level' => 'public']);
        $followersUser = User::factory()->create(['privacy_level' => 'followers']);
        $stranger = User::factory()->create();

        foreach ([$privateUser, $publicUser, $followersUser] as $fan) {
            ActivityInterest::create(['activity_id' => $listing->id, 'user_id' => $fan->id]);
        }

        $visibleToStranger = $service->visibleInterestedUsers($listing, $stranger)->pluck('id');
        $this->assertContains($publicUser->id, $visibleToStranger);
        $this->assertNotContains($privateUser->id, $visibleToStranger);
        $this->assertNotContains($followersUser->id, $visibleToStranger);

        // The owner of a private profile always sees themselves.
        $visibleToSelf = $service->visibleInterestedUsers($listing, $privateUser)->pluck('id');
        $this->assertContains($privateUser->id, $visibleToSelf);

        // A follower sees a followers-level profile.
        Follow::create(['follower_id' => $stranger->id, 'following_id' => $followersUser->id]);
        $visibleAfterFollow = $service->visibleInterestedUsers($listing, $stranger)->pluck('id');
        $this->assertContains($followersUser->id, $visibleAfterFollow);
    }

    /** @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function importRow(array $overrides = []): array
    {
        return array_merge([
            'title' => 'Sample listing',
            'description' => 'A researched listing.',
            'source_name' => 'Example Org',
            'source_url' => 'https://example.com/sample',
            'start_time' => now()->addDays(3)->toIso8601String(),
            'location_name' => 'Bluff Park',
            'latitude' => 33.763,
            'longitude' => -118.164,
            'payment_type' => 'free',
        ], $overrides);
    }

    /** @param  list<array<string, mixed>>  $rows */
    private function writeImportFile(array $rows): string
    {
        $path = tempnam(sys_get_temp_dir(), 'listings-import-').'.json';
        file_put_contents($path, json_encode($rows));

        return $path;
    }
}
