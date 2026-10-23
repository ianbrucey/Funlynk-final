<?php

use App\Livewire\Profile\InterestedTab;
use App\Models\Activity;
use App\Models\Group;
use App\Models\Post;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use MatanYadaev\EloquentSpatial\Objects\Point;

test('authenticated user can access edit profile page', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('profile.edit'))
        ->assertOk();
});

test('guest cannot access edit profile page', function () {
    $this->get(route('profile.edit'))
        ->assertRedirect(route('login'));
});

test('user can update display name and bio', function () {
    $user = User::factory()->create([
        'display_name' => 'Old Name',
        'bio' => 'Old bio',
    ]);

    Livewire::actingAs($user)
        ->test('profile.edit-profile')
        ->set('display_name', 'New Name')
        ->set('bio', 'New bio about me')
        ->call('save')
        ->assertHasNoErrors()
        ->assertDispatched('profile-updated');

    $user->refresh();
    expect($user->display_name)->toBe('New Name')
        ->and($user->bio)->toBe('New bio about me');
});

test('user can add and remove interests', function () {
    $user = User::factory()->create(['interests' => ['hiking']]);

    $component = Livewire::actingAs($user)
        ->test('profile.edit-profile');

    // Add interest
    $component->set('newInterest', 'photography')
        ->call('addInterest')
        ->assertSet('interests', ['hiking', 'photography']);

    // Remove interest
    $component->call('removeInterest', 0)
        ->assertSet('interests', ['photography']);
});

test('user cannot add more than 10 interests', function () {
    $user = User::factory()->create([
        'interests' => ['1', '2', '3', '4', '5', '6', '7', '8', '9', '10'],
    ]);

    Livewire::actingAs($user)
        ->test('profile.edit-profile')
        ->set('newInterest', '11')
        ->call('addInterest')
        ->assertHasErrors('interests');
});

test('user can upload profile image', function () {
    Storage::fake('s3');
    $user = User::factory()->create();

    $file = UploadedFile::fake()->image('profile.jpg', 200, 200);

    Livewire::actingAs($user)
        ->test('profile.edit-profile')
        ->set('profile_image', $file)
        ->call('save')
        ->assertHasNoErrors();

    $user->refresh();
    expect($user->profile_image_url)->not->toBeNull();
    Storage::disk('s3')->assertExists($user->profile_image_url);
});

test('user can remove profile image', function () {
    Storage::fake('s3');
    $user = User::factory()->create([
        'profile_image_url' => 'profiles/test.jpg',
    ]);

    Storage::disk('s3')->put('profiles/test.jpg', 'fake content');

    Livewire::actingAs($user)
        ->test('profile.edit-profile')
        ->call('removeProfileImage')
        ->assertSet('current_profile_image_url', null);

    $user->refresh();
    expect($user->profile_image_url)->toBeNull();
    Storage::disk('s3')->assertMissing('profiles/test.jpg');
});

test('user can update location with coordinates', function () {
    $user = User::factory()->create();

    Livewire::actingAs($user)
        ->test('profile.edit-profile')
        ->set('location_name', 'San Francisco, CA')
        ->set('latitude', 37.7749)
        ->set('longitude', -122.4194)
        ->call('save')
        ->assertHasNoErrors();

    $user->refresh();
    expect($user->location_name)->toBe('San Francisco, CA')
        ->and($user->location_coordinates)->toBeInstanceOf(Point::class)
        ->and($user->location_coordinates->latitude)->toBe(37.7749)
        ->and($user->location_coordinates->longitude)->toBe(-122.4194);
});

test('bio cannot exceed 500 characters', function () {
    $user = User::factory()->create();

    Livewire::actingAs($user)
        ->test('profile.edit-profile')
        ->set('bio', str_repeat('a', 501))
        ->call('save')
        ->assertHasErrors(['bio']);
});

test('display name cannot exceed 100 characters', function () {
    $user = User::factory()->create();

    Livewire::actingAs($user)
        ->test('profile.edit-profile')
        ->set('display_name', str_repeat('a', 101))
        ->call('save')
        ->assertHasErrors(['display_name']);
});

test('user can update username', function () {
    $user = User::factory()->create(['username' => 'oldusername']);

    Livewire::actingAs($user)
        ->test('profile.edit-profile')
        ->set('username', 'newusername')
        ->call('save')
        ->assertHasNoErrors();

    $user->refresh();
    expect($user->username)->toBe('newusername');
});

