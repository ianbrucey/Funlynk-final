# Livewire Component Structure

**Standard:** All Livewire components follow this exact structure.

## Standard Component Template

### PHP Class Structure

**Location:** `app/Livewire/Feature/ComponentName.php`

```php
<?php

namespace App\Livewire\Feature;

use App\Models\ModelName;
use App\Services\ServiceName;
use Livewire\Component;
use Livewire\Attributes\On;

class ComponentName extends Component
{
    // ============================================
    // PUBLIC PROPERTIES (bound to view)
    // ============================================
    
    public $propertyName;
    public $anotherProperty;
    
    // ============================================
    // LIFECYCLE HOOKS
    // ============================================
    
    /**
     * Initialize component with data.
     */
    public function mount(?ModelName $model = null): void
    {
        if ($model) {
            $this->propertyName = $model->property_name;
            $this->anotherProperty = $model->another_property;
        }
    }
    
    // ============================================
    // VALIDATION
    // ============================================
    
    /**
     * Validation rules for component properties.
     */
    protected function rules(): array
    {
        return [
            'propertyName' => ['required', 'string', 'max:255'],
            'anotherProperty' => ['nullable', 'string', 'max:1000'],
        ];
    }
    
    /**
     * Custom validation messages.
     */
    protected function messages(): array
    {
        return [
            'propertyName.required' => 'Please provide a value.',
            'propertyName.max' => 'Value cannot exceed 255 characters.',
        ];
    }
    
    // ============================================
    // PUBLIC METHODS (callable from view)
    // ============================================
    
    /**
     * Handle form submission.
     */
    public function save(): mixed
    {
        try {
            $validated = $this->validate();
            
            // Call service class for business logic
            app(ServiceName::class)->create($validated);
            
            session()->flash('success', 'Item created successfully!');
            return redirect()->route('route.name');
            
        } catch (\Exception $e) {
            session()->flash('error', 'Unable to save. Please try again.');
            \Log::error('Component save failed', ['error' => $e->getMessage()]);
        }
    }
    
    /**
     * Handle cancel action.
     */
    public function cancel(): mixed
    {
        return redirect()->route('route.name');
    }
    
    // ============================================
    // EVENT LISTENERS
    // ============================================
    
    /**
     * Listen for custom events.
     */
    #[On('event-name')]
    public function handleEvent($data): void
    {
        // Handle event
    }
    
    // ============================================
    // RENDER
    // ============================================
    
    /**
     * Render the component.
     */
    public function render()
    {
        return view('livewire.feature.component-name');
    }
}
```

### Blade View Structure

**Location:** `resources/views/livewire/feature/component-name.blade.php`

```blade
<div>
    <x-galaxy-layout>
        <x-slot name="title">Page Title</x-slot>
        
        <div class="container mx-auto px-6 py-8">
            <div class="relative p-8 glass-card max-w-4xl mx-auto">
                <div class="top-accent-center"></div>
                
                <!-- Page Header -->
                <div class="mb-8">
                    <h1 class="text-3xl font-bold text-white mb-2">Component Title</h1>
                    <p class="text-gray-400">Component description</p>
                </div>
                
                <!-- Component Content -->
                <form wire:submit="save" class="space-y-6">
                    <!-- Form fields here -->
                    
                    <!-- Form Actions -->
                    <div class="flex gap-3 pt-4">
                        <button 
                            type="submit"
                            class="flex-1 px-6 py-3 bg-gradient-to-r from-pink-500 to-purple-500 
                                   rounded-xl font-semibold text-white
                                   hover:scale-105 transition-all shadow-lg hover:shadow-pink-500/50
                                   disabled:opacity-50 disabled:cursor-not-allowed disabled:hover:scale-100"
                            wire:loading.attr="disabled"
                        >
                            <span wire:loading.remove>Save</span>
                            <span wire:loading>Saving...</span>
                        </button>
                        
                        <button 
                            type="button"
                            wire:click="cancel"
                            class="px-6 py-3 bg-slate-800/50 border border-white/10 
                                   rounded-xl text-white
                                   hover:border-cyan-500/50 transition"
                        >
                            Cancel
                        </button>
                    </div>
                </form>
                
            </div>
        </div>
    </x-galaxy-layout>
</div>
```

## Component Types

### Form Component (Create/Edit)

