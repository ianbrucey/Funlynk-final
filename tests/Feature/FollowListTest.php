<?php

use App\Livewire\Profile\FollowList;
use App\Models\Follow;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;

function listViewer(array $attributes = []): User
{
    return User::factory()->create($attributes + ['onboarding_completed_at' => now()]);
}

function followEdge(User $follower, User $following, ?string $at = null): void
{
    Follow::create(['follower_id' => $follower->id, 'following_id' => $following->id, 'created_at' => $at ?? now()]);
}

function listFor(User $viewer, User $profile, string $tab = 'followers')
{
    return Livewire::actingAs($viewer)->test(FollowList::class, ['username' => $profile->username, 'tab' => $tab]);
}

test('followers list shows followers newest first', function () {
    $profile = listViewer();
    $old = listViewer(['display_name' => 'Older Fan']);
    $new = listViewer(['display_name' => 'Newer Fan']);
    $stranger = listViewer(['display_name' => 'Total Stranger']);
    followEdge($old, $profile, now()->subDays(2));
    followEdge($new, $profile, now()->subHour());
    followEdge($profile, $stranger);

    listFor(listViewer(), $profile)
        ->assertSeeInOrder(['Newer Fan', 'Older Fan'])
        ->assertDontSee('Total Stranger');
});

test('following list shows followed users', function () {
    $profile = listViewer();
    $host = listViewer(['display_name' => 'Hosty McHost']);
    $fan = listViewer(['display_name' => 'Only A Fan']);
    followEdge($profile, $host);
    followEdge($fan, $profile);

    listFor(listViewer(), $profile, 'following')->assertSee('Hosty McHost')->assertDontSee('Only A Fan');
});

test('header counts link to the lists', function () {
    $profile = listViewer();

    Livewire::actingAs(listViewer())->test('profile.show-profile', ['username' => $profile->username])
        ->assertSee(route('profile.followers', $profile->username), false)
        ->assertSee(route('profile.following', $profile->username), false);

    $this->actingAs(listViewer())->get(route('profile.followers', $profile->username))->assertOk();
    $this->actingAs(listViewer())->get(route('profile.following', $profile->username))->assertOk();
});

test('lists respect profile visibility', function () {
    $fan = listViewer(['display_name' => 'Hidden Fan']);
    $followersOnly = listViewer(['privacy_level' => 'followers']);
    $private = listViewer(['privacy_level' => 'private']);
    followEdge($fan, $followersOnly);
    followEdge($fan, $private);

    $outsider = listViewer();
    listFor($outsider, $followersOnly)->assertSee('only visible to followers')->assertDontSee('Hidden Fan');
    listFor($outsider, $private)->assertSee('This profile is private')->assertDontSee('Hidden Fan');

    followEdge($outsider, $followersOnly);
    listFor($outsider, $followersOnly)->assertSee('Hidden Fan');
    listFor($outsider, $private)->assertDontSee('Hidden Fan');
    listFor($private, $private)->assertSee('Hidden Fan');
});

test('inactive users are excluded and header counts match the lists', function () {
    $profile = listViewer();
    $active = listViewer(['display_name' => 'Active Ann']);
    $gone = listViewer(['display_name' => 'Gone Gary', 'is_active' => false]);
    followEdge($active, $profile);
    followEdge($gone, $profile);

    listFor(listViewer(), $profile)->assertSee('Active Ann')->assertDontSee('Gone Gary');
    Livewire::actingAs(listViewer())->test('profile.show-profile', ['username' => $profile->username])->assertSet('followersCount', 1);
});

test('lists page without duplicates', function () {
    $profile = listViewer();
    foreach (range(1, 25) as $i) {
        followEdge(listViewer(['display_name' => sprintf('Fan %02d', $i)]), $profile, now()->subMinutes($i));
    }

    $component = listFor(listViewer(), $profile)->assertSee('Fan 01')->assertSee('Fan 20')->assertDontSee('Fan 21')->assertSee('Load more');
    $component->call('loadMore')->assertSee('Fan 25')->assertDontSee('Load more');
});

test('list follow pill follows and unfollows, and own row has none', function () {
    $profile = listViewer();
    $viewer = listViewer(['display_name' => 'Me Myself']);
    $other = listViewer(['display_name' => 'Casey Other']);
    followEdge($viewer, $profile);
    followEdge($other, $profile);

    $component = listFor($viewer, $profile)->assertSee('+ Follow Casey Other');
    expect(substr_count($component->html(), 'aria-label="Follow Me Myself"'))->toBe(0);

    $component->call('followHost', $other->id)->assertSee('✓ Following Casey Other');
    expect(Follow::where('follower_id', $viewer->id)->where('following_id', $other->id)->exists())->toBeTrue();

    $component->call('unfollowHost', $other->id)->assertSee('+ Follow Casey Other');
    expect(Follow::where('follower_id', $viewer->id)->where('following_id', $other->id)->exists())->toBeFalse();
});

test('lists use constant queries', function () {
    $count = function (int $rows) {
        $profile = listViewer();
        foreach (range(1, $rows) as $i) {
            followEdge(listViewer(), $profile);
        }
        $viewer = listViewer();
        DB::flushQueryLog();
        DB::enableQueryLog();
        listFor($viewer, $profile);
        $n = count(DB::getQueryLog());
        DB::disableQueryLog();

        return $n;
    };

    expect($count(15))->toBe($count(3));
});

test('list unknown user is 404', function () {
    $this->actingAs(listViewer())->get('/u/nobody-here-at-all/followers')->assertNotFound();
    $this->actingAs(listViewer())->get('/u/nobody-here-at-all/following')->assertNotFound();
});

test('list search filters and escapes', function () {
    $profile = listViewer();
    followEdge(listViewer(['display_name' => 'Ana Rivera', 'username' => 'ana.rivera']), $profile);
    followEdge(listViewer(['display_name' => 'Lee Park', 'username' => 'lee.park']), $profile);
    followEdge(listViewer(['display_name' => '100% Real', 'username' => 'hundred.real']), $profile);
    followEdge(listViewer(['display_name' => 'Zed Zed', 'username' => 'zedzed']), $profile);

    $component = listFor(listViewer(), $profile)
        ->set('search', 'ANA')->assertSee('Ana Rivera')->assertDontSee('Lee Park')
        ->set('search', 'lee.p')->assertSee('Lee Park')->assertDontSee('Ana Rivera')
        ->set('search', '%')->assertSee('100% Real')->assertDontSee('Zed Zed')
        ->set('search', '_')->assertDontSee('Zed Zed')->assertDontSee('Lee Park')
        ->set('search', 'nobody-matches')->assertSee('No one matches');

    $component->call('$set', 'search', '')->assertSee('Lee Park');

    $private = listViewer(['privacy_level' => 'private']);
    followEdge(listViewer(['display_name' => 'Secret Sue', 'username' => 'secret.sue']), $private);
    listFor(listViewer(), $private)->set('search', 'secret')->assertDontSee('Secret Sue');
});
