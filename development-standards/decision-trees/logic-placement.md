# Decision Tree: Where Should Business Logic Go?

**Purpose:** Deterministic rules for placing business logic in the correct layer.

## The Decision Tree

```
Does this logic involve multiple models or complex business rules?
├─ YES → Service Class (app/Services/)
│   Examples:
│   - Creating a post with reactions and notifications
│   - Converting a post to an event
│   - Processing payments with Stripe
│   - Calculating recommendation scores
│
└─ NO
   ├─ Is this a simple query or relationship access?
   │  └─ YES → Model Method (app/Models/)
   │      Examples:
   │      - Getting active posts: $user->activePosts()
   │      - Checking if user can edit: $post->isEditableBy($user)
   │      - Formatting data: $post->getFormattedDate()
   │
   └─ NO
      ├─ Is this UI-specific logic (validation, form state)?
      │  └─ YES → Livewire Component (app/Livewire/)
      │      Examples:
      │      - Form validation rules
      │      - UI state management (modals, tabs)
      │      - User input handling
      │
      └─ NO
         ├─ Is this HTTP-specific logic (redirects, responses)?
         │  └─ YES → Controller (app/Http/Controllers/)
         │      Examples:
         │      - Route handling
         │      - Response formatting
         │      - Middleware application
         │
         └─ NO
            ├─ Is this background/async work?
            │  └─ YES → Job (app/Jobs/)
            │      Examples:
            │      - Sending emails
            │      - Processing images
            │      - Expiring old posts
            │
            └─ NO
               └─ Is this a side effect of an action?
                  └─ YES → Event + Listener (app/Events/, app/Listeners/)
                      Examples:
                      - Sending notifications after post creation
                      - Updating analytics after user action
                      - Broadcasting WebSocket events
```

## Layer Responsibilities

### Service Layer (app/Services/)

**Responsibility:** Complex business logic involving multiple models or external services

**When to use:**
- ✅ Logic involves 2+ models
- ✅ Logic involves external APIs (Stripe, Meilisearch)
- ✅ Logic has multiple steps that should be transactional
- ✅ Logic is reused across multiple controllers/components

**Example:**
```php
// app/Services/PostService.php
class PostService
{
    public function createPost(array $data): Post
    {
        return DB::transaction(function () use ($data) {
            // Create post
            $post = Post::create($data);
            
            // Create initial reactions
            $post->reactions()->create([...]);
            
            // Notify followers
            event(new PostCreated($post));
            
            // Index in search
            $post->searchable();
            
            return $post;
        });
    }
}
```

### Model Layer (app/Models/)

**Responsibility:** Simple queries, relationships, data formatting

**When to use:**
- ✅ Defining relationships (hasMany, belongsTo)
- ✅ Simple queries (scopes, accessors)
- ✅ Data formatting (getters, mutators)
- ✅ Single-model business rules

**Example:**
```php
// app/Models/Post.php
class Post extends Model
{
    // Relationship
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
    
    // Scope
    public function scopeActive($query)
    {
        return $query->where('expires_at', '>', now());
    }
    
    // Business rule
    public function isEditableBy(User $user): bool
    {
        return $this->user_id === $user->id 
            && $this->created_at->diffInMinutes(now()) < 15;
    }
    
    // Accessor
    public function getFormattedDateAttribute(): string
    {
        return $this->created_at->format('M j, Y');
    }
}
```

### Livewire Component Layer (app/Livewire/)

**Responsibility:** UI logic, form validation, user interaction

**When to use:**
- ✅ Form validation rules
- ✅ UI state (modals, tabs, toggles)
- ✅ User input handling
- ✅ Calling service classes

**Example:**
```php
// app/Livewire/Posts/CreatePost.php
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
        
        // Call service class for business logic
        app(PostService::class)->create($validated);
        
        session()->flash('success', 'Post created!');
        return redirect()->route('feed.nearby');
    }
}
```

### Controller Layer (app/Http/Controllers/)

**Responsibility:** HTTP request/response handling

**When to use:**
- ✅ API endpoints
- ✅ Traditional form submissions (non-Livewire)
- ✅ File downloads
- ✅ Redirects

**Example:**
```php
// app/Http/Controllers/PostController.php
class PostController extends Controller
{
    public function store(Request $request)
    {
        $validated = $request->validate([...]);
        
        // Call service class
        $post = app(PostService::class)->create($validated);
        
        return redirect()
            ->route('posts.show', $post)
            ->with('success', 'Post created!');
    }
}
```

