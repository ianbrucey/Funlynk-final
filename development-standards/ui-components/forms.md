# Form Patterns

**Standard:** All forms in FunLynk follow these exact patterns.

## Standard Form Structure

### Complete Form Template

```blade
<form wire:submit="save" class="space-y-6">
    <!-- Text Input -->
    <div>
        <label for="title" class="block text-sm font-medium text-gray-300 mb-2">
            Title <span class="text-pink-500">*</span>
        </label>
        <input 
            type="text" 
            id="title"
            wire:model="title"
            class="w-full px-4 py-3 bg-slate-800/50 border border-white/10 
                   rounded-xl text-white placeholder-gray-400
                   focus:border-cyan-500 focus:ring-2 focus:ring-cyan-500/20 
                   transition
                   @error('title') border-red-500 focus:border-red-500 focus:ring-red-500/20 @enderror"
            placeholder="Enter title..."
            required
        >
        @error('title')
            <p class="mt-2 text-sm text-red-400">{{ $message }}</p>
        @enderror
    </div>

    <!-- Textarea -->
    <div>
        <label for="description" class="block text-sm font-medium text-gray-300 mb-2">
            Description
        </label>
        <textarea 
            id="description"
            wire:model="description"
            rows="4"
            class="w-full px-4 py-3 bg-slate-800/50 border border-white/10 
                   rounded-xl text-white placeholder-gray-400
                   focus:border-cyan-500 focus:ring-2 focus:ring-cyan-500/20 
                   transition resize-none
                   @error('description') border-red-500 focus:border-red-500 focus:ring-red-500/20 @enderror"
            placeholder="Enter description..."
        ></textarea>
        @error('description')
            <p class="mt-2 text-sm text-red-400">{{ $message }}</p>
        @enderror
    </div>

    <!-- Select Dropdown -->
    <div>
        <label for="category" class="block text-sm font-medium text-gray-300 mb-2">
            Category <span class="text-pink-500">*</span>
        </label>
        <select 
            id="category"
            wire:model="category"
            class="w-full px-4 py-3 bg-slate-800/50 border border-white/10 
                   rounded-xl text-white
                   focus:border-cyan-500 focus:ring-2 focus:ring-cyan-500/20 
                   transition
                   @error('category') border-red-500 focus:border-red-500 focus:ring-red-500/20 @enderror"
            required
        >
            <option value="">Select a category...</option>
            <option value="sports">Sports</option>
            <option value="food">Food & Drink</option>
            <option value="arts">Arts & Culture</option>
        </select>
        @error('category')
            <p class="mt-2 text-sm text-red-400">{{ $message }}</p>
        @enderror
    </div>

    <!-- Checkbox -->
    <div class="flex items-start gap-3">
        <input 
            type="checkbox" 
            id="is_public"
            wire:model="isPublic"
            class="mt-1 w-4 h-4 bg-slate-800/50 border border-white/10 
                   rounded text-cyan-500 
                   focus:ring-2 focus:ring-cyan-500/20"
        >
        <label for="is_public" class="text-sm text-gray-300">
            Make this public (visible to all users)
        </label>
    </div>

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
```

## Field Types

### Text Input (Standard)

```blade
<div>
    <label for="field_name" class="block text-sm font-medium text-gray-300 mb-2">
        Field Label <span class="text-pink-500">*</span>
    </label>
    <input 
        type="text" 
        id="field_name"
        wire:model="fieldName"
        class="w-full px-4 py-3 bg-slate-800/50 border border-white/10 
               rounded-xl text-white placeholder-gray-400
               focus:border-cyan-500 focus:ring-2 focus:ring-cyan-500/20 
               transition
               @error('fieldName') border-red-500 focus:border-red-500 focus:ring-red-500/20 @enderror"
        placeholder="Enter value..."
        required
    >
    @error('fieldName')
        <p class="mt-2 text-sm text-red-400">{{ $message }}</p>
    @enderror
</div>
```

### Email Input

