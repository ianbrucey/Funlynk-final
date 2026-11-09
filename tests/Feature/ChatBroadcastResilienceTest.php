<?php

namespace Tests\Feature;

use App\Http\Middleware\SanitizeSocketId;
use App\Livewire\Chat\ChatComponent;
use App\Models\Activity;
use App\Models\User;
use App\Services\ChatService;
use Illuminate\Contracts\Broadcasting\Broadcaster;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Broadcast;
use Livewire\Livewire;
use MatanYadaev\EloquentSpatial\Objects\Point;
use Symfony\Component\HttpFoundation\Response;
use Tests\TestCase;

/**
 * Production incident (2026-11-09): posting in a listing chat threw
 * Pusher\PusherException "Invalid socket ID undefined". Livewire copies
 * Echo.socketId() into the X-Socket-Id header on every request; Echo
 * (configured for Reverb) never connected in production, so the header
 * arrived as the literal string "undefined", Laravel passed it verbatim
 * to Pusher as the toOthers() exclusion, and Pusher rejected it. The
 * message itself was saved (the create precedes the broadcast), but the
 * Livewire request failed, so the send appeared to fail.
 *
 * Invariant under test: sending a chat message must never fail because
 * realtime broadcasting failed.
 */
class ChatBroadcastResilienceTest extends TestCase
{
    use RefreshDatabase;

    private function useBroadcaster(string $name, Broadcaster $broadcaster): void
    {
        Broadcast::extend($name, fn () => $broadcaster);
        config([
            'broadcasting.default' => $name,
            "broadcasting.connections.{$name}" => ['driver' => $name],
        ]);
    }

    private function throwingBroadcaster(): Broadcaster
    {
        return new class implements Broadcaster
        {
            public function auth($request)
            {
                return null;
            }

            public function validAuthenticationResponse($request, $result)
            {
                return $result;
            }

            public function broadcast(array $channels, $event, array $payload = []): void
            {
                throw new \RuntimeException('Realtime is down');
            }
        };
    }

    public function test_middleware_removes_invalid_socket_id_and_keeps_valid_one(): void
    {
        $middleware = new SanitizeSocketId;

        $seen = [];
        $next = function (Request $request) use (&$seen) {
            $seen[] = $request->header('X-Socket-Id');

            return new Response('ok');
        };

        // The exact production value: Echo never connected.
        $middleware->handle(Request::create('/', 'GET', [], [], [], ['HTTP_X_SOCKET_ID' => 'undefined']), $next);
        // Other malformed values go too.
        $middleware->handle(Request::create('/', 'GET', [], [], [], ['HTTP_X_SOCKET_ID' => 'abc.def']), $next);
        $middleware->handle(Request::create('/', 'GET', [], [], [], ['HTTP_X_SOCKET_ID' => '']), $next);
        // A real Pusher/Reverb socket id survives untouched.
        $middleware->handle(Request::create('/', 'GET', [], [], [], ['HTTP_X_SOCKET_ID' => '123456.789']), $next);
        // And no header stays no header.
        $middleware->handle(Request::create('/', 'GET'), $next);

        $this->assertSame([null, null, null, '123456.789', null], $seen);
    }

    public function test_message_persists_and_send_succeeds_when_broadcaster_throws(): void
    {
        $this->useBroadcaster('throwing', $this->throwingBroadcaster());

        $user = User::factory()->create();
        $activity = Activity::factory()->create();
        $service = app(ChatService::class);
        $conversation = $service->getOrCreateConversation($activity);

        $message = $service->sendMessage($conversation, $user, 'Still here?');

        $this->assertSame('Still here?', $message->body);
        $this->assertDatabaseHas('messages', [
            'id' => $message->id,
            'conversation_id' => $conversation->id,
            'body' => 'Still here?',
        ]);
    }

    public function test_component_send_succeeds_when_broadcaster_throws(): void
    {
        $this->useBroadcaster('throwing', $this->throwingBroadcaster());

        $user = User::factory()->create();
        $listing = Activity::factory()->listing()->create([
            'location_coordinates' => new Point(33.7710, -118.1910, 4326),
            'start_time' => now()->addDays(2),
            'end_time' => now()->addDays(2)->addHours(2),
        ]);

        // Ian's exact path: join the listing chat, then post. Before the
        // fix, the sendMessage call died on the broadcast exception.
        Livewire::actingAs($user)->test(ChatComponent::class, ['conversationable' => $listing])
            ->call('joinChat')
            ->set('newMessage', 'Anyone else going?')
            ->call('sendMessage')
            ->assertSet('newMessage', '');

        $this->assertDatabaseHas('messages', [
            'user_id' => $user->id,
            'body' => 'Anyone else going?',
        ]);
    }

    public function test_healthy_broadcaster_receives_event_with_socket_exclusion(): void
    {
        $recorder = new class implements Broadcaster
        {
            /** @var array<int, array{channels: array, event: string, payload: array}> */
            public array $calls = [];

            public function auth($request)
            {
                return null;
            }

            public function validAuthenticationResponse($request, $result)
            {
                return $result;
            }

            public function broadcast(array $channels, $event, array $payload = []): void
            {
                $this->calls[] = [
                    'channels' => array_map(fn ($channel) => (string) $channel, $channels),
                    'event' => $event,
                    'payload' => $payload,
                ];
            }
        };
        $this->useBroadcaster('recording', $recorder);

        // A connected client: Livewire sent a real socket id, which must
        // still reach the broadcaster as the toOthers() exclusion.
        request()->headers->set('X-Socket-Id', '12345.6789');

        $user = User::factory()->create();
        $activity = Activity::factory()->create();
        $service = app(ChatService::class);
        $conversation = $service->getOrCreateConversation($activity);
        $recorder->calls = [];

        $message = $service->sendMessage($conversation, $user, 'Hello realtime');

        $this->assertCount(1, $recorder->calls);
        $call = $recorder->calls[0];
        $this->assertStringContainsString('MessageSent', $call['event']);
        $this->assertSame(['private-conversation.'.$conversation->id], $call['channels']);
        $this->assertSame('12345.6789', $call['payload']['socket'] ?? null);
        $this->assertSame($message->id, $call['payload']['id'] ?? null);
        $this->assertSame('Hello realtime', $call['payload']['body'] ?? null);
    }
}
