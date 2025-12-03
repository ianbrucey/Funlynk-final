# Button Patterns

**Standard:** All buttons in FunLynk use these exact variants.

## Button Variants

### Primary Button (Gradient)

**Use for:** Main actions (Save, Submit, Create, Confirm)

```blade
<button 
    type="submit"
    class="px-6 py-3 bg-gradient-to-r from-pink-500 to-purple-500 
           rounded-xl font-semibold text-white
           hover:scale-105 transition-all shadow-lg hover:shadow-pink-500/50
           disabled:opacity-50 disabled:cursor-not-allowed disabled:hover:scale-100"
>
    Button Text
</button>
```

**With loading state:**
```blade
<button 
    type="submit"
    class="px-6 py-3 bg-gradient-to-r from-pink-500 to-purple-500 
           rounded-xl font-semibold text-white
           hover:scale-105 transition-all shadow-lg hover:shadow-pink-500/50
           disabled:opacity-50 disabled:cursor-not-allowed disabled:hover:scale-100"
    wire:loading.attr="disabled"
>
    <span wire:loading.remove>Save</span>
    <span wire:loading>Saving...</span>
</button>
```

### Secondary Button (Glass)

**Use for:** Secondary actions (Cancel, Back, Skip)

```blade
<button 
    type="button"
    class="px-6 py-3 bg-slate-800/50 border border-white/10 
           rounded-xl text-white
           hover:border-cyan-500/50 transition"
>
    Button Text
</button>
```

### Destructive Button (Red)

**Use for:** Destructive actions (Delete, Remove, Leave)

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

### Icon Button (Small)

**Use for:** Icon-only actions (Edit, Close, More)

```blade
<button 
    type="button"
    class="p-3 bg-slate-800/50 rounded-lg 
           hover:bg-slate-700/50 transition"
    aria-label="Edit"
>
    <svg class="w-5 h-5 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/>
    </svg>
</button>
```

### Link Button (Text Only)

**Use for:** Tertiary actions (Learn More, View Details)

```blade
<button 
    type="button"
    class="text-cyan-400 hover:text-cyan-300 transition font-medium"
>
    Button Text
</button>
```

## Button Groups

### Horizontal Group (Save + Cancel)

```blade
<div class="flex gap-3">
    <button 
        type="submit"
        class="flex-1 px-6 py-3 bg-gradient-to-r from-pink-500 to-purple-500 
               rounded-xl font-semibold text-white
               hover:scale-105 transition-all shadow-lg hover:shadow-pink-500/50"
    >
        Save
    </button>
    
    <button 
        type="button"
        class="px-6 py-3 bg-slate-800/50 border border-white/10 
               rounded-xl text-white
               hover:border-cyan-500/50 transition"
    >
        Cancel
    </button>
</div>
```

### Vertical Stack

```blade
<div class="flex flex-col gap-3">
    <button class="px-6 py-3 bg-gradient-to-r from-pink-500 to-purple-500 
                   rounded-xl font-semibold text-white
                   hover:scale-105 transition-all shadow-lg hover:shadow-pink-500/50">
        Primary Action
    </button>
    
    <button class="px-6 py-3 bg-slate-800/50 border border-white/10 
                   rounded-xl text-white
                   hover:border-cyan-500/50 transition">
        Secondary Action
    </button>
</div>
```

## Button States

### Loading State

**Always use for async actions:**

```blade
<button 
    wire:loading.attr="disabled"
    class="... disabled:opacity-50 disabled:cursor-not-allowed disabled:hover:scale-100"
>
    <span wire:loading.remove>Save</span>
    <span wire:loading>Saving...</span>
</button>
```

**With spinner:**
```blade
<button wire:loading.attr="disabled" class="...">
    <span wire:loading.remove>Save</span>
    <span wire:loading class="flex items-center gap-2">
        <svg class="animate-spin h-5 w-5" viewBox="0 0 24 24">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" fill="none"/>
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"/>
        </svg>
        Saving...
    </span>
</button>
```

### Disabled State

**Use sparingly - prefer hiding over disabling:**

```blade
<button 
    disabled
    class="px-6 py-3 bg-slate-800/30 border border-white/5 
           rounded-xl text-gray-500 cursor-not-allowed"
>
    Disabled Button
</button>
```

## Button Sizes

### Large (Default)

```blade
<button class="px-6 py-3 ...">Button Text</button>
```

### Medium

```blade
<button class="px-4 py-2 text-sm ...">Button Text</button>
```

### Small

```blade
<button class="px-3 py-1.5 text-xs ...">Button Text</button>
```

## Special Patterns

### Confirmation Button

**Always use `wire:confirm` for destructive actions:**

```blade
<button 
    wire:click="delete"
    wire:confirm="Are you sure you want to delete this? This action cannot be undone."
    class="px-6 py-3 bg-red-500/20 border border-red-500/50 
           rounded-xl text-red-400
           hover:bg-red-500/30 transition"
>
    Delete
</button>
```

### Button with Icon

```blade
<button class="px-6 py-3 bg-gradient-to-r from-pink-500 to-purple-500 
               rounded-xl font-semibold text-white
               hover:scale-105 transition-all shadow-lg hover:shadow-pink-500/50
               flex items-center gap-2">
    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
    </svg>
    Create New
</button>
```

### Full Width Button

```blade
<button class="w-full px-6 py-3 bg-gradient-to-r from-pink-500 to-purple-500 
               rounded-xl font-semibold text-white
               hover:scale-105 transition-all shadow-lg hover:shadow-pink-500/50">
    Button Text
</button>
```

## Decision Tree

**Which button variant should I use?**

```
Is this the primary action on the page?
├─ YES → Primary Button (Gradient)
└─ NO
   ├─ Is this a destructive action?
   │  └─ YES → Destructive Button (Red) + wire:confirm
   └─ NO
      ├─ Is this an icon-only action?
      │  └─ YES → Icon Button
      └─ NO
         ├─ Is this a secondary action?
         │  └─ YES → Secondary Button (Glass)
         └─ NO → Link Button (Text Only)
```

## Completion Checklist

When adding a button, verify:

- [ ] Button uses the correct variant for its purpose
- [ ] Destructive actions have `wire:confirm`
- [ ] Async actions have loading state (`wire:loading`)
- [ ] Button has appropriate `type` attribute (submit/button)
- [ ] Icon buttons have `aria-label` for accessibility
- [ ] Button text is clear and action-oriented (verb)
- [ ] Disabled state includes `disabled:` classes
- [ ] Button is keyboard accessible (can be focused/activated)

## Common Mistakes to Avoid

❌ **Don't** use multiple primary buttons on the same page  
✅ **Do** use one primary button and secondary buttons for other actions

❌ **Don't** use destructive styling for non-destructive actions  
✅ **Do** reserve red buttons for delete/remove/leave actions

❌ **Don't** forget loading states on async actions  
✅ **Do** always show loading feedback for actions that take time

❌ **Don't** use generic text like "Submit" or "OK"  
✅ **Do** use specific action verbs like "Create Post" or "Save Changes"

