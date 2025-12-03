# UI Patterns & Design System

**Generated:** 2025-12-02

## Design System Overview

FunLynk uses a **galaxy/space theme** with glass morphism effects, inspired by modern AI interfaces (Claude, Gemini).

### Core Visual Identity

**Theme:** Dark cosmic/galaxy aesthetic  
**Primary Colors:**
- Pink: `#ec4899` (pink-500)
- Purple: `#a855f7` (purple-500)
- Cyan: `#06b6d4` (cyan-500)
- Slate: `#1e293b` (slate-800)

**Effects:**
- Glass morphism (frosted glass cards)
- Gradient backgrounds (pink → purple)
- Aurora/nebula effects
- Twinkling stars animation
- Glow effects on interactive elements

## Layout Components

### 1. Galaxy Layout (`resources/views/components/galaxy-layout.blade.php`)

Base layout component used across all pages.

**Usage:**
```blade
<x-galaxy-layout>
    <x-slot name="title">Page Title</x-slot>
    
    <!-- Page content -->
    
</x-galaxy-layout>
```

**Features:**
- Animated galaxy background
- Aurora gradient layers
- Twinkling stars
- Responsive navigation
- Mobile-optimized

### 2. Glass Card Pattern

Standard container for content sections.

**HTML Structure:**
```blade
<div class="relative p-8 glass-card max-w-4xl mx-auto">
    <div class="top-accent-center"></div>
    <!-- Card content -->
</div>
```

**CSS Classes:**
```css
.glass-card {
    background: rgba(15, 23, 42, 0.6);
    backdrop-filter: blur(20px);
    border: 1px solid rgba(255, 255, 255, 0.1);
    border-radius: 1.5rem;
    box-shadow: 0 8px 32px rgba(0, 0, 0, 0.3);
}

.top-accent-center {
    position: absolute;
    top: 0;
    left: 50%;
    transform: translateX(-50%);
    width: 200px;
    height: 4px;
    background: linear-gradient(90deg, 
        transparent, 
        rgba(6, 182, 212, 0.8), 
        transparent
    );
    border-radius: 0 0 4px 4px;
}
```

## Component Patterns

### 1. Buttons

#### Primary Button (Gradient)
```blade
<button class="px-6 py-3 bg-gradient-to-r from-pink-500 to-purple-500 
               rounded-xl font-semibold hover:scale-105 transition-all 
               shadow-lg hover:shadow-pink-500/50">
    Submit
</button>
```

#### Secondary Button (Glass)
```blade
<button class="px-6 py-3 bg-slate-800/50 border border-white/10 
               rounded-xl hover:border-cyan-500/50 transition">
    Cancel
</button>
```

#### Icon Button
```blade
<button class="p-3 bg-slate-800/50 rounded-lg hover:bg-slate-700/50 
               transition">
    <svg class="w-5 h-5">...</svg>
</button>
```

### 2. Forms

#### Input Fields
```blade
<input type="text" 
       class="w-full px-4 py-3 bg-slate-800/50 border border-white/10 
              rounded-xl text-white placeholder-gray-400
              focus:border-cyan-500 focus:ring-2 focus:ring-cyan-500/20 
              transition"
       placeholder="Enter text...">
```

#### Textarea
```blade
<textarea class="w-full px-4 py-3 bg-slate-800/50 border border-white/10 
                 rounded-xl text-white placeholder-gray-400
                 focus:border-cyan-500 focus:ring-2 focus:ring-cyan-500/20 
                 transition resize-none"
          rows="4"
          placeholder="Enter description..."></textarea>
```

#### Select Dropdown
```blade
<select class="w-full px-4 py-3 bg-slate-800/50 border border-white/10 
               rounded-xl text-white
               focus:border-cyan-500 focus:ring-2 focus:ring-cyan-500/20 
               transition">
    <option>Option 1</option>
    <option>Option 2</option>
</select>
```

### 3. Cards

#### Content Card
```blade
<div class="p-6 bg-slate-800/30 border border-white/10 rounded-xl 
            hover:border-cyan-500/30 transition">
    <h3 class="text-xl font-bold text-white mb-2">Card Title</h3>
    <p class="text-gray-300">Card content...</p>
</div>
```

#### Post/Activity Card
```blade
<div class="p-6 bg-slate-800/30 border border-white/10 rounded-xl 
            hover:border-pink-500/30 transition cursor-pointer">
    <div class="flex items-start gap-4">
        <img src="..." class="w-12 h-12 rounded-full">
        <div class="flex-1">
            <h4 class="font-semibold text-white">Title</h4>
            <p class="text-sm text-gray-400">Description</p>
        </div>
    </div>
</div>
```

