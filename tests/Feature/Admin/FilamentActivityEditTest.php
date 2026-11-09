<?php

use App\Filament\Resources\Activities\Pages\EditActivity;
use App\Models\Activity;
use App\Models\User;
use Livewire\Livewire;
use MatanYadaev\EloquentSpatial\Objects\Point;

function filamentAdmin(): User
{
    return User::factory()->create(['email' => 'funlynk_admin@funlynk.com']);
}

it('renders the filament edit page for an external listing', function () {
    $this->actingAs(filamentAdmin());
    $listing = Activity::factory()->listing()->create();

    Livewire::test(EditActivity::class, ['record' => $listing->getRouteKey()])
        ->assertOk();
});

it('renders the filament edit page for a hosted activity', function () {
    $admin = filamentAdmin();
    $this->actingAs($admin);
    // The update policy allows the host to edit; the admin is the host here.
    $activity = Activity::factory()->create(['host_id' => $admin->id]);

    Livewire::test(EditActivity::class, ['record' => $activity->getRouteKey()])
        ->assertOk();
});

it('saves an edited title and preserves the coordinates', function () {
    $this->actingAs(filamentAdmin());
    $listing = Activity::factory()->listing()->create([
        'title' => 'Original Listing Title',
        'location_coordinates' => new Point(33.77005, -118.18373),
    ]);

    Livewire::test(EditActivity::class, ['record' => $listing->getRouteKey()])
        ->fillForm(['title' => 'Updated Listing Title'])
        ->call('save')
        ->assertHasNoFormErrors();

    $listing->refresh();
    expect($listing->title)->toBe('Updated Listing Title')
        ->and($listing->location_coordinates)->not->toBeNull()
        ->and(round($listing->location_coordinates->latitude, 5))->toBe(33.77005)
        ->and(round($listing->location_coordinates->longitude, 5))->toBe(-118.18373);
});

it('presents coordinates as lat, lng text and saves an edited pair as a Point', function () {
    $this->actingAs(filamentAdmin());
    $listing = Activity::factory()->listing()->create([
        'location_coordinates' => new Point(33.77005, -118.18373),
    ]);

    $component = Livewire::test(EditActivity::class, ['record' => $listing->getRouteKey()])
        ->assertFormSet(['location_coordinates' => '33.77005, -118.18373']);

    $component->fillForm(['location_coordinates' => '34.05223, -118.24368'])
        ->call('save')
        ->assertHasNoFormErrors();

    $listing->refresh();
    expect(round($listing->location_coordinates->latitude, 5))->toBe(34.05223)
        ->and(round($listing->location_coordinates->longitude, 5))->toBe(-118.24368);
});
