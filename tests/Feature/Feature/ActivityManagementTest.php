<?php

use App\Livewire\Activities\ActivityDetail;
use App\Livewire\Activities\CreateActivity;
use App\Livewire\Activities\EditActivity;
use App\Models\Activity;
use App\Models\Post;
use App\Models\Tag;
use App\Models\User;
use App\Services\ActivityService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use MatanYadaev\EloquentSpatial\Objects\Point;

beforeEach(function () {
    Storage::fake('public');
});

describe('Activity Service', function () {
    it('can create activity from post', function () {
        $user = User::factory()->create();
        $post = Post::factory()->create([
            'user_id' => $user->id,
            'content' => 'Test Content',
            'location_name' => 'Test Location',
            'location_coordinates' => new Point(40.7128, -74.0060),
        ]);

        $service = app(ActivityService::class);
        $activity = $service->createFromPost($post);

        expect($activity)
            ->title->toBe('Test Content')
            ->description->toBe('Test Content')
            ->host_id->toBe($user->id)
            ->originated_from_post_id->toBe($post->id)
            ->status->toBe('draft');
            
        expect($activity->location_coordinates->latitude)->toBe(40.7128);
    });

    it('validates capacity correctly', function () {
        $activity = Activity::factory()->create([
            'max_attendees' => 10,
            'current_attendees' => 8,
        ]);

        $service = app(ActivityService::class);

        // Can add 2
        $result = $service->validateCapacity($activity, 2);
        expect($result['valid'])->toBeTrue();

        // Cannot add 3
        $result = $service->validateCapacity($activity, 3);
        expect($result['valid'])->toBeFalse();
    });

    it('validates status transitions', function () {
        $activity = Activity::factory()->create(['status' => 'draft']);
        $service = app(ActivityService::class);

        // Draft -> Published (Valid)
        expect($service->updateStatus($activity, 'published'))->toBeTrue();
        expect($activity->fresh()->status)->toBe('published');

        // Published -> Completed (Invalid, must go to active first)
        expect($service->updateStatus($activity, 'completed'))->toBeFalse();
    });
});

describe('Activity Policy', function () {
    it('allows host to update activity', function () {
        $host = User::factory()->create();
        $activity = Activity::factory()->create(['host_id' => $host->id]);

        expect($host->can('update', $activity))->toBeTrue();
    });

    it('denies others from updating activity', function () {
        $host = User::factory()->create();
        $other = User::factory()->create();
        $activity = Activity::factory()->create(['host_id' => $host->id]);

        expect($other->can('update', $activity))->toBeFalse();
    });

    it('prevents deletion if attendees exist', function () {
        $host = User::factory()->create();
        $activity = Activity::factory()->create([
            'host_id' => $host->id,
            'current_attendees' => 5
        ]);

        expect($host->can('delete', $activity))->toBeFalse();
    });
});

describe('Create Activity Component', function () {
    it('can render create page', function () {
        $user = User::factory()->create();
        
        Livewire::actingAs($user)
            ->test(CreateActivity::class)
            ->assertStatus(200);
    });

    it('can create activity', function () {
        $user = User::factory()->create();
        $tag = Tag::factory()->create();
        
        Livewire::actingAs($user)
            ->test(CreateActivity::class)
            ->set('title', 'New Activity')
            ->set('description', 'Description here')
            ->set('activity_type', 'sports')
            ->set('location_name', 'Central Park')
            ->set('latitude', 40.7829)
            ->set('longitude', -73.9654)
            ->set('start_time', now()->addDay()->format('Y-m-d\TH:i'))
            ->set('selectedTags', [['id' => $tag->id, 'name' => $tag->name]])
            ->call('createActivity')
            ->assertHasNoErrors()
            ->assertSessionMissing('error');

        $activity = Activity::where('title', 'New Activity')->first();
        expect($activity)->not->toBeNull();
        expect($activity->tags->first()->id)->toBe($tag->id);
    });

    it('validates required fields', function () {
        $user = User::factory()->create();
        
        Livewire::actingAs($user)
            ->test(CreateActivity::class)
            ->call('createActivity')
            ->assertHasErrors(['title', 'description', 'location_name', 'latitude']);
    });
});

describe('Edit Activity Component', function () {
    it('loads existing activity data', function () {
        $user = User::factory()->create();
        $activity = Activity::factory()->create(['host_id' => $user->id]);
        $activity->refresh(); // Ensure casts are applied
        
        Livewire::actingAs($user)
            ->test(EditActivity::class, ['activity' => $activity])
            ->assertSet('title', $activity->title)
            ->assertSet('description', $activity->description);
    });

    it('can update activity', function () {
        $user = User::factory()->create();
        $activity = Activity::factory()->create(['host_id' => $user->id]);
        $activity->refresh(); // Ensure casts are applied
        
        Livewire::actingAs($user)
            ->test(EditActivity::class, ['activity' => $activity])
            ->set('title', 'Updated Title')
            ->call('updateActivity')
            ->assertHasNoErrors();

        expect($activity->fresh()->title)->toBe('Updated Title');
    });

    it('prevents unauthorized access', function () {
        $owner = User::factory()->create();
        $other = User::factory()->create();
        $activity = Activity::factory()->create(['host_id' => $owner->id]);
        $activity->refresh();
        
        Livewire::actingAs($other)
            ->test(EditActivity::class, ['activity' => $activity])
            ->assertForbidden();
    });
});

describe('Activity Detail Component', function () {
    it('can render public activity', function () {
        $activity = Activity::factory()->create(['is_public' => true]);
        
        Livewire::test(ActivityDetail::class, ['activity' => $activity])
            ->assertStatus(200)
            ->assertSee($activity->title);
    });

    it('allows host to delete activity', function () {
        $user = User::factory()->create();
        $activity = Activity::factory()->create([
            'host_id' => $user->id,
            'current_attendees' => 0
        ]);
        
        Livewire::actingAs($user)
            ->test(ActivityDetail::class, ['activity' => $activity])
            ->call('deleteActivity')
            ->assertHasNoErrors();

        expect(Activity::find($activity->id))->toBeNull();
    });
});