### Job Layer (app/Jobs/)

**Responsibility:** Background/async processing

**When to use:**
- ✅ Sending emails
- ✅ Processing images/files
- ✅ Long-running operations
- ✅ Scheduled tasks

**Example:**
```php
// app/Jobs/ExpirePostsJob.php
class ExpirePostsJob implements ShouldQueue
{
    public function handle(): void
    {
        Post::where('expires_at', '<', now())
            ->where('status', 'active')
            ->update(['status' => 'expired']);
    }
}
```

### Event/Listener Layer (app/Events/, app/Listeners/)

**Responsibility:** Side effects and decoupled actions

**When to use:**
- ✅ Sending notifications
- ✅ Updating analytics
- ✅ Broadcasting WebSocket events
- ✅ Triggering multiple actions from one event

**Example:**
```php
// app/Events/PostCreated.php
class PostCreated
{
    public function __construct(public Post $post) {}
}

// app/Listeners/SendPostNotification.php
class SendPostNotification
{
    public function handle(PostCreated $event): void
    {
        $event->post->user->followers->each(function ($follower) use ($event) {
            Notification::create([
                'user_id' => $follower->id,
                'type' => 'new_post',
                'data' => ['post_id' => $event->post->id],
            ]);
        });
    }
}
```

## Common Scenarios

### Scenario 1: Creating a Post

**Question:** Where should the logic go?

**Answer:** Service class

**Reasoning:** Involves multiple steps (create post, notify followers, index in search)

```php
// ✅ CORRECT: Service class
app(PostService::class)->create($data);

// ❌ WRONG: Livewire component
public function save()
{
    $post = Post::create($this->validate());
    event(new PostCreated($post));
    $post->searchable();
    // ... too much logic in component
}
```

### Scenario 2: Checking if User Can Edit Post

**Question:** Where should the logic go?

**Answer:** Model method

**Reasoning:** Simple business rule involving single model

```php
// ✅ CORRECT: Model method
$post->isEditableBy($user);

// ❌ WRONG: Service class (overkill)
app(PostService::class)->canEdit($post, $user);
```

### Scenario 3: Form Validation

**Question:** Where should the logic go?

**Answer:** Livewire component

**Reasoning:** UI-specific validation rules

```php
// ✅ CORRECT: Livewire component
protected function rules(): array
{
    return [
        'title' => ['required', 'string', 'max:255'],
    ];
}

// ❌ WRONG: Service class (validation is UI concern)
```

### Scenario 4: Sending Welcome Email

**Question:** Where should the logic go?

**Answer:** Job (queued)

**Reasoning:** Async operation that doesn't block user

```php
// ✅ CORRECT: Job
SendWelcomeEmail::dispatch($user);

// ❌ WRONG: Service class (blocks user)
Mail::to($user)->send(new WelcomeEmail($user));
```

## Anti-Patterns to Avoid

### ❌ Fat Controllers

**Don't put business logic in controllers:**
```php
// ❌ WRONG
public function store(Request $request)
{
    $post = Post::create($request->validated());
    $post->reactions()->create([...]);
    event(new PostCreated($post));
    $post->searchable();
    // ... too much logic
}
```

**Do call service classes:**
```php
// ✅ CORRECT
public function store(Request $request)
{
    $post = app(PostService::class)->create($request->validated());
    return redirect()->route('posts.show', $post);
}
```

### ❌ Fat Models

**Don't put complex business logic in models:**
```php
// ❌ WRONG
class Post extends Model
{
    public function createWithReactions(array $data)
    {
        DB::transaction(function () use ($data) {
            // ... complex multi-step logic
        });
    }
}
```

**Do use service classes:**
```php
// ✅ CORRECT
app(PostService::class)->createWithReactions($data);
```

### ❌ Fat Livewire Components

**Don't put business logic in components:**
```php
// ❌ WRONG
public function save()
{
    $post = Post::create($this->validate());
    $post->reactions()->create([...]);
    event(new PostCreated($post));
    // ... too much logic
}
```

**Do call service classes:**
```php
// ✅ CORRECT
public function save()
{
    app(PostService::class)->create($this->validate());
    session()->flash('success', 'Post created!');
}
```

## Completion Checklist

When placing logic, verify:

- [ ] Complex business logic is in service classes
- [ ] Simple queries are in model methods
- [ ] UI logic is in Livewire components
- [ ] HTTP logic is in controllers
- [ ] Async work is in jobs
- [ ] Side effects are in event listeners
- [ ] No fat controllers/models/components

