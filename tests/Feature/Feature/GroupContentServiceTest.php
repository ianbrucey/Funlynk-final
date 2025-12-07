<?php

use App\Events\GroupEventCreated;
use App\Events\GroupPostCreated;
use App\Models\Activity;
use App\Models\Group;
use App\Models\Post;
use App\Models\Tag;
use App\Models\User;
use App\Services\GroupContentService;
use App\Services\GroupService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use MatanYadaev\EloquentSpatial\Objects\Point;

uses(RefreshDatabase::class);

beforeEach(function () {
    // Disable event listeners to avoid transaction issues in tests
    Event::fake();
    $this->groupContentService = new GroupContentService;
    $this->groupService = new GroupService;
    $this->user = User::factory()->create(['email' => 'groupcontentservice_user@example.com']);
    $this->group = Group::factory()->create();
    $this->groupService->addMember($this->group, $this->user, 'admin');
    $this->actingAs($this->user);
});

describe('GroupContentService', function () {
    it('can create a group post', function () {
        Event::fake();

        $tag = Tag::factory()->create();
        $data = [
            'title' => 'Group Post Title',
            'description' => 'Group Post Description',
            'tags' => [$tag->id], // Tags stored as JSON array in posts table
            'expires_at' => now()->addHours(24),
        ];

        $post = $this->groupContentService->createGroupPost($this->group, $this->user, $data);

        expect($post)->toBeInstanceOf(Post::class);
        expect($post->group_id)->toBe($this->group->id);
        expect($post->user_id)->toBe($this->user->id);
        expect($post->title)->toBe('Group Post Title');
        expect($post->tags)->toContain($tag->id); // Tags is a JSON column, not a relationship

        Event::assertDispatched(GroupPostCreated::class);
    });

    it('can create a group event', function () {
        Event::fake();

        $data = [
            'title' => 'Group Event Title',
            'description' => 'Group Event Description',
            'location_name' => 'Test Location',
            'location_coordinates' => new Point(20, 10),
            'start_time' => now()->addDay()->toDateTimeString(),
            'end_time' => now()->addDays(2)->toDateTimeString(),
            'tags' => [Tag::factory()->create()->id],
        ];

        $activity = $this->groupContentService->createGroupEvent($this->group, $this->user, $data);

        expect($activity)->toBeInstanceOf(Activity::class);
        expect($activity->group_id)->toBe($this->group->id);
        expect($activity->host_id)->toBe($this->user->id); // Activities use host_id
        expect($activity->title)->toBe('Group Event Title');
        expect($activity->tags()->count())->toBe(1);

        Event::assertDispatched(GroupEventCreated::class);
    });

    it('can get group timeline', function () {
        $post = $this->groupContentService->createGroupPost($this->group, $this->user, [
            'title' => 'Post 1',
            'description' => 'Desc 1',
            'expires_at' => now()->addHours(24),
        ]);
        $event = $this->groupContentService->createGroupEvent($this->group, $this->user, [
            'title' => 'Event 1',
            'description' => 'Desc 2',
            'location_name' => 'Loc 1',
            'location_coordinates' => new Point(1, 1),
            'start_time' => now()->addDay(),
            'end_time' => now()->addDays(2),
        ]);

        $timeline = $this->groupContentService->getGroupTimeline($this->group);

        expect($timeline->count())->toBe(2);
        // Timeline should contain both post and event (order depends on created_at timestamps)
        $ids = $timeline->pluck('id')->toArray();
        expect($ids)->toContain($post->id);
        expect($ids)->toContain($event->id);
    });

    it('can get group posts', function () {
        $post1 = $this->groupContentService->createGroupPost($this->group, $this->user, [
            'title' => 'Post 1',
            'description' => 'Desc 1',
            'expires_at' => now()->addHours(24),
        ]);
        $post2 = $this->groupContentService->createGroupPost($this->group, $this->user, [
            'title' => 'Post 2',
            'description' => 'Desc 2',
            'expires_at' => now()->addHours(24),
        ]);
        $this->groupContentService->createGroupEvent($this->group, $this->user, [
            'title' => 'Event 1',
            'description' => 'Desc 3',
            'location_name' => 'Loc 1',
            'location_coordinates' => new Point(1, 1),
            'start_time' => now()->addDay(),
            'end_time' => now()->addDays(2),
        ]);

        $posts = $this->groupContentService->getGroupPosts($this->group);

        expect($posts->count())->toBe(2);
        expect($posts->pluck('id'))->toContain($post1->id, $post2->id);
    });

    it('can get group events', function () {
        $this->groupContentService->createGroupPost($this->group, $this->user, [
            'title' => 'Post 1',
            'description' => 'Desc 1',
            'expires_at' => now()->addHours(24),
        ]);
        $event1 = $this->groupContentService->createGroupEvent($this->group, $this->user, [
            'title' => 'Event 1',
            'description' => 'Desc 2',
            'location_name' => 'Loc 1',
            'location_coordinates' => new \MatanYadaev\EloquentSpatial\Objects\Point(1, 1),
            'start_time' => now()->addDay(),
            'end_time' => now()->addDays(2),
        ]);
        $event2 = $this->groupContentService->createGroupEvent($this->group, $this->user, [
            'title' => 'Event 2',
            'description' => 'Desc 3',
            'location_name' => 'Loc 2',
            'location_coordinates' => new \MatanYadaev\EloquentSpatial\Objects\Point(2, 2),
            'start_time' => now()->addDays(3),
            'end_time' => now()->addDays(4),
        ]);

        $events = $this->groupContentService->getGroupEvents($this->group);

        expect($events->count())->toBe(2);
        expect($events->pluck('id'))->toContain($event1->id, $event2->id);
    });
});
