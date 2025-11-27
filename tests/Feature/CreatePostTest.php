<?php

use App\Models\User;
use App\Models\Post;
use Livewire\Livewire;
use App\Livewire\Posts\CreatePost;
use MatanYadaev\EloquentSpatial\Objects\Point;

beforeEach(function () {
    $this->user = User::factory()->create([
        'location_coordinates' => new Point(37.7749, -122.4194, 4326), // San Francisco
    ]);
    $this->actingAs($this->user);
});

it('can render the create post page', function () {
    $response = $this->get(route('posts.create'));
    
    $response->assertStatus(200);
    $response->assertSeeLivewire(CreatePost::class);
});

it('can create a post with required fields', function () {
    Livewire::test(CreatePost::class)
        ->set('title', 'Coffee at Starbucks')
        ->set('location_name', 'Starbucks Downtown')
        ->set('latitude', 37.7749)
        ->set('longitude', -122.4194)
        ->call('createPost')
        ->assertHasNoErrors()
        ->assertRedirect(route('feed.nearby'));
    
    expect(Post::count())->toBe(1);
    
    $post = Post::first();
    expect($post->title)->toBe('Coffee at Starbucks');
    expect($post->location_name)->toBe('Starbucks Downtown');
    expect($post->user_id)->toBe($this->user->id);
    expect($post->status)->toBe('active');
});

it('can create a post with all fields', function () {
    Livewire::test(CreatePost::class)
        ->set('title', 'Basketball game')
        ->set('description', 'Looking for players for a pickup game')
        ->set('location_name', 'Golden Gate Park')
        ->set('latitude', 37.7694)
        ->set('longitude', -122.4862)
        ->set('time_hint', 'In 2 hours')
        ->set('mood', 'active')
        ->set('selectedTags', [
            ['id' => 1, 'name' => 'basketball'],
            ['id' => 2, 'name' => 'sports'],
        ])
        ->set('ttl_hours', 24)
        ->call('createPost')
        ->assertHasNoErrors();
    
    $post = Post::first();
    expect($post->title)->toBe('Basketball game');
    expect($post->description)->toBe('Looking for players for a pickup game');
    expect($post->time_hint)->toBe('In 2 hours');
    expect($post->mood)->toBe('active');
    expect($post->tags)->toBeArray();
    expect($post->expires_at)->not->toBeNull();
});

it('validates required fields', function () {
    Livewire::test(CreatePost::class)
        ->set('title', '')
        ->set('location_name', '')
        ->call('createPost')
        ->assertHasErrors(['title', 'location_name', 'latitude', 'longitude']);
});

it('validates title length', function () {
    Livewire::test(CreatePost::class)
        ->set('title', 'ab') // Too short
        ->call('createPost')
        ->assertHasErrors(['title']);
});

it('validates description length', function () {
    Livewire::test(CreatePost::class)
        ->set('title', 'Valid title')
        ->set('description', str_repeat('a', 501)) // Too long
        ->set('location_name', 'Valid location')
        ->set('latitude', 37.7749)
        ->set('longitude', -122.4194)
        ->call('createPost')
        ->assertHasErrors(['description']);
});

it('validates ttl_hours range', function () {
    Livewire::test(CreatePost::class)
        ->set('title', 'Valid title')
        ->set('location_name', 'Valid location')
        ->set('latitude', 37.7749)
        ->set('longitude', -122.4194)
        ->set('ttl_hours', 12) // Too short
        ->call('createPost')
        ->assertHasErrors(['ttl_hours']);

    Livewire::test(CreatePost::class)
        ->set('title', 'Valid title')
        ->set('location_name', 'Valid location')
        ->set('latitude', 37.7749)
        ->set('longitude', -122.4194)
        ->set('ttl_hours', 96) // Too long (max is 72)
        ->call('createPost')
        ->assertHasErrors(['ttl_hours']);
});

it('can add and remove tags', function () {
    Livewire::test(CreatePost::class)
        ->set('newTag', 'basketball')
        ->call('addTag')
        ->assertSet('selectedTags', function ($tags) {
            return count($tags) === 1 && $tags[0]['name'] === 'basketball';
        })
        ->call('removeTag', 0)
        ->assertSet('selectedTags', []);
});

it('limits tags to 5', function () {
    $component = Livewire::test(CreatePost::class);
    
    for ($i = 1; $i <= 5; $i++) {
        $component->set('newTag', "tag{$i}")->call('addTag');
    }
    
    $component
        ->set('newTag', 'tag6')
        ->call('addTag')
        ->assertHasErrors(['selectedTags']);
});