**Use for:** Creating or editing a resource

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
        app(PostService::class)->create($validated);
        
        session()->flash('success', 'Post created!');
        return redirect()->route('feed.nearby');
    }
    
    public function render()
    {
        return view('livewire.posts.create-post');
    }
}
```

### List Component (Index)

**Use for:** Displaying a list of resources

```php
class PostList extends Component
{
    public $search = '';
    public $filter = 'all';
    
    public function render()
    {
        $posts = Post::query()
            ->when($this->search, fn($q) => $q->where('title', 'like', "%{$this->search}%"))
            ->when($this->filter !== 'all', fn($q) => $q->where('status', $this->filter))
            ->latest()
            ->paginate(20);
        
        return view('livewire.posts.post-list', [
            'posts' => $posts,
        ]);
    }
}
```

### Detail Component (Show)

**Use for:** Displaying a single resource with actions

```php
class ShowPost extends Component
{
    public Post $post;
    
    public function mount(Post $post): void
    {
        $this->post = $post;
    }
    
    public function delete(): mixed
    {
        $this->authorize('delete', $this->post);
        
        app(PostService::class)->delete($this->post);
        
        session()->flash('success', 'Post deleted!');
        return redirect()->route('feed.nearby');
    }
    
    public function render()
    {
        return view('livewire.posts.show-post');
    }
}
```

### Modal Component

**Use for:** Modal dialogs

```php
class ConfirmDeleteModal extends Component
{
    public $show = false;
    public $itemId;
    
    #[On('open-delete-modal')]
    public function open($itemId): void
    {
        $this->itemId = $itemId;
        $this->show = true;
    }
    
    public function confirm(): void
    {
        app(PostService::class)->delete($this->itemId);
        
        $this->show = false;
        $this->dispatch('item-deleted');
        
        session()->flash('success', 'Item deleted!');
    }
    
    public function cancel(): void
    {
        $this->show = false;
    }
    
    public function render()
    {
        return view('livewire.modals.confirm-delete-modal');
    }
}
```

## Property Binding

### Wire:model (Two-way Binding)

**Use for:** Form inputs that need real-time updates

```blade
<input type="text" wire:model="title">
```

### Wire:model.live (Real-time Updates)

**Use for:** Search, filters, live validation

```blade
<input type="text" wire:model.live="search">
```

### Wire:model.blur (Update on Blur)

**Use for:** Performance optimization on large forms

```blade
<input type="text" wire:model.blur="description">
```

## Event Handling

### Dispatching Events

**From component:**
```php
$this->dispatch('event-name', data: $value);
```

**From view:**
```blade
<button wire:click="$dispatch('event-name', { data: 'value' })">
    Trigger Event
</button>
```

### Listening to Events

**In component:**
```php
#[On('event-name')]
public function handleEvent($data): void
{
    // Handle event
}
```

**In view:**
```blade
<div x-on:event-name.window="console.log($event.detail)">
    <!-- Content -->
</div>
```

## Loading States

### Button Loading State

```blade
<button wire:loading.attr="disabled">
    <span wire:loading.remove>Save</span>
    <span wire:loading>Saving...</span>
</button>
```

### Full Page Loading

```blade
<div wire:loading class="fixed inset-0 bg-black/50 backdrop-blur-sm z-50 
                         flex items-center justify-center">
    <div class="animate-spin rounded-full h-12 w-12 border-4 
                border-cyan-500 border-t-transparent"></div>
</div>
```

### Target Specific Actions

```blade
<button wire:click="save">Save</button>

<div wire:loading wire:target="save">
    Saving...
</div>
```

## Authorization

**Always check authorization in components:**

```php
public function mount(Post $post): void
{
    $this->authorize('view', $post);
    $this->post = $post;
}

public function delete(): mixed
{
    $this->authorize('delete', $this->post);
    app(PostService::class)->delete($this->post);
}
```

## Completion Checklist

When creating a Livewire component, verify:

- [ ] Component follows standard structure (properties, mount, rules, methods, render)
- [ ] Public properties are documented
- [ ] Validation rules are defined
- [ ] Business logic is in service classes (not component)
- [ ] Authorization checks are in place
- [ ] Loading states are implemented
- [ ] Error handling is implemented
- [ ] View uses galaxy layout and glass cards
- [ ] Form has proper validation error display
- [ ] Component dispatches/listens to events correctly

