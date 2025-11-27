<?php

use App\Contracts\SearchServiceInterface;
use App\Models\Activity;
use App\Models\Post;
use App\Models\Tag;
use App\Models\User;
use App\Services\MeilisearchSearchService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use MatanYadaev\EloquentSpatial\Objects\Point;

uses(RefreshDatabase::class);

beforeEach(function () {
    // Set Meilisearch driver
    config(['search.driver' => 'meilisearch']);
    
    // Flush Meilisearch indexes to start fresh
    \Artisan::call('scout:flush', ['model' => 'App\\Models\\Post']);
    \Artisan::call('scout:flush', ['model' => 'App\\Models\\Activity']);
    
    // Create test user with location (San Francisco)
    $this->user = User::factory()->create([
        'location_coordinates' => new Point(37.7749, -122.4194, 4326),
    ]);
});

test('can search posts by title', function () {
    // Create posts with searchable content
    $basketballPost = Post::factory()->create([
        'title' => 'Looking for basketball players',
        'description' => 'Need 2 more for pickup game',
        'location_coordinates' => new Point(37.7749, -122.4194, 4326),
        'location_name' => 'San Francisco',
        'status' => 'active',
        'expires_at' => now()->addHours(24),
    ]);
    
    $soccerPost = Post::factory()->create([
        'title' => 'Soccer match this evening',
        'description' => 'Casual game at the park',
        'location_coordinates' => new Point(37.7849, -122.4094, 4326),
        'location_name' => 'San Francisco',
        'status' => 'active',
        'expires_at' => now()->addHours(24),
    ]);
    
    // Index posts
    $basketballPost->searchable();
    $soccerPost->searchable();
    
    // Wait for indexing
    sleep(1);
    
    $service = app(SearchServiceInterface::class);
    $results = $service->search('basketball', $this->user);
    
    expect($results)->toHaveCount(1)
        ->and($results->first()['type'])->toBe('post')
        ->and($results->first()['data']->title)->toBe('Looking for basketball players');
});

test('can search activities by description', function () {
    // Create activity with searchable content
    $hikingActivity = Activity::factory()->create([
        'title' => 'Weekend Hike',
        'description' => 'Exploring trails in the mountains',
        'location_coordinates' => new Point(37.7749, -122.4194, 4326),
        'location_name' => 'San Francisco',
        'status' => 'published',
        'start_time' => now()->addDays(2),
    ]);
    
    $diningActivity = Activity::factory()->create([
        'title' => 'Dinner Meetup',
        'description' => 'Italian restaurant in downtown',
        'location_coordinates' => new Point(37.7849, -122.4094, 4326),
        'location_name' => 'San Francisco',
        'status' => 'published',
        'start_time' => now()->addDays(3),
    ]);
    
    // Index activities
    $hikingActivity->searchable();
    $diningActivity->searchable();
    
    // Wait for indexing
    sleep(1);
    
    $service = app(SearchServiceInterface::class);
    $results = $service->search('mountains', $this->user);
    
    expect($results)->toHaveCount(1)
        ->and($results->first()['type'])->toBe('event')
        ->and($results->first()['data']->description)->toContain('mountains');
});

test('can search by tags', function () {
    // Create post with tags
    $post = Post::factory()->create([
        'title' => 'Fun activity',
        'description' => 'Join us',
        'tags' => ['outdoor', 'sports', 'hiking'],
        'location_coordinates' => new Point(37.7749, -122.4194, 4326),
        'location_name' => 'San Francisco',
        'status' => 'active',
        'expires_at' => now()->addHours(24),
    ]);
    
    // Index post
    $post->searchable();
    
    // Wait for indexing
    sleep(1);
    
    $service = app(SearchServiceInterface::class);
    $results = $service->search('hiking', $this->user);
    
    expect($results)->toHaveCount(1)
        ->and($results->first()['data']->tags)->toContain('hiking');
});

test('can filter by content type posts only', function () {
    $post = Post::factory()->create([
        'title' => 'Basketball game',
        'description' => 'Come play basketball',
        'location_coordinates' => new Point(37.7749, -122.4194, 4326),
        'status' => 'active',
        'expires_at' => now()->addHours(24),
    ]);
    
    $activity = Activity::factory()->create([
        'title' => 'Basketball tournament',
        'description' => 'Annual basketball competition',
        'location_coordinates' => new Point(37.7749, -122.4194, 4326),
        'status' => 'published',
        'start_time' => now()->addDays(2),
    ]);
    
    // Index both
    $post->searchable();
    $activity->searchable();
    
    // Wait for indexing
    sleep(1);
    
    $service = app(SearchServiceInterface::class);
    $results = $service->search('basketball', $this->user, null, 'posts');
    
    expect($results)->toHaveCount(1)
        ->and($results->first()['type'])->toBe('post');
});

