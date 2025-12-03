# Testing Conventions

**Generated:** 2025-12-02

## Testing Framework

**Primary:** Pest v4  
**Underlying:** PHPUnit  
**Location:** `tests/`

## Test Structure

```
tests/
├── Feature/          # Integration/feature tests
│   ├── Services/     # Service class tests
│   ├── Livewire/     # Livewire component tests
│   ├── Events/       # Event/listener tests
│   └── Database/     # Database/migration tests
├── Unit/             # Unit tests (isolated logic)
├── Pest.php          # Pest configuration
└── TestCase.php      # Base test case
```

## Pest Configuration

**File:** `tests/Pest.php`

```php
<?php

use Tests\TestCase;

uses(TestCase::class)->in('Feature', 'Unit');
```

## Test Case Base Class

**File:** `tests/TestCase.php`

```php
<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    use CreatesApplication;
}
```

## Feature Test Patterns

### Service Class Tests

**Location:** `tests/Feature/Services/`

**Pattern:**
```php
<?php

use App\Models\User;
use App\Models\Post;
use App\Services\PostService;

beforeEach(function () {
    $this->service = app(PostService::class);
    $this->user = User::factory()->create();
});

test('creates post with valid data', function () {
    $data = [
        'user_id' => $this->user->id,
        'title' => 'Test Post',
        'description' => 'Test description',
        'location_name' => 'San Francisco',
        'latitude' => 37.7749,
        'longitude' => -122.4194,
    ];
    
    $post = $this->service->createPost($data);
    
    expect($post)
        ->toBeInstanceOf(Post::class)
        ->title->toBe('Test Post')
        ->user_id->toBe($this->user->id);
    
    $this->assertDatabaseHas('posts', [
        'id' => $post->id,
        'title' => 'Test Post',
    ]);
});

test('throws exception when user_id is missing', function () {
    $data = [
        'title' => 'Test Post',
    ];
    
    $this->service->createPost($data);
})->throws(InvalidArgumentException::class);

test('dispatches PostCreated event', function () {
    Event::fake();
    
    $data = [
        'user_id' => $this->user->id,
        'title' => 'Test Post',
        'location_name' => 'San Francisco',
        'latitude' => 37.7749,
        'longitude' => -122.4194,
    ];
    
    $post = $this->service->createPost($data);
    
    Event::assertDispatched(PostCreated::class, function ($event) use ($post) {
        return $event->post->id === $post->id;
    });
});
```

### Livewire Component Tests

**Location:** `tests/Feature/Livewire/`

**Pattern:**
```php
<?php

use App\Livewire\Posts\CreatePost;
use App\Models\User;
use Livewire\Livewire;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->actingAs($this->user);
});

test('renders create post component', function () {
    Livewire::test(CreatePost::class)
        ->assertStatus(200)
        ->assertSee('Create Post');
});

test('creates post with valid input', function () {
    Livewire::test(CreatePost::class)
        ->set('title', 'Test Post')
        ->set('description', 'Test description')
        ->set('locationName', 'San Francisco')
        ->set('latitude', 37.7749)
        ->set('longitude', -122.4194)
        ->call('save')
        ->assertHasNoErrors()
        ->assertRedirect(route('feed.nearby'));
    
    $this->assertDatabaseHas('posts', [
        'title' => 'Test Post',
        'user_id' => $this->user->id,
    ]);
});

test('validates required fields', function () {
    Livewire::test(CreatePost::class)
        ->set('title', '')
        ->call('save')
        ->assertHasErrors(['title' => 'required']);
});

test('updates property reactively', function () {
    Livewire::test(CreatePost::class)
        ->set('title', 'New Title')
        ->assertSet('title', 'New Title');
});
```

### Event/Listener Tests

**Location:** `tests/Feature/Events/`

**Pattern:**
```php
<?php

use App\Events\PostCreated;
use App\Listeners\SendPostNotification;
use App\Models\Post;
use App\Models\User;

test('PostCreated event is dispatched', function () {
    Event::fake();
    
    $user = User::factory()->create();
    $post = Post::factory()->create(['user_id' => $user->id]);
    
    event(new PostCreated($post));
    
    Event::assertDispatched(PostCreated::class);
});

test('listener creates notification', function () {
    $user = User::factory()->create();
    $post = Post::factory()->create(['user_id' => $user->id]);
    
    $listener = new SendPostNotification();
    $listener->handle(new PostCreated($post));
    
    $this->assertDatabaseHas('notifications', [
        'user_id' => $user->id,
        'type' => 'post_created',
    ]);
});
```

