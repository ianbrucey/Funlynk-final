<?php

use App\Jobs\NotifyFollowersOfNewEvent;
use App\Livewire\Activities\CreateActivity;
use App\Models\Activity;
use App\Models\Follow;
use App\Models\Group;
use App\Models\Notification;
use App\Models\Post;
use App\Models\User;
use App\Services\ActivityConversionService;
use App\Services\GroupContentService;
use App\Services\RecurringScheduleService;
use Illuminate\Support\Facades\Queue;
use Livewire\Livewire;
use MatanYadaev\EloquentSpatial\Objects\Point;

function followerOf(User $host, array $attributes = []): User
{
    $follower = User::factory()->create(['onboarding_completed_at' => now()] + $attributes);
    Follow::create(['follower_id' => $follower->id, 'following_id' => $host->id]);

    return $follower;
}

function followNotificationsFor(User $user)
{
    return Notification::where('user_id', $user->id)->where('type', 'followed_host_event')->get();
}

function publishedEventBy(User $host, array $attributes = []): Activity
{
    return Activity::factory()->create(['host_id' => $host->id, 'status' => 'published', 'start_time' => now()->addDay(), 'is_public' => true] + $attributes);
}

test('follower notified when host publishes event', function () {
    $host = User::factory()->create(['display_name' => 'Skate Sam']);
    $follower = followerOf($host);
    $event = publishedEventBy($host, ['title' => 'Pier Jam']);

    NotifyFollowersOfNewEvent::dispatchSync($event->id);

    $notes = followNotificationsFor($follower);
    expect($notes)->toHaveCount(1);
    expect($notes->first()->message)->toContain('Skate Sam')->toContain('Pier Jam');
    expect($notes->first()->data['url'])->toBe(route('events.show', $event->id));
});

test('publishing through the create event form notifies followers', function () {
    $host = User::factory()->create(['onboarding_completed_at' => now()]);
    $follower = followerOf($host);

    Livewire::actingAs($host)->test(CreateActivity::class)
        ->set('title', 'Sunrise Yoga')
        ->set('description', 'Easy morning flow on the beach')
        ->set('location_name', 'Junipero Beach')
        ->set('latitude', 33.7622)
        ->set('longitude', -118.1647)
        ->set('start_time', now()->addDay()->format('Y-m-d\TH:i'))
        ->call('createActivity')
        ->assertHasNoErrors();

    expect(followNotificationsFor($follower))->toHaveCount(1);
});

test('non follower not notified', function () {
    $host = User::factory()->create();
    $stranger = User::factory()->create();

    NotifyFollowersOfNewEvent::dispatchSync(publishedEventBy($host)->id);

    expect(followNotificationsFor($stranger))->toHaveCount(0);
});

test('private group event does not notify non member follower', function () {
    $host = User::factory()->create();
    $follower = followerOf($host);
    $group = Group::factory()->create(['privacy' => 'private']);
    $event = publishedEventBy($host, ['group_id' => $group->id, 'is_public' => false]);

    NotifyFollowersOfNewEvent::dispatchSync($event->id);

    expect(followNotificationsFor($follower))->toHaveCount(0);
});

test('public group event notifies non member follower but not group member follower', function () {
    $host = User::factory()->create();
    $outsider = followerOf($host);
    $member = followerOf($host);
    $group = Group::factory()->create(['privacy' => 'public']);
    $group->memberships()->create(['user_id' => $member->id, 'role' => 'member']);

    NotifyFollowersOfNewEvent::dispatchSync(publishedEventBy($host, ['group_id' => $group->id, 'is_public' => false])->id);

    expect(followNotificationsFor($outsider))->toHaveCount(1);
    expect(followNotificationsFor($member))->toHaveCount(0);
});

