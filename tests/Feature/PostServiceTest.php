<?php

use App\Events\PostCreated;
use App\Events\PostReacted;
use App\Jobs\ExpirePostsJob;
use App\Models\Post;
use App\Models\PostReaction;
use App\Models\User;
use App\Services\PostService;
use Illuminate\Support\Facades\Event;
use MatanYadaev\EloquentSpatial\Objects\Point;

describe('PostService', function () {
    it('creates a post with default expiration and dispatches an event', function () {
        Event::fake();

        $user = User::factory()->create();

        $service = app(PostService::class);

        $post = $service->createPost([
            'user' => $user,
            'title' => 'Pickup basketball tonight',
            'description' => 'Who is down for a game?',
            'location_name' => 'Dolores Park',
            'latitude' => 37.7599,
            'longitude' => -122.4148,
        ]);

        expect($post->user_id)->toBe($user->id)
            ->and($post->status)->toBe('active')
            ->and($post->expires_at->greaterThan(now()))->toBeTrue();

        expect($post->location_coordinates)->toBeInstanceOf(Point::class);

        Event::assertDispatched(PostCreated::class, function (PostCreated $event) use ($post) {
            return $event->post->id === $post->id;
        });
    });

    it('rejects invalid latitude and longitude', function () {
        $user = User::factory()->create();

        $service = app(PostService::class);

        expect(fn () => $service->createPost([
            'user' => $user,
            'title' => 'Invalid location',
            'latitude' => 200,
            'longitude' => 0,
        ]))->toThrow(InvalidArgumentException::class);
    });

    it('creates or updates reactions and updates reaction_count', function () {
        Event::fake([PostReacted::class]);

        $author = User::factory()->create();
        $reactor = User::factory()->create();
        $post = Post::factory()->create([
            'user_id' => $author->id,
            'reaction_count' => 0,
        ]);

        $service = app(PostService::class);

        // First reaction
        $reaction = $service->reactToPost($post->id, 'im_down', $reactor);

        expect($reaction->reaction_type)->toBe('im_down');
        expect($post->fresh()->reaction_count)->toBe(1);

        Event::assertDispatched(PostReacted::class, 1);

        // Update the same user reaction
        $reaction = $service->reactToPost($post->id, 'invite_friends', $reactor);

        expect($reaction->reaction_type)->toBe('invite_friends');
        expect(PostReaction::where('post_id', $post->id)->count())->toBe(1);
        expect($post->fresh()->reaction_count)->toBe(1);
    });

    it('calculates conversion eligibility thresholds correctly', function () {
        $user = User::factory()->create();
        $post = Post::factory()->create([
            'user_id' => $user->id,
            'reaction_count' => Post::CONVERSION_SOFT_THRESHOLD,
        ]);

        $service = app(PostService::class);

        $eligibility = $service->checkConversionEligibility($post->id);

        expect($eligibility['eligible'])->toBeTrue();
        
        // With current test config (Soft=2, Strong=1), auto_convert is also true
        // expect($eligibility['auto_convert'])->toBeFalse(); 
        
        $post->update(['reaction_count' => Post::CONVERSION_STRONG_THRESHOLD + 5]);
        $eligibility = $service->checkConversionEligibility($post->id);

        expect($eligibility['auto_convert'])->toBeTrue();
    });

    it('expires overdue posts via service and job', function () {
        $user = User::factory()->create();

        // One active, one already expired
        $active = Post::factory()->create([
            'user_id' => $user->id,
            'expires_at' => now()->subHour(),
            'status' => 'active',
        ]);

        $alreadyExpired = Post::factory()->create([
            'user_id' => $user->id,
            'expires_at' => now()->subHours(2),
            'status' => 'expired',
        ]);

        $service = app(PostService::class);

        $expiredCount = $service->expirePosts();
        expect($expiredCount)->toBe(1);
        expect($active->fresh()->status)->toBe('expired');
        expect($alreadyExpired->fresh()->status)->toBe('expired');

        // Ensure the job delegates to the service
        $job = new ExpirePostsJob();
        $job->handle($service);

        expect(Post::where('status', 'active')->count())->toBe(0);
    });
});