test('username must be unique', function () {
    $existingUser = User::factory()->create(['username' => 'takenusername']);
    $user = User::factory()->create(['username' => 'myusername']);

    Livewire::actingAs($user)
        ->test('profile.edit-profile')
        ->set('username', 'takenusername')
        ->call('save')
        ->assertHasErrors(['username']);

    $user->refresh();
    expect($user->username)->toBe('myusername');
});

test('username availability check works', function () {
    $existingUser = User::factory()->create(['username' => 'takenusername']);
    $user = User::factory()->create(['username' => 'myusername']);

    $component = Livewire::actingAs($user)
        ->test('profile.edit-profile');

    // Check available username
    $component->set('username', 'availableusername');
    expect($component->get('usernameAvailable'))->toBeTrue();

    // Check taken username
    $component->set('username', 'takenusername');
    expect($component->get('usernameAvailable'))->toBeFalse();

    // Check own username (should be available)
    $component->set('username', 'myusername');
    expect($component->get('usernameAvailable'))->toBeTrue();
});

test('username must be at least 3 characters', function () {
    $user = User::factory()->create();

    Livewire::actingAs($user)
        ->test('profile.edit-profile')
        ->set('username', 'ab')
        ->call('save')
        ->assertHasErrors(['username']);
});

test('follow is idempotent and counters only change once', function () {
    $viewer = User::factory()->create();
    $target = User::factory()->create(['follower_count' => 0]);

    Livewire::actingAs($viewer)
        ->test('profile.show-profile', ['username' => $target->username])
        ->call('follow')
        ->call('follow')
        ->assertSet('isFollowing', true)
        ->assertSet('followersCount', 1);

    expect($target->fresh()->follower_count)->toBe(1);

    Livewire::actingAs($viewer)
        ->test('profile.show-profile', ['username' => $target->username])
        ->call('unfollow')
        ->assertSet('isFollowing', false)
        ->assertSet('followersCount', 0);

    expect($target->fresh()->follower_count)->toBe(0);
});

test('profile ignores unknown tab names', function () {
    $user = User::factory()->create();

    Livewire::actingAs($user)
        ->test('profile.show-profile')
        ->call('switchTab', 'bogus')
        ->assertSet('activeTab', 'posts')
        ->call('switchTab', 'hosted')
        ->assertSet('activeTab', 'hosted');
});

function profileViewer(): User
{
    return User::factory()->create(['onboarding_completed_at' => now()]);
}

test('other viewers do not see private-group posts or non-public events on a profile', function () {
    $owner = User::factory()->create();
    $private = Group::factory()->create(['privacy' => 'private']);
    $public = Group::factory()->create(['privacy' => 'public']);

    Post::factory()->create(['user_id' => $owner->id, 'title' => 'Standalone Visible']);
    Post::factory()->create(['user_id' => $owner->id, 'group_id' => $public->id, 'title' => 'Public Group Visible']);
    Post::factory()->create(['user_id' => $owner->id, 'group_id' => $private->id, 'title' => 'Private Group Secret']);

    $open = ['host_id' => $owner->id, 'start_time' => now()->addDay()];
    Activity::factory()->create($open + ['title' => 'Open Event', 'status' => 'published']);
    Activity::factory()->create($open + ['title' => 'Cancelled Event', 'status' => 'cancelled']);
    Activity::factory()->create($open + ['title' => 'Draft Event', 'status' => 'draft']);
    Activity::factory()->create($open + ['title' => 'Private Group Event', 'status' => 'published', 'group_id' => $private->id]);

    $viewer = Livewire::actingAs(profileViewer())->test('profile.show-profile', ['username' => $owner->username]);
    $viewer->assertSee('Standalone Visible')->assertSee('Public Group Visible')->assertDontSee('Private Group Secret')
        ->assertSet('postsCount', 2)
        ->call('switchTab', 'hosted')
        ->assertSee('Open Event')->assertDontSee('Cancelled Event')->assertDontSee('Draft Event')->assertDontSee('Private Group Event')
        ->assertSet('hostedActivitiesCount', 1);

    Livewire::actingAs($owner)->test('profile.show-profile')
        ->assertSee('Private Group Secret')->assertSet('postsCount', 3)
        ->call('switchTab', 'hosted')
        ->assertSee('Cancelled Event')->assertSee('Private Group Event');
});