test('group event service notifies public follower exactly once', function () {
    $host = User::factory()->create(['onboarding_completed_at' => now()]);
    $follower = followerOf($host);
    $group = Group::factory()->create(['privacy' => 'public', 'created_by' => $host->id]);
    $group->memberships()->create(['user_id' => $host->id, 'role' => 'admin']);

    app(GroupContentService::class)->createGroupEvent($group, $host, [
        'title' => 'Group Jam', 'description' => 'Fun', 'location_name' => 'Beach',
        'location_coordinates' => new Point(33.76, -118.16), 'start_time' => now()->addDay(),
    ]);

    expect(followNotificationsFor($follower))->toHaveCount(1);
});

test('preference none suppresses follow notification', function () {
    $host = User::factory()->create();
    $quiet = followerOf($host, ['notification_preference' => 'none']);
    $emailOnly = followerOf($host, ['notification_preference' => 'email_only']);
    $normal = followerOf($host, ['notification_preference' => 'all']);

    NotifyFollowersOfNewEvent::dispatchSync(publishedEventBy($host)->id);

    expect(followNotificationsFor($quiet))->toHaveCount(0);
    expect(followNotificationsFor($emailOnly))->toHaveCount(0);
    expect(followNotificationsFor($normal))->toHaveCount(1);
});

test('recurring schedule sends single notification', function () {
    $host = User::factory()->create(['onboarding_completed_at' => now()]);
    $follower = followerOf($host);
    $group = Group::factory()->create(['privacy' => 'public', 'created_by' => $host->id]);
    $group->memberships()->create(['user_id' => $host->id, 'role' => 'admin']);

    $schedule = app(RecurringScheduleService::class)->createSchedule($group, $host, [
        'title' => 'Weekly Skate', 'location_name' => 'Beach', 'location_coordinates' => new Point(33.76, -118.16),
        'frequency' => 'daily', 'start_time' => '18:00:00', 'duration_minutes' => 60,
    ]);

    expect($schedule->activities()->count())->toBeGreaterThan(3);
    $notes = followNotificationsFor($follower);
    expect($notes)->toHaveCount(1);
    expect($notes->first()->data['recurring'])->toBeTrue();
});

test('converted event notifies followers once and skips interested users', function () {
    $host = User::factory()->create();
    $follower = followerOf($host);
    $interested = followerOf($host);
    $post = Post::factory()->create(['user_id' => $host->id, 'status' => 'active', 'reaction_count' => 5]);
    $post->reactions()->create(['user_id' => $interested->id, 'reaction_type' => 'im_down']);

    app(ActivityConversionService::class)->createFromPost($post, [
        'start_time' => now()->addDay()->toDateTimeString(), 'end_time' => now()->addDay()->addHours(2)->toDateTimeString(),
        'max_attendees' => 20, 'price' => 0,
    ], $host);

    expect(followNotificationsFor($follower))->toHaveCount(1);
    expect(followNotificationsFor($interested))->toHaveCount(0);
});

test('follow notification payload links event', function () {
    $host = User::factory()->create(['display_name' => 'Skate Sam']);
    $follower = followerOf($host);
    $event = publishedEventBy($host);

    NotifyFollowersOfNewEvent::dispatchSync($event->id);

    $data = followNotificationsFor($follower)->first()->data;
    expect($data['activity_id'])->toBe($event->id)->and($data['host_name'])->toBe('Skate Sam');
    expect(json_encode($data))->not->toContain('location_coordinates');
});

test('follow notification is queued', function () {
    Queue::fake();
    $host = User::factory()->create(['onboarding_completed_at' => now()]);
    Livewire::actingAs($host)->test(CreateActivity::class)
        ->set('title', 'Queued Event')->set('description', 'Long enough description')
        ->set('location_name', 'Somewhere')->set('latitude', 33.7)->set('longitude', -118.1)
        ->set('start_time', now()->addDay()->format('Y-m-d\TH:i'))
        ->call('createActivity');

    Queue::assertPushed(NotifyFollowersOfNewEvent::class);
});

test('unfollow before the job runs means no notification', function () {
    $host = User::factory()->create();
    $follower = followerOf($host);
    $event = publishedEventBy($host);
    Follow::where('follower_id', $follower->id)->delete();

    NotifyFollowersOfNewEvent::dispatchSync($event->id);

    expect(followNotificationsFor($follower))->toHaveCount(0);
});
