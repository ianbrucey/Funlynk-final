# Service Layer Pattern

**Standard:** All business logic goes in service classes following this exact pattern.

## Service Class Template

**Location:** `app/Services/FeatureService.php`

```php
<?php

namespace App\Services;

use App\Models\ModelName;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class FeatureService
{
    // ============================================
    // CREATE OPERATIONS
    // ============================================
    
    /**
     * Create a new resource.
     *
     * @param array $data
     * @return ModelName
     * @throws \Exception
     */
    public function create(array $data): ModelName
    {
        return DB::transaction(function () use ($data) {
            try {
                // Validate data (optional - validation may be in controller/component)
                $this->validateData($data);
                
                // Create the model
                $model = ModelName::create($data);
                
                // Perform related operations
                $this->performRelatedOperations($model);
                
                // Dispatch events
                event(new ResourceCreated($model));
                
                // Log the action
                Log::info('Resource created', ['id' => $model->id]);
                
                return $model;
                
            } catch (\Exception $e) {
                Log::error('Failed to create resource', [
                    'error' => $e->getMessage(),
                    'data' => $data,
                ]);
                throw $e;
            }
        });
    }
    
    // ============================================
    // READ OPERATIONS
    // ============================================
    
    /**
     * Get a resource by ID.
     *
     * @param string $id
     * @return ModelName|null
     */
    public function find(string $id): ?ModelName
    {
        return ModelName::find($id);
    }
    
    /**
     * Get a resource by ID or throw exception.
     *
     * @param string $id
     * @return ModelName
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     */
    public function findOrFail(string $id): ModelName
    {
        return ModelName::findOrFail($id);
    }
    
    /**
     * Get all resources with optional filtering.
     *
     * @param array $filters
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getAll(array $filters = [])
    {
        $query = ModelName::query();
        
        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }
        
        if (isset($filters['user_id'])) {
            $query->where('user_id', $filters['user_id']);
        }
        
        return $query->latest()->get();
    }
    
    // ============================================
    // UPDATE OPERATIONS
    // ============================================
    
    /**
     * Update an existing resource.
     *
     * @param ModelName $model
     * @param array $data
     * @return ModelName
     * @throws \Exception
     */
    public function update(ModelName $model, array $data): ModelName
    {
        return DB::transaction(function () use ($model, $data) {
            try {
                // Validate data
                $this->validateData($data);
                
                // Store old values for comparison
                $oldValues = $model->getAttributes();
                
                // Update the model
                $model->update($data);
                
                // Perform related operations
                $this->performRelatedOperations($model);
                
                // Dispatch events
                event(new ResourceUpdated($model, $oldValues));
                
                // Log the action
                Log::info('Resource updated', [
                    'id' => $model->id,
                    'changes' => $model->getChanges(),
                ]);
                
                return $model->fresh();
                
            } catch (\Exception $e) {
                Log::error('Failed to update resource', [
                    'id' => $model->id,
                    'error' => $e->getMessage(),
                ]);
                throw $e;
            }
        });
    }
    
    // ============================================
    // DELETE OPERATIONS
    // ============================================
    
    /**
     * Delete a resource.
     *
     * @param ModelName $model
     * @return bool
     * @throws \Exception
     */
    public function delete(ModelName $model): bool
    {
        return DB::transaction(function () use ($model) {
            try {
                // Perform cleanup operations
                $this->performCleanup($model);
                
                // Delete the model
                $deleted = $model->delete();
                
                // Dispatch events
                event(new ResourceDeleted($model));
                
                // Log the action
                Log::info('Resource deleted', ['id' => $model->id]);
                
                return $deleted;
                
            } catch (\Exception $e) {
                Log::error('Failed to delete resource', [
                    'id' => $model->id,
                    'error' => $e->getMessage(),
                ]);
                throw $e;
            }
        });
    }
    
    // ============================================
    // HELPER METHODS
    // ============================================
    
    /**
     * Validate data before create/update.
     *
     * @param array $data
     * @throws \InvalidArgumentException
     */
    private function validateData(array $data): void
    {
        if (empty($data['required_field'])) {
            throw new \InvalidArgumentException('Required field is missing');
        }
    }
    
    /**
     * Perform related operations after create/update.
     *
     * @param ModelName $model
     */
    private function performRelatedOperations(ModelName $model): void
    {
        // Update related models
        // Index in search
        // Update counters
        // etc.
    }
    
    /**
     * Perform cleanup before delete.
     *
     * @param ModelName $model
     */
    private function performCleanup(ModelName $model): void
    {
        // Delete related records
        // Remove from search index
        // Update counters
        // etc.
    }
}
```

