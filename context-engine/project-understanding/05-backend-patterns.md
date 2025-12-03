# Backend Patterns & Conventions

**Generated:** 2025-12-02

## Service Layer Architecture

FunLynk uses a **service-oriented architecture** where business logic is encapsulated in service classes.

### Service Class Pattern

**Location:** `app/Services/`

**Structure:**
```php
<?php

namespace App\Services;

use App\Models\ModelName;
use Illuminate\Support\Facades\DB;

class FeatureService
{
    /**
     * Create a new resource.
     *
     * @param array $data
     * @return ModelName
     */
    public function create(array $data): ModelName
    {
        return DB::transaction(function () use ($data) {
            // Business logic
            $model = ModelName::create($data);
            
            // Dispatch events
            event(new ResourceCreated($model));
            
            return $model;
        });
    }
    
    /**
     * Update an existing resource.
     *
     * @param ModelName $model
     * @param array $data
     * @return ModelName
     */
    public function update(ModelName $model, array $data): ModelName
    {
        DB::transaction(function () use ($model, $data) {
            $model->update($data);
            
            // Additional business logic
            
            event(new ResourceUpdated($model));
        });
        
        return $model->fresh();
    }
}
```

### Key Service Classes

1. **PostService** - Post creation, reactions, invitations
2. **ActivityService** - Event management, RSVPs
3. **ActivityConversionService** - Post → Event conversion
4. **ConversionEligibilityService** - Conversion threshold checks
5. **ChatService** - Multi-context messaging
6. **GroupService** - Group management
7. **FeedService** - Content discovery and ranking
8. **RecommendationEngine** - Personalized content ranking
9. **PaymentService** - Payment processing
10. **StripeConnectService** - Host payout management

## Model Conventions

### Model Structure

**Location:** `app/Models/`

**Standard Pattern:**
```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ModelName extends Model
{
    use HasFactory, HasUuids;
    
    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'column1',
        'column2',
    ];
    
    /**
     * Get the attributes that should be cast.
     */
    protected function casts(): array
    {
        return [
            'created_at' => 'datetime',
            'json_column' => 'array',
        ];
    }
    
    /**
     * Relationships
     */
    public function relatedModel(): BelongsTo
    {
        return $this->belongsTo(RelatedModel::class);
    }
    
    public function children(): HasMany
    {
        return $this->hasMany(ChildModel::class);
    }
}
```

### PostGIS Spatial Models

Models with location use `MatanYadaev\EloquentSpatial`:

```php
use MatanYadaev\EloquentSpatial\Objects\Point;
use MatanYadaev\EloquentSpatial\Traits\HasSpatial;

class Post extends Model
{
    use HasSpatial;
    
    protected function casts(): array
    {
        return [
            'location_coordinates' => Point::class,
        ];
    }
}
```

**Spatial Queries:**
```php
// Find posts within 10km
Post::whereDistance('location_coordinates', $userLocation, '<=', 10000)->get();

// Order by distance
Post::orderByDistance('location_coordinates', $userLocation)->get();
```

## Event-Driven Architecture

### Event Pattern

**Location:** `app/Events/`

```php
<?php

namespace App\Events;

use App\Models\ModelName;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ResourceCreated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;
    
    public function __construct(
        public ModelName $model
    ) {}
    
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('user.' . $this->model->user_id),
        ];
    }
    
    public function broadcastAs(): string
    {
        return 'resource.created';
    }
}
```

### Listener Pattern

**Location:** `app/Listeners/`

```php
<?php

namespace App\Listeners;

use App\Events\ResourceCreated;
use App\Models\Notification;

class SendResourceNotification
{
    public function handle(ResourceCreated $event): void
    {
        Notification::create([
            'user_id' => $event->model->user_id,
            'type' => 'resource_created',
            'data' => [
                'resource_id' => $event->model->id,
                'message' => 'Resource created successfully',
            ],
        ]);
    }
}
```

### Event Registration

**Location:** `app/Providers/EventServiceProvider.php`

```php
protected $listen = [
    ResourceCreated::class => [
        SendResourceNotification::class,
        UpdateAnalytics::class,
    ],
];
```

## Validation Patterns

### Form Request Validation

**Location:** `app/Http/Requests/`

```php
<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CreateResourceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Or policy check
    }
    
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
            'location_name' => ['required', 'string'],
            'latitude' => ['required', 'numeric', 'between:-90,90'],
            'longitude' => ['required', 'numeric', 'between:-180,180'],
        ];
    }
    
    public function messages(): array
    {
        return [
            'title.required' => 'Please provide a title.',
            'location_name.required' => 'Please specify a location.',
        ];
    }
}
```

### Livewire Component Validation

```php
class CreatePost extends Component
{
    public $title;
    public $description;
    
    protected function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
        ];
    }
    
    public function save()
    {
        $validated = $this->validate();
        
        // Use service
        app(PostService::class)->create($validated);
        
        session()->flash('success', 'Post created!');
        return redirect()->route('feed.nearby');
    }
}
```

## Authorization Patterns

### Policy Classes

**Location:** `app/Policies/`

```php
<?php

namespace App\Policies;

use App\Models\Post;
use App\Models\User;

class PostPolicy
{
    public function view(User $user, Post $post): bool
    {
        // Public posts visible to all
        if ($post->privacy === 'public') {
            return true;
        }
        
        // Private posts only to creator
        return $user->id === $post->user_id;
    }
    
    public function update(User $user, Post $post): bool
    {
        return $user->id === $post->user_id;
    }
    
    public function delete(User $user, Post $post): bool
    {
        return $user->id === $post->user_id;
    }
}
```

### Policy Usage

**In Controllers/Livewire:**
```php
$this->authorize('update', $post);
```

**In Blade:**
```blade
@can('update', $post)
    <button>Edit</button>
@endcan
```

## Database Transaction Pattern

Always wrap multi-step operations in transactions:

```php
use Illuminate\Support\Facades\DB;

public function complexOperation(array $data): Model
{
    return DB::transaction(function () use ($data) {
        $model = Model::create($data);
        
        // Related operations
        $model->relatedModels()->create([...]);
        
        // Update counters
        $model->user->increment('activity_count');
        
        // Dispatch events
        event(new ModelCreated($model));
        
        return $model;
    });
}
```

## Error Handling

### Service Layer Exceptions

```php
use InvalidArgumentException;
use Illuminate\Database\Eloquent\ModelNotFoundException;

public function findOrFail(string $id): Model
{
    $model = Model::find($id);
    
    if (!$model) {
        throw new ModelNotFoundException("Model not found: {$id}");
    }
    
    return $model;
}

public function create(array $data): Model
{
    if (empty($data['required_field'])) {
        throw new InvalidArgumentException('Required field is missing');
    }
    
    return Model::create($data);
}
```

### Livewire Error Handling

```php
public function save()
{
    try {
        $validated = $this->validate();
        app(Service::class)->create($validated);
        
        session()->flash('success', 'Success!');
        return redirect()->route('index');
        
    } catch (\Exception $e) {
        session()->flash('error', 'An error occurred: ' . $e->getMessage());
    }
}
```

## Queue Job Pattern

**Location:** `app/Jobs/`

```php
<?php

namespace App\Jobs;

use App\Models\Post;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ProcessResource implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    
    public function __construct(
        public Post $post
    ) {}
    
    public function handle(): void
    {
        // Background processing logic
    }
}
```

**Dispatching:**
```php
ProcessResource::dispatch($post);
```