### 4. Navigation

#### Bottom Navigation (Mobile)
```blade
<nav class="fixed bottom-0 left-0 right-0 bg-slate-900/95 backdrop-blur-xl 
            border-t border-white/10 z-50">
    <div class="flex justify-around items-center h-16">
        <a href="..." class="flex flex-col items-center gap-1 text-gray-400 
                            hover:text-cyan-500 transition">
            <svg class="w-6 h-6">...</svg>
            <span class="text-xs">Home</span>
        </a>
        <!-- More nav items -->
    </div>
</nav>
```

#### Top Navigation (Desktop)
```blade
<nav class="fixed top-0 left-0 right-0 bg-slate-900/95 backdrop-blur-xl 
            border-b border-white/10 z-50">
    <div class="container mx-auto px-6 h-16 flex items-center justify-between">
        <a href="/" class="text-2xl font-bold bg-gradient-to-r 
                          from-pink-500 to-purple-500 bg-clip-text 
                          text-transparent">
            FunLynk
        </a>
        <!-- Nav items -->
    </div>
</nav>
```

### 5. Modals

```blade
<div class="fixed inset-0 bg-black/50 backdrop-blur-sm z-50 
            flex items-center justify-center p-4">
    <div class="relative p-8 glass-card max-w-lg w-full">
        <div class="top-accent-center"></div>
        
        <h2 class="text-2xl font-bold text-white mb-4">Modal Title</h2>
        
        <!-- Modal content -->
        
        <div class="flex gap-3 mt-6">
            <button class="flex-1 px-6 py-3 bg-gradient-to-r 
                          from-pink-500 to-purple-500 rounded-xl">
                Confirm
            </button>
            <button class="flex-1 px-6 py-3 bg-slate-800/50 
                          border border-white/10 rounded-xl">
                Cancel
            </button>
        </div>
    </div>
</div>
```

## Livewire Component Structure

### Standard Livewire Component Pattern

**PHP Class (`app/Livewire/Feature/ComponentName.php`):**
```php
<?php

namespace App\Livewire\Feature;

use Livewire\Component;

class ComponentName extends Component
{
    public $property;
    
    public function mount()
    {
        // Initialize component
    }
    
    public function action()
    {
        // Handle user action
    }
    
    public function render()
    {
        return view('livewire.feature.component-name');
    }
}
```

**Blade View (`resources/views/livewire/feature/component-name.blade.php`):**
```blade
<div>
    <x-galaxy-layout>
        <x-slot name="title">Component Title</x-slot>
        
        <div class="container mx-auto px-6 py-8">
            <div class="relative p-8 glass-card max-w-4xl mx-auto">
                <div class="top-accent-center"></div>
                
                <!-- Component content -->
                
            </div>
        </div>
    </x-galaxy-layout>
</div>
```

## Typography

### Headings
```blade
<h1 class="text-4xl font-bold text-white mb-4">Heading 1</h1>
<h2 class="text-3xl font-bold text-white mb-3">Heading 2</h2>
<h3 class="text-2xl font-bold text-white mb-2">Heading 3</h3>
<h4 class="text-xl font-semibold text-white mb-2">Heading 4</h4>
```

### Body Text
```blade
<p class="text-gray-300 leading-relaxed">Body text</p>
<p class="text-sm text-gray-400">Small text</p>
<p class="text-xs text-gray-500">Extra small text</p>
```

### Links
```blade
<a href="..." class="text-cyan-400 hover:text-cyan-300 transition">Link</a>
```

## Responsive Design

### Breakpoints (Tailwind)
- `sm`: 640px
- `md`: 768px
- `lg`: 1024px
- `xl`: 1280px
- `2xl`: 1536px

### Mobile-First Approach
```blade
<!-- Mobile: full width, Desktop: max-width -->
<div class="w-full lg:max-w-4xl lg:mx-auto">
    <!-- Content -->
</div>

<!-- Mobile: stack, Desktop: grid -->
<div class="flex flex-col lg:grid lg:grid-cols-2 gap-4">
    <!-- Items -->
</div>
```

## Animation & Transitions

### Hover Effects
```blade
<div class="transition-all duration-300 hover:scale-105 hover:shadow-xl">
    <!-- Content -->
</div>
```

### Loading States
```blade
<div wire:loading class="fixed inset-0 bg-black/50 backdrop-blur-sm z-50 
                         flex items-center justify-center">
    <div class="animate-spin rounded-full h-12 w-12 border-4 
                border-cyan-500 border-t-transparent"></div>
</div>
```

