# FunLynk UI Design Standards

## Overview
This document defines the comprehensive design system for FunLynk, based on a galaxy-themed aesthetic with aurora borealis effects and glass morphism. All UI components should follow these standards for visual consistency.

## Color Palette

### Background Colors
- **Primary Background Gradient**: `linear-gradient(to bottom right, #0a0a1a, #0f1729, #0a0a1a)`
- **Dark Base**: `#0a0a1a` (very dark blue-black)
- **Mid Tone**: `#0f1729` (dark blue)
- **Card Background**: `rgba(15, 23, 42, 0.5)` (semi-transparent slate)
- **Slate 800**: `#1e293b` (for inner elements)
- **Slate 900**: `#0f172a` (for darker elements)

### Aurora Borealis Colors
- **Green Aurora**: `rgba(16, 185, 129, 0.18)` - Positioned at 30% 20%
- **Blue Aurora**: `rgba(59, 130, 246, 0.2)` - Positioned at 70% 40%
- **Purple Aurora**: `rgba(139, 92, 246, 0.15)` - Positioned at 50% 60%

### Accent Colors
- **Yellow**: `#fbbf24` (#fbbf24) - Used for "Fun" in logo
- **Cyan**: `#06b6d4` (#06b6d4) - Used for "Lynk" in logo
- **Pink**: `#ec4899` (#ec4899) - Primary accent
- **Purple**: `#8b5cf6` (#8b5cf6) - Secondary accent
- **Green**: `#10b981` (#10b981) - Success/online status
- **Orange**: `#f97316` (#f97316) - Hot badges

### Text Colors
- **Primary Text**: `#ffffff` (white)
- **Secondary Text**: `#d1d5db` (#d1d5db, gray-300)
- **Muted Text**: `#9ca3af` (#9ca3af, gray-400)
- **Subtle Text**: `#6b7280` (#6b7280, gray-600)

### Border Colors
- **Glass Card Border**: `rgba(59, 130, 246, 0.3)` (blue with 30% opacity)
- **Subtle Border**: `rgba(255, 255, 255, 0.1)` (white with 10% opacity)
- **Hover Border**: `rgba(139, 92, 246, 0.5)` (purple with 50% opacity)

## Background Effects

### Galaxy Background
```css
body {
    background: linear-gradient(to bottom right, #0a0a1a, #0f1729, #0a0a1a);
    min-height: 100vh;
    color: white;
    position: relative;
    overflow-x: hidden;
}
```

### Aurora Borealis Animation
```css
@keyframes aurora {
    0%, 100% {
        opacity: 0.3;
        transform: translateY(0) scale(1);
    }
    50% {
        opacity: 0.6;
        transform: translateY(-20px) scale(1.1);
    }
}

.aurora {
    position: absolute;
    width: 100%;
    height: 100%;
    pointer-events: none;
    opacity: 0.4;
}

.aurora-layer-1 {
    background: radial-gradient(ellipse at 30% 20%, rgba(16, 185, 129, 0.18) 0%, transparent 50%);
    animation: aurora 8s ease-in-out infinite;
}

.aurora-layer-2 {
    background: radial-gradient(ellipse at 70% 40%, rgba(59, 130, 246, 0.2) 0%, transparent 50%);
    animation: aurora 10s ease-in-out infinite 2s;
}

.aurora-layer-3 {
    background: radial-gradient(ellipse at 50% 60%, rgba(139, 92, 246, 0.15) 0%, transparent 50%);
    animation: aurora 12s ease-in-out infinite 4s;
}
```

### Animated Stars
```css
@keyframes twinkle {
    0%, 100% { opacity: 0.2; }
    50% { opacity: 0.8; }
}

.star {
    position: absolute;
    width: 2px;
    height: 2px;
    background: white;
    border-radius: 50%;
    animation: twinkle 3s infinite;
}
```

**Implementation**: Generate 150 stars with random positions and animation delays:
```blade
@for($i = 0; $i < 150; $i++)
<div class="star" style="left: {{ rand(0, 100) }}%; top: {{ rand(0, 100) }}%; animation-delay: {{ rand(0, 3000) }}ms; opacity: {{ rand(20, 80) / 100 }};"></div>
@endfor
```

### Tech Grid Overlay
```css
.tech-grid {
    position: absolute;
    inset: 0;
    opacity: 0.05;
    pointer-events: none;
    background-image:
        linear-gradient(rgba(99, 102, 241, 0.08) 1px, transparent 1px),
        linear-gradient(90deg, rgba(99, 102, 241, 0.08) 1px, transparent 1px);
    background-size: 50px 50px;
}
```

## Glass Morphism

### Glass Card
```css
.glass-card {
    background: rgba(15, 23, 42, 0.5);
    backdrop-filter: blur(20px);
    border: 1px solid rgba(59, 130, 246, 0.3);
    border-radius: 1.5rem;
}
```

**Usage**: Apply to all major content containers (headers, panels, forms)

### Gradient Border
```css
.gradient-border {
    position: relative;
    padding: 0.125rem;
    background: linear-gradient(to right, #ec4899, #8b5cf6, #06b6d4);
    border-radius: 1rem;
    animation: pulse 2s infinite;
}
```

**Usage**: Logo containers, featured elements

### Top Accent Lines
```css
.top-accent {
    position: absolute;
    top: 0;
    left: 0;
    width: 8rem;
    height: 0.25rem;
    background: linear-gradient(to right, #ec4899, #8b5cf6, transparent);
    border-radius: 9999px;
}

.top-accent-center {
    position: absolute;
    top: 0;
    left: 50%;
    transform: translateX(-50%);
    width: 8rem;
    height: 0.25rem;
    background: linear-gradient(to right, transparent, #ec4899, transparent);
}
```

## Typography

### Font Family
- **Primary**: `Inter, ui-sans-serif, system-ui, sans-serif`
- **Monospace**: Use for technical labels (e.g., "SOCIAL ACTIVITY NETWORK")

### Font Sizes
- **Hero/H1**: `text-2xl` (1.5rem / 24px)
- **H2**: `text-2xl` (1.5rem / 24px)
- **H3**: `text-xl` (1.25rem / 20px)
- **Body**: `text-base` (1rem / 16px)
- **Small**: `text-sm` (0.875rem / 14px)
- **Extra Small**: `text-xs` (0.75rem / 12px)

### Font Weights
- **Bold**: `font-bold` (700) - Headings, emphasis
- **Semibold**: `font-semibold` (600) - Subheadings
- **Medium**: `font-medium` (500) - Navigation
- **Normal**: `font-normal` (400) - Body text

### Gradient Text
```css
.gradient-text {
    background: linear-gradient(to right, #fbbf24, #06b6d4);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
}
```

**Usage**: Logo text, featured headings

### Brand Text Colors
- **"Fun"**: `text-yellow-400` (#fbbf24)
- **"Lynk"**: `text-cyan-400` (#06b6d4)
- **Tagline**: `text-gray-400 font-mono text-xs` - "SOCIAL ACTIVITY NETWORK"

## Interactive Elements

### Hover Effects

#### Activity Cards
```css
.activity-card {
    transition: all 0.3s ease;
}

.activity-card:hover {
    transform: scale(1.1);
    box-shadow: 0 0 30px rgba(139, 92, 246, 0.5);
}
```

#### Avatar Hover
```css
.crew-avatar {
    transition: all 0.3s ease;
}

.crew-avatar:hover {
    transform: scale(1.1);
    box-shadow: 0 0 30px rgba(59, 130, 246, 0.5);
}
```

#### Navigation Links
```html
<button class="text-gray-300 hover:text-white transition relative group">
    Link Text
    <div class="absolute -bottom-2 left-0 right-0 h-0.5 bg-gradient-to-r from-pink-500 to-purple-500 scale-x-0 group-hover:scale-x-100 transition-transform"></div>
</button>
```

### Transitions
- **Standard**: `transition-all duration-300` or `transition`
- **Transform**: `transition-transform duration-300`
- **Colors**: `transition-colors duration-200`

### Animations
- **Pulse**: `animate-pulse` - Status indicators, hot badges
- **Rotate on Hover**: `group-hover:rotate-90 transition-transform duration-300` - FAB icons

## Component Patterns

### Buttons

#### Primary Button
```html
<button class="px-6 py-3 bg-gradient-to-r from-pink-500 to-purple-500 rounded-xl font-semibold hover:scale-105 transition-all">
    Button Text
</button>
```

#### Secondary Button
```html
<button class="px-6 py-3 bg-slate-800/50 border border-white/10 rounded-xl hover:border-cyan-500/50 transition">
    Button Text
</button>
```

#### Icon Button
```html
<button class="p-2 hover:bg-white/10 rounded-xl transition">
    <svg class="w-5 h-5">...</svg>
</button>
```

### Cards

#### Glass Card Container
```html
<div class="relative p-8 glass-card">
    <div class="top-accent-center"></div>
    <!-- Content -->
</div>
```

#### Event Card
```html
<div class="relative p-4 rounded-2xl bg-slate-800/50 border border-white/10 hover:border-purple-500/50 transition-all group cursor-pointer">
    <!-- Content -->
</div>
```

### Forms

#### Text Input
```html
<input type="text" 
       placeholder="Placeholder..." 
       class="w-full px-4 py-3 bg-slate-800/50 border border-white/10 rounded-2xl focus:border-cyan-500/50 focus:outline-none transition text-white"/>
```

#### Search Input with Icon
```html
<div class="relative">
    <svg class="absolute left-4 top-1/2 transform -translate-y-1/2 w-5 h-5 text-gray-400">...</svg>
    <input type="text" 
           placeholder="Search..." 
           class="w-full pl-12 pr-4 py-3 bg-slate-800/50 border border-white/10 rounded-2xl focus:border-cyan-500/50 focus:outline-none transition text-white"/>
</div>
```

### Avatars

#### User Avatar with Gradient Border
```html
<div class="w-12 h-12 rounded-full bg-gradient-to-br from-purple-500 to-pink-500 p-0.5">
    <div class="w-full h-full bg-slate-800 rounded-full flex items-center justify-center text-lg font-bold">
        U
    </div>
</div>
```

#### Avatar with Online Status
```html
<div class="relative">
    <div class="w-20 h-20 rounded-full border-3 border-cyan-500 p-0.5">
        <div class="w-full h-full bg-slate-800 rounded-full flex items-center justify-center">
            A
        </div>
    </div>
    <div class="absolute top-0 right-0 w-4 h-4 bg-green-500 rounded-full border-2 border-slate-900 animate-pulse"></div>
</div>
```

### Badges

#### Hot Badge
```html
<div class="px-3 py-1 bg-gradient-to-r from-orange-500 to-pink-500 rounded-full text-xs font-bold flex items-center gap-1 animate-pulse">
    <svg class="w-3 h-3">...</svg>
    HOT
</div>
```

#### Category Badge
```html
<span class="px-2 py-1 bg-purple-500/20 text-purple-300 rounded-lg text-xs">
    Category
</span>
```

#### Status Badge
```html
<span class="w-2 h-2 bg-pink-500 rounded-full animate-pulse"></span>
```

### Navigation

#### Header Navigation
```html
<nav class="flex gap-8">
    <button class="text-gray-300 hover:text-white transition relative group">
        Nav Item
        <div class="absolute -bottom-2 left-0 right-0 h-0.5 bg-gradient-to-r from-pink-500 to-purple-500 scale-x-0 group-hover:scale-x-100 transition-transform"></div>
    </button>
</nav>
```

### Floating Action Button
```html
<button class="fixed bottom-8 right-8 w-16 h-16 bg-gradient-to-br from-pink-500 via-purple-500 to-cyan-500 rounded-full shadow-2xl hover:scale-110 transition-all duration-300 flex items-center justify-center group">
    <svg class="w-8 h-8 text-white group-hover:rotate-90 transition-transform duration-300">...</svg>
</button>
```

## Spacing & Layout

### Container
- **Max Width**: `max-w-7xl mx-auto`
- **Padding**: `p-6` (1.5rem / 24px)

### Grid Systems
- **Two Column**: `grid grid-cols-1 lg:grid-cols-2 gap-6`
- **Three Column**: `grid grid-cols-3 gap-4`

### Spacing Scale
- **xs**: `gap-1` (0.25rem / 4px)
- **sm**: `gap-2` (0.5rem / 8px)
- **md**: `gap-4` (1rem / 16px)
- **lg**: `gap-6` (1.5rem / 24px)
- **xl**: `gap-8` (2rem / 32px)

### Padding
- **Card**: `p-8` (2rem / 32px)
- **Button**: `px-6 py-3`
- **Input**: `px-4 py-3`

### Border Radius
- **Small**: `rounded-lg` (0.5rem / 8px)
- **Medium**: `rounded-xl` (0.75rem / 12px)
- **Large**: `rounded-2xl` (1rem / 16px)
- **Full**: `rounded-full` (9999px)

### Responsive Breakpoints
- **sm**: 640px
- **md**: 768px
- **lg**: 1024px
- **xl**: 1280px
- **2xl**: 1536px

## Logo Usage

### Header Logo (Icon Only)
- **File**: `images/fl-logo-icon-only.png`
- **Container**: Gradient border with pulse animation
- **Size**: `h-12 w-auto` inside `w-16 h-16` container

### Full Logo
- **File**: `images/fl-logo-main.png`
- **Usage**: Landing page hero, footer, large displays

### Text Logo
- **"Fun"**: `text-yellow-400`
- **"Lynk"**: `text-cyan-400`
- **Tagline**: `text-xs text-gray-400 font-mono` - "SOCIAL ACTIVITY NETWORK"

## Implementation Checklist

When creating a new page, ensure:
- [ ] Galaxy background gradient applied to body
- [ ] Three aurora layers added
- [ ] 150 animated stars generated
- [ ] Tech grid overlay included
- [ ] All content in glass cards with proper borders
- [ ] Top accent lines on major sections
- [ ] Proper hover states on interactive elements
- [ ] Gradient borders on featured elements
- [ ] Consistent spacing and typography
- [ ] Logo properly displayed
- [ ] All colors from the palette
- [ ] Responsive grid layouts

---

## Agent Implementation Guide

### How to Ensure Styling Consistency

**CRITICAL**: Every Livewire component, Blade view, and page MUST follow these standards. No exceptions.

### Step 1: Use the Base Layout Template

All user-facing pages should extend one of these layouts:

**For Auth Pages** (login, register):
```blade
<x-layouts.auth>
    <!-- Your content here -->
</x-layouts.auth>
```

**For App Pages** (dashboard, profiles, activities):
Create `resources/views/layouts/app.blade.php` following the same pattern as `auth.blade.php`:
- Galaxy background gradient
- Aurora layers (3)
- Animated stars (150)
- Tech grid overlay
- Filament form styling (if using Filament forms)

### Step 2: Always Use Glass Cards

**Every major content section** must be wrapped in a glass card:

```blade
<div class="relative p-8 glass-card">
    <div class="top-accent-center"></div>
    <!-- Your content -->
</div>
```

**For smaller cards** (activity cards, user cards):
```blade
<div class="relative p-4 rounded-2xl bg-slate-800/50 border border-white/10 hover:border-purple-500/50 transition-all">
    <!-- Your content -->
</div>
```

### Step 3: Button Styling

**Primary Actions** (submit, create, confirm):
```blade
<button class="px-6 py-3 bg-gradient-to-r from-pink-500 to-purple-500 rounded-xl font-semibold hover:scale-105 transition-all">
    Action Text
</button>
```

**Secondary Actions** (cancel, back, view):
```blade
<button class="px-6 py-3 bg-slate-800/50 border border-white/10 rounded-xl hover:border-cyan-500/50 transition">
    Action Text
</button>
```

### Step 4: Form Inputs

**Text Inputs**:
```blade
<input type="text"
       class="w-full px-4 py-3 bg-slate-800/50 border border-white/10 rounded-2xl focus:border-cyan-500/50 focus:outline-none transition text-white"
       placeholder="Enter text..."/>
```

**Textareas**:
```blade
<textarea
    class="w-full px-4 py-3 bg-slate-800/50 border border-white/10 rounded-2xl focus:border-cyan-500/50 focus:outline-none transition text-white"
    rows="4"
    placeholder="Enter description..."></textarea>
```

**Select Dropdowns**:
```blade
<select class="w-full px-4 py-3 bg-slate-800/50 border border-white/10 rounded-2xl focus:border-cyan-500/50 focus:outline-none transition text-white">
    <option>Option 1</option>
</select>
```

### Step 5: Typography

**Headings**:
```blade
<h1 class="text-5xl font-bold mb-6">Main Heading</h1>
<h2 class="text-4xl font-bold mb-4">Section Heading</h2>
<h3 class="text-2xl font-bold mb-3">Subsection Heading</h3>
```

**Body Text**:
```blade
<p class="text-lg text-gray-300 mb-4">Regular paragraph text</p>
<p class="text-sm text-gray-400">Smaller secondary text</p>
```

**Brand Text** (for logo/branding):
```blade
<span class="text-yellow-400">Fun</span><span class="text-cyan-400">Lynk</span>
```

### Step 6: Livewire Component Styling

When creating Livewire components, **always include the base layout**:

```blade
<div>
    <!-- Aurora layers (if not in layout) -->
    <div class="aurora aurora-layer-1"></div>
    <div class="aurora aurora-layer-2"></div>
    <div class="aurora aurora-layer-3"></div>

    <!-- Content in glass card -->
    <div class="container mx-auto px-6 py-8">
        <div class="relative p-8 glass-card max-w-4xl mx-auto">
            <div class="top-accent-center"></div>

            <!-- Your component content -->

        </div>
    </div>
</div>
```

### Step 7: DaisyUI Integration

FunLynk uses **DaisyUI with dark theme**. When using DaisyUI components:

```blade
<!-- Card -->
<div class="card bg-base-100/80 backdrop-blur-lg border border-white/10">
    <div class="card-body">
        <!-- Content -->
    </div>
</div>

<!-- Button -->
<button class="btn btn-primary">
    <!-- Will be styled with gradient via custom CSS -->
</button>

<!-- Badge -->
<div class="badge badge-primary">New</div>
```

**IMPORTANT**: DaisyUI classes are **enhanced** with custom CSS for galaxy theme. Don't override the base styles.

### Step 8: Reference Implementation

**Before creating any new UI**, review these files:
1. `resources/views/welcome.blade.php` - Landing page with all effects
2. `resources/views/layouts/auth.blade.php` - Base layout with aurora/stars
3. `resources/views/livewire/auth/login.blade.php` - Form styling example

**Copy the exact CSS** from these files for:
- Aurora animations
- Glass card styling
- Form input styling
- Button gradients

### Step 9: Testing Your Styling

After creating a component, verify:
1. ✅ Background has galaxy gradient
2. ✅ Aurora layers are visible and animating
3. ✅ Stars are twinkling
4. ✅ Tech grid is subtle in background
5. ✅ Glass cards have blur effect and borders
6. ✅ Buttons have gradient or glass styling
7. ✅ Forms have proper focus states (cyan glow)
8. ✅ Hover effects work smoothly
9. ✅ Text is readable (white/gray on dark)
10. ✅ Responsive on mobile (test at 375px width)

### Step 10: Common Mistakes to Avoid

❌ **DON'T**:
- Use plain white backgrounds
- Use default Tailwind buttons without gradients
- Forget the aurora layers
- Use black text on dark backgrounds
- Skip the glass-card wrapper
- Use sharp corners (always use rounded-xl or rounded-2xl)
- Forget hover states
- Use colors outside the palette

✅ **DO**:
- Always use galaxy gradient background
- Always wrap content in glass cards
- Always use gradient buttons for primary actions
- Always include aurora layers and stars
- Always use colors from the defined palette
- Always add smooth transitions
- Always test hover states
- Always make it responsive

---

## Quick Copy-Paste Snippets

### Full Page Template
```blade
<!DOCTYPE html>
<html lang="en" data-theme="dark">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Page Title</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        /* Copy all CSS from layouts/auth.blade.php lines 15-240 */
    </style>
</head>
<body class="font-sans antialiased">
    <!-- Aurora Layers -->
    <div class="aurora aurora-layer-1"></div>
    <div class="aurora aurora-layer-2"></div>
    <div class="aurora aurora-layer-3"></div>

    <!-- Stars -->
    <div class="absolute inset-0 overflow-hidden pointer-events-none">
        @for($i = 0; $i < 150; $i++)
        <div class="star" style="left: {{ rand(0, 100) }}%; top: {{ rand(0, 100) }}%; animation-delay: {{ rand(0, 3000) }}ms; opacity: {{ rand(20, 80) / 100 }};"></div>
        @endfor
    </div>

    <!-- Tech Grid -->
    <div class="tech-grid"></div>

    <!-- Content -->
    <div class="relative z-10">
        <!-- Your content here -->
    </div>
</body>
</html>
```

### Livewire Component Template
```blade
<div class="min-h-screen">
    <div class="container mx-auto px-6 py-8">
        <div class="relative p-8 glass-card max-w-4xl mx-auto">
            <div class="top-accent-center"></div>

            <h2 class="text-3xl font-bold mb-6">Component Title</h2>

            <!-- Your content -->

        </div>
    </div>
</div>
```

---

**Last Updated**: 2025-11-20
**Status**: Production Standard - All new UI must follow these guidelines

