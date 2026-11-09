<?php

namespace Tests\Feature\Listings;

use App\Livewire\Chat\ChatComponent;
use App\Livewire\Listings\InterestToggle;
use App\Models\Activity;
use App\Models\Group;
use App\Models\RecurringSchedule;
use App\Models\User;
use App\Services\ChatService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use MatanYadaev\EloquentSpatial\Objects\Point;
use Tests\TestCase;

/**
 * Spec 020 fix (2026-11-09): the listing chat join path.
 *
 * Live-use report: the owner, signed in on a published listing, saw
 * "Mark Going to join the chat." and could not chat at all. Root
 * cause: the Going toggle and the chat are separate Livewire
 * components; the toggle dispatched nothing and the chat never
 * re-rendered, so its canSend flag stayed at the page-load value
 * (false) until a manual reload. These tests walk the real
 * component path: mount the chat as a signed-in non-Going user,
 * join (via the chat's own Join button or via the page toggle's
 * event), and post, all without a reload or a remount.
 */
class ListingChatJoinFlowTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function listing(array $overrides = []): Activity
    {
        return Activity::factory()->listing()->create(array_merge([
            'location_coordinates' => new Point(33.7710, -118.1910, 4326),
            'start_time' => now()->addDays(2),
            'end_time' => now()->addDays(2)->addHours(2),
            'source_name' => 'Example Org',
        ], $overrides));
    }

    public function test_join_chat_button_marks_going_and_unlocks_posting_without_reload(): void
    {
        $user = User::factory()->create();
        $listing = $this->listing();

        $chat = Livewire::actingAs($user)->test(ChatComponent::class, ['conversationable' => $listing]);

        // Not Going: no composer. In its place, plain copy and a
        // Join the chat button (not the old instruction text).
        $chat->assertViewHas('canSend', false)
            ->assertSee('People who are going can chat here.')
            ->assertSee('Join the chat')
            ->assertDontSee('Mark Going to join the chat');

        // One tap on Join the chat: the Going mark lands AND the
        // composer unlocks in the same interaction.
        $chat->call('joinChat')
            ->assertViewHas('canSend', true)
            ->assertDispatched('going-changed');

        $this->assertDatabaseHas('activity_interests', [
            'activity_id' => $listing->id,
            'user_id' => $user->id,
        ]);

        // Posting works right away, no reload.
        $chat->set('newMessage', 'See you there!')
            ->call('sendMessage');

        $this->assertDatabaseHas('messages', [
            'user_id' => $user->id,
            'body' => 'See you there!',
        ]);
    }

    public function test_going_toggle_notifies_the_page_and_the_mounted_chat_unlocks_on_the_event(): void
    {
        $user = User::factory()->create();
        $listing = $this->listing();

        // The chat is already on the page, locked, before Going.
        $chat = Livewire::actingAs($user)->test(ChatComponent::class, ['conversationable' => $listing]);
        $chat->assertViewHas('canSend', false);

        // The page's Going toggle marks Going and tells the page.
        Livewire::actingAs($user)->test(InterestToggle::class, [
            'activityId' => $listing->id,
            'interested' => false,
            'count' => 0,
        ])->call('toggle')
            ->assertSet('interested', true)
            ->assertDispatched('going-changed');

        $this->assertDatabaseHas('activity_interests', [
            'activity_id' => $listing->id,
            'user_id' => $user->id,
        ]);

        // The already-mounted chat receives the event and unlocks
        // without a page reload.
        $chat->dispatch('going-changed', $listing->id)
            ->assertViewHas('canSend', true);

        $chat->set('newMessage', 'Count me in')
            ->call('sendMessage');

        $this->assertDatabaseHas('messages', [
            'user_id' => $user->id,
            'body' => 'Count me in',
        ]);
    }

    public function test_recurring_series_going_on_the_viewed_occurrence_unlocks_the_series_chat(): void
    {
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

        // The one series conversation is created against the SECOND
        // occurrence first (someone opened that page first).
        $chatService = app(ChatService::class);
        $conversation = $chatService->getOrCreateConversation($second);
        $this->assertSame($second->id, $conversation->conversationable_id);

        // The user lands on the FIRST occurrence and joins from there.
        $user = User::factory()->create();
        $chat = Livewire::actingAs($user)->test(ChatComponent::class, ['conversationable' => $first]);
        $chat->assertViewHas('canSend', false);

        $chat->call('joinChat')
            ->assertViewHas('canSend', true);

        // The Going mark is on the viewed occurrence...
        $this->assertDatabaseHas('activity_interests', [
            'activity_id' => $first->id,
            'user_id' => $user->id,
        ]);

        // ...and the post lands in the shared series conversation.
        $chat->set('newMessage', 'See you Tuesday')
            ->call('sendMessage');

        $this->assertSame(1, $chatService->getMessages($conversation)->count());
    }

    public function test_ended_listing_chat_shows_the_ended_state_and_join_does_nothing(): void
    {
        $user = User::factory()->create();
        $ended = $this->listing(['start_time' => now()->subDays(2), 'end_time' => now()->subDay()]);

        $chat = Livewire::actingAs($user)->test(ChatComponent::class, ['conversationable' => $ended]);

        $chat->assertViewHas('canSend', false)
            ->assertViewHas('listingEnded', true)
            ->assertSee('This event has ended.')
            ->assertDontSee('Join the chat');

        // Joining is a no-op once the event has ended: no Going mark,
        // no unlock.
        $chat->call('joinChat');

        $this->assertDatabaseMissing('activity_interests', [
            'activity_id' => $ended->id,
            'user_id' => $user->id,
        ]);
        $chat->assertViewHas('canSend', false);
    }

    public function test_detail_page_shows_the_join_call_to_action_and_guests_keep_the_sign_in_prompt(): void
    {
        $listing = $this->listing();

        // Guests: sign-in prompt only, no join button, no old copy.
        $this->get(route('events.show', $listing))
            ->assertOk()
            ->assertSeeText('Sign in to see the chat')
            ->assertDontSeeText('Join the chat')
            ->assertDontSeeText('Mark Going to join the chat');

        // Signed in and not Going: the plain explainer and the
        // Join the chat button, never the old instruction text.
        $user = User::factory()->create();
        $this->actingAs($user)->get(route('events.show', $listing))
            ->assertOk()
            ->assertSeeText('People who are going can chat here.')
            ->assertSeeText('Join the chat')
            ->assertDontSeeText('Mark Going to join the chat');
    }
}