```blade
<input 
    type="email" 
    id="email"
    wire:model="email"
    class="w-full px-4 py-3 bg-slate-800/50 border border-white/10 
           rounded-xl text-white placeholder-gray-400
           focus:border-cyan-500 focus:ring-2 focus:ring-cyan-500/20 
           transition
           @error('email') border-red-500 focus:border-red-500 focus:ring-red-500/20 @enderror"
    placeholder="your@email.com"
    required
>
```

### Password Input

```blade
<input 
    type="password" 
    id="password"
    wire:model="password"
    class="w-full px-4 py-3 bg-slate-800/50 border border-white/10 
           rounded-xl text-white placeholder-gray-400
           focus:border-cyan-500 focus:ring-2 focus:ring-cyan-500/20 
           transition
           @error('password') border-red-500 focus:border-red-500 focus:ring-red-500/20 @enderror"
    placeholder="Enter password..."
    required
>
```

### Number Input

```blade
<input 
    type="number" 
    id="quantity"
    wire:model="quantity"
    min="1"
    max="100"
    class="w-full px-4 py-3 bg-slate-800/50 border border-white/10 
           rounded-xl text-white placeholder-gray-400
           focus:border-cyan-500 focus:ring-2 focus:ring-cyan-500/20 
           transition
           @error('quantity') border-red-500 focus:border-red-500 focus:ring-red-500/20 @enderror"
    placeholder="0"
>
```

### Date Input

```blade
<input 
    type="date" 
    id="event_date"
    wire:model="eventDate"
    class="w-full px-4 py-3 bg-slate-800/50 border border-white/10 
           rounded-xl text-white
           focus:border-cyan-500 focus:ring-2 focus:ring-cyan-500/20 
           transition
           @error('eventDate') border-red-500 focus:border-red-500 focus:ring-red-500/20 @enderror"
>
```

### Time Input

```blade
<input 
    type="time" 
    id="event_time"
    wire:model="eventTime"
    class="w-full px-4 py-3 bg-slate-800/50 border border-white/10 
           rounded-xl text-white
           focus:border-cyan-500 focus:ring-2 focus:ring-cyan-500/20 
           transition
           @error('eventTime') border-red-500 focus:border-red-500 focus:ring-red-500/20 @enderror"
>
```

## Validation States

### Error State (Automatic)

Use `@error` directive - styling is automatic via `@error('field')` class:

```blade
<input 
    class="... @error('fieldName') border-red-500 focus:border-red-500 focus:ring-red-500/20 @enderror"
>
@error('fieldName')
    <p class="mt-2 text-sm text-red-400">{{ $message }}</p>
@enderror
```

### Success State (Optional)

Only use for explicit success feedback (e.g., username availability):

```blade
@if($usernameAvailable)
    <p class="mt-2 text-sm text-green-400 flex items-center gap-2">
        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
        </svg>
        Username is available!
    </p>
@endif
```

## Form Actions

### Standard Actions (Save + Cancel)

```blade
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
```

### Destructive Action (Delete)

```blade
<button 
    type="button"
    wire:click="delete"
    wire:confirm="Are you sure you want to delete this?"
    class="px-6 py-3 bg-red-500/20 border border-red-500/50 
           rounded-xl text-red-400
           hover:bg-red-500/30 transition"
>
    Delete
</button>
```

## Required Field Indicator

Always use red asterisk for required fields:

```blade
<label for="field" class="block text-sm font-medium text-gray-300 mb-2">
    Field Label <span class="text-pink-500">*</span>
</label>
```

## Completion Checklist

When implementing a form, verify:

- [ ] All inputs use the standard classes
- [ ] Required fields have red asterisk in label
- [ ] All fields have `@error` directive with error message
- [ ] Form has `wire:submit` on `<form>` tag
- [ ] Submit button has loading state (`wire:loading`)
- [ ] Submit button is disabled during submission
- [ ] Cancel button exists (if applicable)
- [ ] Form is wrapped in `space-y-6` for consistent spacing
- [ ] Placeholder text is helpful and concise
- [ ] Field IDs match label `for` attributes

