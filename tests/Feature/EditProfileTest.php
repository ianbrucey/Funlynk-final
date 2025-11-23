<?php

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
    Storage::fake('public');
    $user = User::factory()->create();

    $file = UploadedFile::fake()->image('profile.jpg', 200, 200);

    Livewire::actingAs($user)
        ->test('profile.edit-profile')
        ->set('profile_image', $file)
        ->call('save')
        ->assertHasNoErrors();

    $user->refresh();
    expect($user->profile_image_url)->not->toBeNull();
    Storage::disk('public')->assertExists($user->profile_image_url);
});

test('user can remove profile image', function () {
    Storage::fake('public');
    $user = User::factory()->create([
        'profile_image_url' => 'profiles/test.jpg',
    ]);

    Storage::disk('public')->put('profiles/test.jpg', 'fake content');

    Livewire::actingAs($user)
        ->test('profile.edit-profile')
        ->call('removeProfileImage')
        ->assertSet('current_profile_image_url', null);

    $user->refresh();
    expect($user->profile_image_url)->toBeNull();
    Storage::disk('public')->assertMissing('profiles/test.jpg');
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
