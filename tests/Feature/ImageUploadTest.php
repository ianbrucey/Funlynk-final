<?php

use App\Livewire\Onboarding\OnboardingWizard;
use App\Models\User;
use App\Services\ImageProcessor;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

it('shrinks large uploads to the preset and stores webp', function () {
    Storage::fake('public');
    $file = UploadedFile::fake()->image('big.jpg', 4000, 3000);

    $path = app(ImageProcessor::class)->store($file, 'activities', 'cover', 'public');

    expect($path)->toStartWith('activities/')->toEndWith('.webp');
    [$width, $height] = getimagesizefromstring(Storage::disk('public')->get($path));
    expect($width)->toBe(1200)->and($height)->toBe(900);
});

it('does not upscale small uploads', function () {
    Storage::fake('public');
    $file = UploadedFile::fake()->image('small.png', 300, 200);

    $path = app(ImageProcessor::class)->store($file, 'activities', 'cover', 'public');

    [$width, $height] = getimagesizefromstring(Storage::disk('public')->get($path));
    expect($width)->toBe(300)->and($height)->toBe(200);
});

it('crops avatars to a square', function () {
    Storage::fake('public');
    $file = UploadedFile::fake()->image('wide.jpg', 1000, 500);

    $path = app(ImageProcessor::class)->store($file, 'profiles', 'avatar', 'public');

    [$width, $height] = getimagesizefromstring(Storage::disk('public')->get($path));
    expect($width)->toBe(400)->and($height)->toBe(400);
});

it('lets a user skip the photo and finish onboarding', function () {
    $user = User::factory()->create(['onboarding_completed_at' => null, 'profile_image_url' => null]);

    Livewire::actingAs($user)->test(OnboardingWizard::class)
        ->set('location_name', 'Atlanta, GA')
        ->set('latitude', 33.749)
        ->set('longitude', -84.388)
        ->call('nextStepFromLocation')
        ->assertSet('currentStep', 2)
        ->call('skipPhoto')
        ->assertSet('currentStep', 3)
        ->call('complete')
        ->assertRedirect(route('feed.nearby'));

    expect($user->fresh()->hasCompletedOnboarding())->toBeTrue()
        ->and($user->fresh()->profile_image_url)->toBeNull();
});