test('followers-only profile hides content until the viewer follows', function () {
    $owner = User::factory()->create(['privacy_level' => 'followers']);
    Post::factory()->create(['user_id' => $owner->id, 'title' => 'Followers Only Post']);

    Livewire::actingAs(profileViewer())->test('profile.show-profile', ['username' => $owner->username])
        ->assertSet('canViewContent', false)
        ->assertDontSee('Followers Only Post')
        ->assertSee('only visible to followers')
        ->call('follow')
        ->assertSet('canViewContent', true)
        ->assertSee('Followers Only Post');
});

test('private profile hides content from everyone but the owner', function () {
    $owner = User::factory()->create(['privacy_level' => 'private']);
    Post::factory()->create(['user_id' => $owner->id, 'title' => 'Hidden Post']);

    Livewire::actingAs(profileViewer())->test('profile.show-profile', ['username' => $owner->username])
        ->assertDontSee('Hidden Post')->assertSee('This profile is private')
        ->call('switchTab', 'interested')
        ->assertDontSee('Converted to Events');

    Livewire::actingAs($owner)->test('profile.show-profile')->assertSee('Hidden Post');
});

test('saved tab hides private-group posts from other viewers and blocks removal by non-owners', function () {
    $owner = User::factory()->create();
    $private = Group::factory()->create(['privacy' => 'private']);
    $secret = Post::factory()->create(['group_id' => $private->id, 'title' => 'Secret Saved Post', 'status' => 'active', 'expires_at' => now()->addDay()]);
    $open = Post::factory()->create(['title' => 'Open Saved Post', 'status' => 'active', 'expires_at' => now()->addDay()]);
    foreach ([$secret, $open] as $post) {
        $post->reactions()->create(['user_id' => $owner->id, 'reaction_type' => 'im_down']);
    }

    Livewire::actingAs(profileViewer())->test(InterestedTab::class, ['user' => $owner])
        ->assertSee('Open Saved Post')->assertDontSee('Secret Saved Post')
        ->call('removeInterest', $open->id)->assertForbidden();

    Livewire::actingAs($owner)->test(InterestedTab::class, ['user' => $owner])
        ->assertSee('Secret Saved Post');
});

test('user can change profile visibility and invalid values are rejected', function () {
    $user = User::factory()->create();

    Livewire::actingAs($user)->test('profile.edit-profile')
        ->set('privacy_level', 'followers')->call('save')->assertHasNoErrors();
    expect($user->fresh()->privacy_level)->toBe('followers');

    Livewire::actingAs($user)->test('profile.edit-profile')
        ->set('privacy_level', 'everyone-ish')->call('save')->assertHasErrors(['privacy_level']);
    expect($user->fresh()->privacy_level)->toBe('followers');
});

test('events tab lists upcoming before past with section headings and badges', function () {
    $owner = User::factory()->create();
    $base = ['host_id' => $owner->id, 'status' => 'published'];
    Activity::factory()->create($base + ['title' => 'Old Skate Jam', 'start_time' => now()->subDays(3)]);
    Activity::factory()->create($base + ['title' => 'Later Hike', 'start_time' => now()->addDays(5)]);
    Activity::factory()->create($base + ['title' => 'Soon Brunch', 'start_time' => now()->addDays(1), 'payment_type' => 'online', 'price_cents' => 500]);

    Livewire::actingAs(profileViewer())->test('profile.show-profile', ['username' => $owner->username])
        ->call('switchTab', 'hosted')
        ->assertSeeInOrder(['Upcoming', 'Soon Brunch', 'Later Hike', 'Past', 'Old Skate Jam'])
        ->assertSee('Paid');
});

test('profile tabs show labels with counts and posts show status badges', function () {
    $owner = User::factory()->create();
    Post::factory()->create(['user_id' => $owner->id, 'title' => 'Live One', 'status' => 'active', 'expires_at' => now()->addDay()]);
    Post::factory()->create(['user_id' => $owner->id, 'title' => 'Gone One', 'status' => 'expired', 'expires_at' => now()->subDay()]);
    Post::factory()->create(['user_id' => $owner->id, 'title' => 'Became Event', 'status' => 'converted', 'expires_at' => now()->addDay()]);

    Livewire::actingAs(profileViewer())->test('profile.show-profile', ['username' => $owner->username])
        ->assertSee('Posts')->assertSee('Events')->assertSee('Going')->assertSee('Saved Posts')
        ->assertSee('Live')->assertSee('Expired')->assertSee('Converted');
});
