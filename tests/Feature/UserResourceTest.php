<?php

use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use MatanYadaev\EloquentSpatial\Objects\Point;

test('user can have profile image uploaded', function () {
    Storage::fake('public');

    $user = User::factory()->create();
    $file = UploadedFile::fake()->image('profile.jpg', 200, 200);

    $user->update([
        'profile_image_url' => $file->store('profiles', 'public'),
    ]);

    expect($user->profile_image_url)->not->toBeNull();
    Storage::disk('public')->assertExists($user->profile_image_url);
});

test('user can have interests as array', function () {
    $user = User::factory()->create([
        'interests' => ['hiking', 'photography', 'cooking'],
    ]);

    expect($user->interests)
        ->toBeArray()
        ->toHaveCount(3)
        ->toContain('hiking');
});

test('user can have location coordinates as PostGIS point', function () {
    $user = User::factory()->create([
        'location_name' => 'San Francisco, CA',
        'location_coordinates' => new Point(37.7749, -122.4194),
    ]);

    expect($user->location_coordinates)
        ->toBeInstanceOf(Point::class)
        ->and($user->location_coordinates->latitude)->toBe(37.7749)
        ->and($user->location_coordinates->longitude)->toBe(-122.4194);
});

test('user profile fields are fillable', function () {
    $user = User::factory()->create();

    $user->update([
        'bio' => 'I love outdoor activities!',
        'display_name' => 'John Doe',
        'location_name' => 'Oakland, CA',
        'interests' => ['hiking', 'biking'],
    ]);

    expect($user->bio)->toBe('I love outdoor activities!')
        ->and($user->display_name)->toBe('John Doe')
        ->and($user->location_name)->toBe('Oakland, CA')
        ->and($user->interests)->toHaveCount(2);
});