### Database/Migration Tests

**Location:** `tests/Feature/Database/`

**Pattern:**
```php
<?php

use App\Models\Post;
use App\Models\User;

test('posts table has correct columns', function () {
    $this->assertTrue(Schema::hasTable('posts'));
    $this->assertTrue(Schema::hasColumns('posts', [
        'id', 'user_id', 'title', 'description', 
        'location_coordinates', 'expires_at'
    ]));
});

test('post belongs to user', function () {
    $user = User::factory()->create();
    $post = Post::factory()->create(['user_id' => $user->id]);
    
    expect($post->user)
        ->toBeInstanceOf(User::class)
        ->id->toBe($user->id);
});

test('PostGIS coordinates are stored correctly', function () {
    $post = Post::factory()->create([
        'location_coordinates' => new Point(37.7749, -122.4194),
    ]);
    
    expect($post->location_coordinates)
        ->toBeInstanceOf(Point::class)
        ->latitude->toBe(37.7749)
        ->longitude->toBe(-122.4194);
});
```

## Factory Patterns

**Location:** `database/factories/`

**Pattern:**
```php
<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use MatanYadaev\EloquentSpatial\Objects\Point;

class PostFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'title' => fake()->sentence(),
            'description' => fake()->paragraph(),
            'location_name' => fake()->city(),
            'location_coordinates' => new Point(
                fake()->latitude(),
                fake()->longitude()
            ),
            'expires_at' => now()->addHours(48),
        ];
    }
    
    public function expired(): static
    {
        return $this->state(fn (array $attributes) => [
            'expires_at' => now()->subHours(1),
        ]);
    }
}
```

**Usage:**
```php
// Create single model
$post = Post::factory()->create();

// Create with specific attributes
$post = Post::factory()->create(['title' => 'Custom Title']);

// Create multiple
$posts = Post::factory()->count(5)->create();

// Use state
$expiredPost = Post::factory()->expired()->create();
```

## Assertion Patterns

### Pest Expectations

```php
// Model assertions
expect($post)
    ->toBeInstanceOf(Post::class)
    ->title->toBe('Expected Title')
    ->user_id->toBe($user->id);

// Collection assertions
expect($posts)
    ->toHaveCount(5)
    ->each->toBeInstanceOf(Post::class);

// Boolean assertions
expect($result)->toBeTrue();
expect($result)->toBeFalse();

// Null assertions
expect($value)->toBeNull();
expect($value)->not->toBeNull();
```

### PHPUnit Assertions (via Pest)

```php
// Database assertions
$this->assertDatabaseHas('posts', ['title' => 'Test']);
$this->assertDatabaseMissing('posts', ['title' => 'Deleted']);

// Response assertions
$response = $this->get('/posts');
$response->assertStatus(200);
$response->assertSee('Posts');

// Authentication assertions
$this->assertAuthenticated();
$this->assertGuest();
```

## Test Helpers

### Common Setup

```php
beforeEach(function () {
    // Runs before each test
    $this->user = User::factory()->create();
    $this->actingAs($this->user);
});

afterEach(function () {
    // Runs after each test (cleanup)
});
```

### Shared Test Data

```php
dataset('post_titles', [
    'Short Title',
    'A Much Longer Title That Tests Length Limits',
    'Title with Special Characters !@#$%',
]);

test('validates post titles', function ($title) {
    $post = Post::factory()->create(['title' => $title]);
    expect($post->title)->toBe($title);
})->with('post_titles');
```

## Running Tests

```bash
# Run all tests
php artisan test

# Run specific test file
php artisan test tests/Feature/PostServiceTest.php

# Run tests matching filter
php artisan test --filter=PostService

# Run with coverage
php artisan test --coverage

# Run in parallel
php artisan test --parallel
```

## Test Organization Best Practices

1. **One test file per class** - `PostService.php` → `PostServiceTest.php`
2. **Descriptive test names** - Use natural language
3. **AAA Pattern** - Arrange, Act, Assert
4. **Use factories** - Don't manually create test data
5. **Test one thing** - Each test should verify one behavior
6. **Clean up** - Use database transactions (automatic in Laravel)