## Service Class Patterns

### Simple Service (No Complex Logic)

```php
class UserService
{
    public function create(array $data): User
    {
        return DB::transaction(function () use ($data) {
            $user = User::create($data);
            event(new UserCreated($user));
            return $user;
        });
    }
    
    public function update(User $user, array $data): User
    {
        $user->update($data);
        event(new UserUpdated($user));
        return $user->fresh();
    }
    
    public function delete(User $user): bool
    {
        event(new UserDeleted($user));
        return $user->delete();
    }
}
```

### Complex Service (Multiple Models)

```php
class PostService
{
    public function createWithReactions(array $data): Post
    {
        return DB::transaction(function () use ($data) {
            // Create post
            $post = Post::create($data);
            
            // Create initial reactions
            $post->reactions()->create([
                'user_id' => auth()->id(),
                'type' => 'created',
            ]);
            
            // Update user stats
            auth()->user()->increment('posts_count');
            
            // Index in search
            $post->searchable();
            
            // Notify followers
            event(new PostCreated($post));
            
            return $post;
        });
    }
}
```

### Service with External API

```php
class PaymentService
{
    public function __construct(
        private StripeService $stripe,
    ) {}
    
    public function processPayment(array $data): Payment
    {
        return DB::transaction(function () use ($data) {
            // Create payment record
            $payment = Payment::create($data);
            
            try {
                // Process with Stripe
                $stripePayment = $this->stripe->charge(
                    $payment->amount,
                    $payment->user->stripe_customer_id
                );
                
                // Update payment with Stripe ID
                $payment->update([
                    'stripe_payment_id' => $stripePayment->id,
                    'status' => 'completed',
                ]);
                
                // Dispatch event
                event(new PaymentProcessed($payment));
                
            } catch (\Exception $e) {
                // Mark as failed
                $payment->update(['status' => 'failed']);
                
                // Log error
                Log::error('Payment processing failed', [
                    'payment_id' => $payment->id,
                    'error' => $e->getMessage(),
                ]);
                
                throw $e;
            }
            
            return $payment;
        });
    }
}
```

## Dependency Injection

**Always use dependency injection for services:**

```php
// ✅ CORRECT: Inject in constructor
class PostController extends Controller
{
    public function __construct(
        private PostService $postService,
    ) {}
    
    public function store(Request $request)
    {
        $post = $this->postService->create($request->validated());
        return redirect()->route('posts.show', $post);
    }
}

// ❌ WRONG: Use app() helper
public function store(Request $request)
{
    $post = app(PostService::class)->create($request->validated());
}
```

**In Livewire components, use app() helper:**

```php
class CreatePost extends Component
{
    public function save()
    {
        $validated = $this->validate();
        app(PostService::class)->create($validated);
        session()->flash('success', 'Post created!');
    }
}
```

## Error Handling

**Always wrap operations in try-catch:**

```php
public function create(array $data): Post
{
    return DB::transaction(function () use ($data) {
        try {
            $post = Post::create($data);
            event(new PostCreated($post));
            return $post;
            
        } catch (\Exception $e) {
            Log::error('Post creation failed', [
                'error' => $e->getMessage(),
                'data' => $data,
            ]);
            throw $e;
        }
    });
}
```

## Transactions

**Always use transactions for multi-step operations:**

```php
// ✅ CORRECT: Wrapped in transaction
public function create(array $data): Post
{
    return DB::transaction(function () use ($data) {
        $post = Post::create($data);
        $post->reactions()->create([...]);
        return $post;
    });
}

// ❌ WRONG: No transaction
public function create(array $data): Post
{
    $post = Post::create($data);
    $post->reactions()->create([...]); // Could fail after post is created
    return $post;
}
```

## Logging

**Always log important operations:**

```php
public function create(array $data): Post
{
    return DB::transaction(function () use ($data) {
        $post = Post::create($data);
        
        // Log success
        Log::info('Post created', [
            'id' => $post->id,
            'user_id' => $post->user_id,
        ]);
        
        return $post;
    });
}
```

## Completion Checklist

When creating a service class, verify:

- [ ] Class is in `app/Services/` directory
- [ ] Class has clear, specific methods (create, update, delete, find)
- [ ] All operations are wrapped in transactions
- [ ] All operations have error handling
- [ ] All operations have logging
- [ ] Events are dispatched for important actions
- [ ] Methods have PHPDoc blocks
- [ ] Methods have type hints
- [ ] Dependencies are injected
- [ ] No business logic in controllers/components

