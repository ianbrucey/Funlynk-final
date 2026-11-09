<?php

namespace Tests\Feature\Listings;

use App\Livewire\Chat\ChatComponent;
use App\Models\Activity;
use App\Models\ActivityInterest;
use App\Models\Conversation;
use App\Models\Follow;
use App\Models\Group;
use App\Models\RecurringSchedule;
use App\Models\Report;
use App\Models\User;
use App\Services\ChatService;
use App\Services\EventNeedService;
use App\Services\FeedService;
use App\Services\ListingInterestService;
use App\Services\RsvpService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use MatanYadaev\EloquentSpatial\Objects\Point;
use Tests\TestCase;

/**
 * Spec 020 claim tests (C-20-01 to C-20-15). Method names match the
 * brief's claims table exactly.
 */
class ListingInteractionImagesTest extends TestCase
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

    private function listing(array $overrides = []): Activity
    {
        return Activity::factory()->listing()->create(array_merge([
            'location_coordinates' => $this->nearPoint(),
            'start_time' => now()->addDays(2),
            'end_time' => now()->addDays(2)->addHours(2),
            'source_name' => 'Example Org',
        ], $overrides));
    }

    private function jpegBytes(): string
    {
        $image = imagecreatetruecolor(1200, 800);
        ob_start();
        imagejpeg($image);

        return (string) ob_get_clean();
    }

    private function markGoing(User $user, Activity $activity): void
    {
        ActivityInterest::create(['activity_id' => $activity->id, 'user_id' => $user->id]);
    }

    public function test_going_toggle_rules(): void
    {
        $service = app(ListingInterestService::class);
        $user = User::factory()->create();
        $listing = $this->listing();

        $result = $service->toggle($user, $listing);
        $this->assertTrue($result['interested']);
        $this->assertSame(1, $result['count']);
        // One row per user.
        $this->assertSame(1, ActivityInterest::query()->where('activity_id', $listing->id)->where('user_id', $user->id)->count());

        $result = $service->toggle($user, $listing);
        $this->assertFalse($result['interested']);
        $this->assertSame(0, $result['count']);

        // Draft refuses.
        $draft = $this->listing(['status' => 'draft']);
        try {
            $service->toggle($user, $draft);
            $this->fail('Going on a draft listing should be refused.');
        } catch (AuthorizationException) {
            $this->addToAssertionCount(1);
        }

        // Expired refuses.
        $expired = $this->listing(['start_time' => now()->subDays(2), 'end_time' => now()->subDay()]);
        try {
            $service->toggle($user, $expired);
            $this->fail('Going on an expired listing should be refused.');
        } catch (AuthorizationException) {
            $this->addToAssertionCount(1);
        }

        // Kill switch refuses.
        config(['listings.enabled' => false]);
        try {
            $service->toggle($user, $listing);
            $this->fail('Going should be refused while listings are disabled.');
        } catch (AuthorizationException) {
            $this->addToAssertionCount(1);
        }
    }

    public function test_whos_going_list_respects_privacy(): void
    {
        $service = app(ListingInterestService::class);
        $listing = $this->listing();

        $privateUser = User::factory()->create(['privacy_level' => 'private']);
        $publicUser = User::factory()->create(['privacy_level' => 'public']);
        $followersUser = User::factory()->create(['privacy_level' => 'followers']);
        $stranger = User::factory()->create();

        foreach ([$privateUser, $publicUser, $followersUser] as $fan) {
            $this->markGoing($fan, $listing);
        }

        $visibleToStranger = $service->visibleInterestedUsers($listing, $stranger)->pluck('id');
        $this->assertContains($publicUser->id, $visibleToStranger);
        $this->assertNotContains($privateUser->id, $visibleToStranger);
        $this->assertNotContains($followersUser->id, $visibleToStranger);

        $visibleToSelf = $service->visibleInterestedUsers($listing, $privateUser)->pluck('id');
        $this->assertContains($privateUser->id, $visibleToSelf);

        Follow::create(['follower_id' => $stranger->id, 'following_id' => $followersUser->id]);
        $visibleAfterFollow = $service->visibleInterestedUsers($listing, $stranger)->pluck('id');
        $this->assertContains($followersUser->id, $visibleAfterFollow);
    }

    public function test_listing_chat_is_one_conversation_per_listing_and_series(): void
    {
        $chat = app(ChatService::class);
        $listing = $this->listing();

        $one = $chat->getOrCreateConversation($listing);
        $two = $chat->getOrCreateConversation($listing);
        $this->assertSame($one->id, $two->id);

        // Series: two occurrence rows share one conversation.
        $owner = User::factory()->create();
        $group = Group::factory()->create();
        $schedule = RecurringSchedule::create([
            'group_id' => $group->id,
            'created_by' => $owner->id,
            'title' => 'Weekly run',
            'start_time' => '19:00:00',
            'frequency' => 'weekly',
        ]);
        $first = $this->listing(['recurring_schedule_id' => $schedule->id, 'start_time' => now()->addDay(), 'end_time' => now()->addDay()->addHours(2)]);
        $second = $this->listing(['recurring_schedule_id' => $schedule->id, 'start_time' => now()->addDays(8), 'end_time' => now()->addDays(8)->addHours(2)]);

        $fromFirst = $chat->getOrCreateConversation($first);
        $fromSecond = $chat->getOrCreateConversation($second);
        $this->assertSame($fromFirst->id, $fromSecond->id);
        $this->assertSame(1, Conversation::query()
            ->where('conversationable_type', Activity::class)
            ->whereIn('conversationable_id', [$first->id, $second->id])
            ->count());

        // History is shared: a message sent from the first occurrence
        // is visible from the second.
        $user = User::factory()->create();
        $this->markGoing($user, $first);
        $chat->sendMessage($fromFirst, $user, 'See you there');
        $this->assertSame(1, $chat->getMessages($fromSecond)->count());
    }

    public function test_listing_chat_access_rules(): void
    {
        $chat = app(ChatService::class);
        $listing = $this->listing();
        $conversation = $chat->getOrCreateConversation($listing);

        $going = User::factory()->create();
        $this->markGoing($going, $listing);
        $reader = User::factory()->create();

        // Reading: any signed-in user who can view the listing.
        $this->assertTrue($reader->can('view', $conversation));
        $this->assertTrue($going->can('view', $conversation));

        // Posting: Going mark required, enforced in policy and service.
        $this->assertFalse($reader->can('sendMessage', $conversation));
        $this->assertFalse($reader->can('chat', $listing));
        $this->assertTrue($going->can('sendMessage', $conversation));
        $this->assertTrue($going->can('chat', $listing));

        try {
            $chat->sendMessage($conversation, $reader, 'hello');
            $this->fail('Posting without Going should be refused.');
        } catch (AuthorizationException) {
            $this->addToAssertionCount(1);
        }

        $message = $chat->sendMessage($conversation, $going, 'hello');
        $this->assertNotNull($message->id);

        // Unmarking Going removes posting access immediately; the
        // message and read access stay.
        app(ListingInterestService::class)->toggle($going, $listing);
        $this->assertFalse($going->can('sendMessage', $conversation));
        $this->assertTrue($going->can('view', $conversation));
        $this->assertSame(1, $chat->getMessages($conversation)->count());

        // Guests: no chat on the detail page, only the sign-in prompt
        // and the who's-going surface.
        $this->get(route('events.show', $listing))
            ->assertOk()
            ->assertSeeText('Sign in to see the chat');

        // A signed-in non-Going user is told how to join.
        $this->actingAs($reader)->get(route('events.show', $listing))
            ->assertOk()
            ->assertSeeText('Mark Going to join the chat');
    }

    public function test_listing_chat_lifecycle(): void
    {
        $chat = app(ChatService::class);
        $user = User::factory()->create();
        $listing = $this->listing();
        $this->markGoing($user, $listing);
        $conversation = $chat->getOrCreateConversation($listing);
        $chat->sendMessage($conversation, $user, 'before it ends');

        // The listing ends: posting closes, reading stays. (Reload the
        // conversation so its subject is the updated listing row, as a
        // fresh request would see it.)
        $listing->update(['start_time' => now()->subDays(2), 'end_time' => now()->subDay()]);
        $listing->refresh();
        $conversation = $conversation->fresh();
        $this->assertFalse($user->can('sendMessage', $conversation));
        $this->assertTrue($user->can('view', $conversation));
        $this->assertSame(1, $chat->getMessages($conversation)->count());
        try {
            $chat->sendMessage($conversation, $user, 'after it ends');
            $this->fail('Posting after the listing ends should be refused.');
        } catch (AuthorizationException) {
            $this->addToAssertionCount(1);
        }

        // Unpublished: the resolver refuses, no back door.
        $draft = $this->listing(['status' => 'draft']);
        try {
            $chat->getOrCreateConversation($draft);
            $this->fail('Chat for an unpublished listing should be refused.');
        } catch (AuthorizationException) {
            $this->addToAssertionCount(1);
        }

        // Kill switch: the resolver refuses.
        config(['listings.enabled' => false]);
        $published = $this->listing();
        try {
            $chat->getOrCreateConversation($published);
            $this->fail('Chat should be refused while listings are disabled.');
        } catch (AuthorizationException) {
            $this->addToAssertionCount(1);
        }
    }

    public function test_listing_detail_notice_and_secondary_source_link(): void
    {
        $user = User::factory()->create();
        $listing = $this->listing();

        $response = $this->actingAs($user)->get(route('events.show', $listing))->assertOk();

        // The notice stays byte-for-byte.
        $response->assertSee('FunLynk does not run this event. It is listed here so you can see what is on around town. Details, tickets and changes are handled by the organizer.', false);
        // The source link is a secondary text link, not the primary button.
        $response->assertSeeText('Details at Example Org ↗');
        $response->assertDontSee('>Details ↗<', false);
        $response->assertSee($listing->source_url, false);
    }

    public function test_hosted_activity_chat_unchanged(): void
    {
        $chat = app(ChatService::class);
        $hosted = Activity::factory()->create(['status' => 'published', 'start_time' => now()->addDay()]);
        $user = User::factory()->create();

        $this->assertTrue($user->can('chat', $hosted));
        $conversation = $chat->getOrCreateConversation($hosted);
        $this->assertSame($conversation->id, $chat->getOrCreateConversation($hosted)->id);
        $this->assertTrue($user->can('sendMessage', $conversation));

        // No RSVP and no interest row needed to post in a hosted chat.
        $message = $chat->sendMessage($conversation, $user, 'hosted hello');
        $this->assertNotNull($message->id);
    }

    public function test_hosted_only_actions_still_reject_listings(): void
    {
        $user = User::factory()->create();
        $listing = $this->listing(['payment_type' => 'online', 'price_cents' => 1500]);

        $this->assertFalse($user->can('rsvp', $listing));
        $this->assertFalse($user->can('checkout', $listing));
        $this->assertFalse($user->can('manageNeeds', $listing));
        $this->assertFalse($user->can('claimNeeds', $listing));

        try {
            app(RsvpService::class)->createRsvp($listing, $user);
            $this->fail('RSVP on a listing should be refused.');
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

    public function test_report_listing_chat_message(): void
    {
        $chat = app(ChatService::class);
        $listing = $this->listing();

        $author = User::factory()->create();
        $reporter = User::factory()->create();
        $this->markGoing($author, $listing);
        $this->markGoing($reporter, $listing);

        $conversation = $chat->getOrCreateConversation($listing);
        $message = $chat->sendMessage($conversation, $author, 'spammy message');

        Livewire::actingAs($reporter);
        Livewire::test(ChatComponent::class, ['conversationable' => $listing])
            ->call('reportMessage', $message->id);

        $this->assertSame(1, Report::query()->count());
        $report = Report::query()->first();
        $this->assertSame($reporter->id, $report->reporter_id);
        $this->assertSame($message->id, $report->reported_message_id);
        $this->assertSame($author->id, $report->reported_user_id);
        $this->assertSame('pending', $report->status);
    }

    public function test_import_stores_listing_image(): void
    {
        Storage::fake('s3');
        Http::fake(['example.com/*' => Http::response($this->jpegBytes(), 200, ['Content-Type' => 'image/jpeg'])]);

        $path = $this->writeImportFile([
            $this->importRow(['image_url' => 'https://example.com/images/open-mat.jpg']),
        ]);

        $this->artisan('listings:import', ['path' => $path])
            ->expectsOutputToContain('Images: 1 stored, 0 failed.')
            ->assertExitCode(0);

        $listing = Activity::listings()->firstOrFail();
        $this->assertCount(1, $listing->images);
        $this->assertStringStartsWith('activities/', $listing->images[0]);
        $this->assertStringEndsWith('.webp', $listing->images[0]);
        Storage::disk('s3')->assertExists($listing->images[0]);
        $this->assertSame('https://example.com/images/open-mat.jpg', $listing->image_source_url);
    }

    public function test_import_image_failure_never_fails_listing(): void
    {
        Storage::fake('s3');
        config(['listings.image_max_bytes' => 100]);
        $jpeg = $this->jpegBytes();
        Http::fake(function (Request $request) use ($jpeg) {
            return match (true) {
                str_contains($request->url(), 'html') => Http::response('<html>nope</html>', 200, ['Content-Type' => 'text/html']),
                str_contains($request->url(), 'big') => Http::response($jpeg, 200, ['Content-Type' => 'image/jpeg']),
                str_contains($request->url(), 'fake') => Http::response('not an image', 200, ['Content-Type' => 'image/jpeg']),
                default => Http::response('gone', 500, ['Content-Type' => 'image/jpeg']),
            };
        });

        $path = $this->writeImportFile([
            $this->importRow(['title' => 'Http image', 'source_url' => 'https://example.com/a', 'image_url' => 'http://example.com/images/a.jpg']),
            $this->importRow(['title' => 'Html image', 'source_url' => 'https://example.com/b', 'image_url' => 'https://example.com/images/html.jpg']),
            $this->importRow(['title' => 'Big image', 'source_url' => 'https://example.com/c', 'image_url' => 'https://example.com/images/big.jpg']),
            $this->importRow(['title' => 'Fake image', 'source_url' => 'https://example.com/d', 'image_url' => 'https://example.com/images/fake.jpg']),
            $this->importRow(['title' => 'Gone image', 'source_url' => 'https://example.com/e', 'image_url' => 'https://example.com/images/gone.jpg']),
        ]);

        Artisan::call('listings:import', ['path' => $path]);
        $output = Artisan::output();

        $this->assertSame(5, Activity::listings()->count());
        $this->assertSame(5, substr_count($output, 'image failed'));
        foreach (Activity::listings()->get() as $listing) {
            $this->assertSame([], $listing->images ?? []);
            $this->assertNull($listing->image_source_url);
        }
    }

    public function test_reimport_does_not_redownload_image(): void
    {
        Storage::fake('s3');
        Http::fake(['example.com/*' => Http::response($this->jpegBytes(), 200, ['Content-Type' => 'image/jpeg'])]);

        $path = $this->writeImportFile([
            $this->importRow(['image_url' => 'https://example.com/images/run.jpg']),
        ]);

        $this->artisan('listings:import', ['path' => $path])->assertExitCode(0);
        Http::assertSentCount(1);

        // The duplicate is skipped at the dedupe check, before any fetch.
        $this->artisan('listings:import', ['path' => $path])->assertExitCode(0);
        Http::assertSentCount(1);
        $this->assertSame(1, Activity::listings()->count());
    }

    public function test_listing_image_renders_in_feed_and_detail(): void
    {
        Storage::fake('s3');
        $viewer = User::factory()->create(['location_coordinates' => $this->center]);
        $withImage = $this->listing(['images' => ['activities/cover-one.webp'], 'image_source_url' => 'https://example.com/images/one.jpg']);
        $withoutImage = $this->listing(['title' => 'Imageless listing']);

        $items = collect(app(FeedService::class)->getNearbyFeed($viewer, perPage: 30)['items']);
        $item = $items->firstWhere('id', $withImage->id);
        $this->assertNotNull($item);
        $this->assertSame(['activities/cover-one.webp'], $item['images']);

        $this->actingAs($viewer)->get(route('events.show', $withImage))
            ->assertOk()
            ->assertSee('activities/cover-one.webp', false);

        // An imageless listing still renders the date-strip placeholder.
        $this->actingAs($viewer)->get(route('events.show', $withoutImage))
            ->assertOk()
            ->assertSee('📅', false);
    }

    public function test_listing_photo_credit_rendered(): void
    {
        $user = User::factory()->create();
        $listing = $this->listing([
            'images' => ['activities/cover-two.webp'],
            'image_source_url' => 'https://example.com/images/two.jpg',
        ]);

        $this->actingAs($user)->get(route('events.show', $listing))
            ->assertOk()
            ->assertSeeText('Photo: Example Org')
            ->assertSee($listing->source_url, false);
    }

    public function test_backfill_images_only_fills_empty_listings(): void
    {
        Storage::fake('s3');
        $requested = [];
        Http::fake(function (Request $request) use (&$requested) {
            $requested[] = $request->url();

            return Http::response($this->jpegBytes(), 200, ['Content-Type' => 'image/jpeg']);
        });

        $empty = $this->listing(['title' => 'Empty listing', 'source_url' => 'https://example.com/empty']);
        $filledAlready = $this->listing([
            'title' => 'Filled listing',
            'source_url' => 'https://example.com/filled',
            'images' => ['activities/existing.webp'],
        ]);
        $countBefore = Activity::count();

        $path = $this->writeImportFile([
            ['source_url' => 'https://example.com/empty', 'title' => 'Empty listing', 'start_time' => $empty->start_time->toIso8601String(), 'image_url' => 'https://example.com/images/empty.jpg'],
            ['source_url' => 'https://example.com/filled', 'title' => 'Filled listing', 'start_time' => $filledAlready->start_time->toIso8601String(), 'image_url' => 'https://example.com/images/filled.jpg'],
            ['source_url' => 'https://example.com/missing', 'title' => 'Missing listing', 'start_time' => now()->addDays(5)->toIso8601String(), 'image_url' => 'https://example.com/images/missing.jpg'],
        ]);

        Artisan::call('listings:import-images', ['path' => $path]);
        $output = Artisan::output();
        $this->assertStringContainsString('2 matched, 1 filled, 1 skipped, 1 not found, 0 image failed', $output);

        // Filled: only the empty listing, with provenance recorded.
        $empty->refresh();
        $this->assertCount(1, $empty->images);
        $this->assertStringStartsWith('activities/', $empty->images[0]);
        $this->assertSame('https://example.com/images/empty.jpg', $empty->image_source_url);
        Storage::disk('s3')->assertExists($empty->images[0]);

        // Never overwrites, never downloads for a filled listing,
        // never creates rows.
        $this->assertSame(['activities/existing.webp'], $filledAlready->refresh()->images);
        $this->assertSame(['https://example.com/images/empty.jpg'], $requested);
        $this->assertSame($countBefore, Activity::count());

        // Re-running is a no-op.
        Artisan::call('listings:import-images', ['path' => $path]);
        $output = Artisan::output();
        $this->assertStringContainsString('2 matched, 0 filled, 2 skipped, 1 not found, 0 image failed', $output);
        $this->assertSame(['https://example.com/images/empty.jpg'], $requested);
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
        $path = tempnam(sys_get_temp_dir(), 'listings-020-').'.json';
        file_put_contents($path, json_encode($rows));

        return $path;
    }
}