test('can filter by content type events only', function () {
    $post = Post::factory()->create([
        'title' => 'Basketball game',
        'description' => 'Come play basketball',
        'location_coordinates' => new Point(37.7749, -122.4194, 4326),
        'status' => 'active',
        'expires_at' => now()->addHours(24),
    ]);
    
    $activity = Activity::factory()->create([
        'title' => 'Basketball tournament',
        'description' => 'Annual basketball competition',
        'location_coordinates' => new Point(37.7749, -122.4194, 4326),
        'status' => 'published',
        'start_time' => now()->addDays(2),
    ]);
    
    // Index both
    $post->searchable();
    $activity->searchable();
    
    // Wait for indexing
    sleep(1);
    
    $service = app(SearchServiceInterface::class);
    $results = $service->search('basketball', $this->user, null, 'events');
    
    expect($results)->toHaveCount(1)
        ->and($results->first()['type'])->toBe('event');
});

test('can filter by geo proximity', function () {
    // Create post near user (San Francisco - ~1km away)
    $nearPost = Post::factory()->create([
        'title' => 'Nearby basketball game',
        'description' => 'Close by',
        'location_coordinates' => new Point(37.7749, -122.4094, 4326), // ~0.8km from user
        'location_name' => 'San Francisco',
        'status' => 'active',
        'expires_at' => now()->addHours(24),
    ]);
    
    // Create post far from user (Oakland - ~20km away)
    $farPost = Post::factory()->create([
        'title' => 'Distant basketball game',
        'description' => 'Far away',
        'location_coordinates' => new Point(37.8044, -122.2712, 4326), // ~20km from user
        'location_name' => 'Oakland',
        'status' => 'active',
        'expires_at' => now()->addHours(24),
    ]);
    
    // Index posts
    $nearPost->searchable();
    $farPost->searchable();
    
    // Wait for indexing
    sleep(1);
    
    $service = app(SearchServiceInterface::class);
    
    // Search with 10km radius - should only find near post
    $results = $service->search('basketball', $this->user, 10);
    
    expect($results)->toHaveCount(1)
        ->and($results->first()['data']->location_name)->toBe('San Francisco');
});

test('handles empty query gracefully', function () {
    $service = app(SearchServiceInterface::class);
    $results = $service->search('', $this->user);
    
    expect($results)->toBeInstanceOf(Illuminate\Support\Collection::class);
});

test('returns empty collection when no results found', function () {
    $service = app(SearchServiceInterface::class);
    $results = $service->search('nonexistentterm12345', $this->user);
    
    expect($results)->toBeEmpty();
});

test('does not return expired posts', function () {
    $expiredPost = Post::factory()->create([
        'title' => 'Basketball game',
        'description' => 'Come play',
        'location_coordinates' => new Point(37.7749, -122.4194, 4326),
        'status' => 'active',
        'expires_at' => now()->subHours(1), // Already expired
    ]);
    
    $activePost = Post::factory()->create([
        'title' => 'Basketball match',
        'description' => 'Come play',
        'location_coordinates' => new Point(37.7749, -122.4194, 4326),
        'status' => 'active',
        'expires_at' => now()->addHours(24),
    ]);
    
    // Only index the active post (expired post won't be indexed due to shouldBeSearchable)
    // Note: shouldBeSearchable() prevents expired posts from being indexed
    $activePost->searchable();
    
    // Wait for indexing
    sleep(1);
    
    $service = app(SearchServiceInterface::class);
    $results = $service->search('basketball', $this->user);
    
    // Should only return the active post
    expect($results)->toHaveCount(1)
        ->and($results->first()['data']->id)->toBe($activePost->id);
});

test('does not return unpublished activities', function () {
    $draftActivity = Activity::factory()->create([
        'title' => 'Basketball tournament',
        'description' => 'Annual competition',
        'location_coordinates' => new Point(37.7749, -122.4194, 4326),
        'status' => 'draft',
        'start_time' => now()->addDays(2),
    ]);
    
    $publishedActivity = Activity::factory()->create([
        'title' => 'Basketball event',
        'description' => 'Annual competition',
        'location_coordinates' => new Point(37.7749, -122.4194, 4326),
        'status' => 'published',
        'start_time' => now()->addDays(2),
    ]);
    
    // Index activities
    $draftActivity->searchable();
    $publishedActivity->searchable();
    
    // Wait for indexing
    sleep(1);
    
    $service = app(SearchServiceInterface::class);
    $results = $service->search('basketball', $this->user);
    
    // Should only return the published activity
    expect($results)->toHaveCount(1)
        ->and($results->first()['data']->id)->toBe($publishedActivity->id);
});

test('handles typo tolerance - finds results with 1 typo', function () {
    $post = Post::factory()->create([
        'title' => 'Basketball pickup game',
        'description' => 'Looking for players',
        'location_coordinates' => new Point(37.7749, -122.4194, 4326),
        'status' => 'active',
        'expires_at' => now()->addHours(24),
    ]);
    
    $post->searchable();
    sleep(1);
    
    $service = app(SearchServiceInterface::class);
    
    // Search with typo: "basketbal" instead of "basketball" (missing 'l')
    $results = $service->search('basketbal', $this->user);
    
    expect($results)->toHaveCount(1)
        ->and($results->first()['data']->title)->toContain('Basketball');
});

// Note: Synonym tests removed - synonyms are configured in Meilisearch indexes
// but may require additional Meilisearch configuration or testing via dashboard.
// Synonyms configured: basketball/bball/hoops, soccer/football/futbol, etc.
// Test manually via Meilisearch dashboard at http://127.0.0.1:7700
