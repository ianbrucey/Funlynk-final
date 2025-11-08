# CSS Standards - DaisyUI Implementation Guide

## Overview
This document provides standards for implementing UI components using DaisyUI, our chosen CSS framework. DaisyUI is built on top of Tailwind CSS and provides semantic, component-based classes for consistent styling across the application.

## Core Principles
- **Use DaisyUI components first**: Always check if DaisyUI has a component before creating custom CSS
- **Semantic naming**: DaisyUI uses semantic class names (e.g., `btn-primary`, `input-bordered`)
- **Consistency**: Use DaisyUI's design tokens for colors, spacing, and typography
- **Accessibility**: DaisyUI components are built with accessibility in mind

## Buttons

### Button Types
```html
<!-- Primary Button -->
<button class="btn btn-primary">Primary Action</button>

<!-- Secondary Button -->
<button class="btn btn-secondary">Secondary Action</button>

<!-- Accent Button -->
<button class="btn btn-accent">Accent Action</button>

<!-- Ghost Button (transparent) -->
<button class="btn btn-ghost">Ghost Action</button>

<!-- Link Button -->
<button class="btn btn-link">Link Action</button>
```

### Button Sizes
```html
<button class="btn btn-xs">Extra Small</button>
<button class="btn btn-sm">Small</button>
<button class="btn btn-md">Medium (default)</button>
<button class="btn btn-lg">Large</button>
```

### Button States
```html
<!-- Disabled -->
<button class="btn btn-primary" disabled>Disabled</button>

<!-- Loading -->
<button class="btn btn-primary">
  <span class="loading loading-spinner"></span>
  Loading
</button>

<!-- Active -->
<button class="btn btn-primary btn-active">Active</button>
```

### Button Variants
```html
<!-- Outline -->
<button class="btn btn-outline btn-primary">Outline Primary</button>

<!-- Wide -->
<button class="btn btn-wide">Wide Button</button>

<!-- Block (full width) -->
<button class="btn btn-block">Block Button</button>

<!-- Square -->
<button class="btn btn-square">
  <svg>...</svg>
</button>

<!-- Circle -->
<button class="btn btn-circle">
  <svg>...</svg>
</button>
```

## Forms and Inputs

### Text Inputs
```html
<!-- Basic Input -->
<input type="text" placeholder="Type here" class="input input-bordered w-full" />

<!-- Input with Label -->
<label class="form-control w-full">
  <div class="label">
    <span class="label-text">What is your name?</span>
  </div>
  <input type="text" placeholder="Type here" class="input input-bordered w-full" />
  <div class="label">
    <span class="label-text-alt">Alt label</span>
  </div>
</label>

<!-- Input Sizes -->
<input type="text" class="input input-bordered input-xs" />
<input type="text" class="input input-bordered input-sm" />
<input type="text" class="input input-bordered input-md" />
<input type="text" class="input input-bordered input-lg" />
```

### Input States
```html
<!-- Primary -->
<input type="text" class="input input-bordered input-primary" />

<!-- Secondary -->
<input type="text" class="input input-bordered input-secondary" />

<!-- Accent -->
<input type="text" class="input input-bordered input-accent" />

<!-- Success -->
<input type="text" class="input input-bordered input-success" />

<!-- Warning -->
<input type="text" class="input input-bordered input-warning" />

<!-- Error -->
<input type="text" class="input input-bordered input-error" />

<!-- Disabled -->
<input type="text" class="input input-bordered" disabled />
```

### Textarea
```html
<textarea class="textarea textarea-bordered" placeholder="Bio"></textarea>

<!-- With sizing -->
<textarea class="textarea textarea-bordered textarea-lg" placeholder="Bio"></textarea>
```

### Select
```html
<select class="select select-bordered w-full">
  <option disabled selected>Pick your favorite language</option>
  <option>Java</option>
  <option>Go</option>
  <option>C</option>
  <option>C#</option>
  <option>C++</option>
  <option>Rust</option>
  <option>JavaScript</option>
  <option>Python</option>
</select>
```

### Checkbox
```html
<input type="checkbox" class="checkbox" />
<input type="checkbox" class="checkbox checkbox-primary" />
<input type="checkbox" class="checkbox checkbox-secondary" />
<input type="checkbox" class="checkbox checkbox-accent" />

<!-- Sizes -->
<input type="checkbox" class="checkbox checkbox-xs" />
<input type="checkbox" class="checkbox checkbox-sm" />
<input type="checkbox" class="checkbox checkbox-md" />
<input type="checkbox" class="checkbox checkbox-lg" />
```

### Radio
```html
<input type="radio" name="radio-1" class="radio" checked />
<input type="radio" name="radio-1" class="radio" />

<!-- With colors -->
<input type="radio" name="radio-2" class="radio radio-primary" checked />
<input type="radio" name="radio-2" class="radio radio-secondary" />
```

### Toggle
```html
<input type="checkbox" class="toggle" checked />
<input type="checkbox" class="toggle toggle-primary" checked />
<input type="checkbox" class="toggle toggle-secondary" checked />
<input type="checkbox" class="toggle toggle-accent" checked />
```

## Modals

### Basic Modal
```html
<!-- Button to open modal -->
<button class="btn" onclick="my_modal_1.showModal()">Open Modal</button>

<!-- Modal -->
<dialog id="my_modal_1" class="modal">
  <div class="modal-box">
    <h3 class="font-bold text-lg">Hello!</h3>
    <p class="py-4">Press ESC key or click the button below to close</p>
    <div class="modal-action">
      <form method="dialog">
        <button class="btn">Close</button>
      </form>
    </div>
  </div>
</dialog>
```

### Modal with Backdrop
```html
<dialog id="my_modal_2" class="modal">
  <div class="modal-box">
    <h3 class="font-bold text-lg">Hello!</h3>
    <p class="py-4">Press ESC key or click outside to close</p>
  </div>
  <form method="dialog" class="modal-backdrop">
    <button>close</button>
  </form>
</dialog>
```

### Modal Sizes
```html
<!-- Small -->
<div class="modal-box w-11/12 max-w-sm">...</div>

<!-- Medium (default) -->
<div class="modal-box">...</div>

<!-- Large -->
<div class="modal-box w-11/12 max-w-5xl">...</div>
```

## Cards

### Basic Card
```html
<div class="card w-96 bg-base-100 shadow-xl">
  <figure><img src="https://..." alt="..." /></figure>
  <div class="card-body">
    <h2 class="card-title">Card title!</h2>
    <p>If a dog chews shoes whose shoes does he choose?</p>
    <div class="card-actions justify-end">
      <button class="btn btn-primary">Buy Now</button>
    </div>
  </div>
</div>
```

### Card Variants
```html
<!-- Compact -->
<div class="card card-compact w-96 bg-base-100 shadow-xl">...</div>

<!-- Normal (default) -->
<div class="card card-normal w-96 bg-base-100 shadow-xl">...</div>

<!-- Side -->
<div class="card card-side bg-base-100 shadow-xl">
  <figure><img src="..." alt="..." /></figure>
  <div class="card-body">...</div>
</div>
```

## Alerts

```html
<!-- Info -->
<div role="alert" class="alert alert-info">
  <svg>...</svg>
  <span>New software update available.</span>
</div>

<!-- Success -->
<div role="alert" class="alert alert-success">
  <svg>...</svg>
  <span>Your purchase has been confirmed!</span>
</div>

<!-- Warning -->
<div role="alert" class="alert alert-warning">
  <svg>...</svg>
  <span>Warning: Invalid email address!</span>
</div>

<!-- Error -->
<div role="alert" class="alert alert-error">
  <svg>...</svg>
  <span>Error! Task failed successfully.</span>
</div>
```

## Badges

```html
<div class="badge">neutral</div>
<div class="badge badge-primary">primary</div>
<div class="badge badge-secondary">secondary</div>
<div class="badge badge-accent">accent</div>
<div class="badge badge-ghost">ghost</div>

<!-- Sizes -->
<div class="badge badge-lg">Large</div>
<div class="badge badge-md">Medium</div>
<div class="badge badge-sm">Small</div>
<div class="badge badge-xs">Tiny</div>

<!-- Outline -->
<div class="badge badge-outline">outline</div>
<div class="badge badge-primary badge-outline">primary</div>
```

## Loading Indicators

```html
<!-- Spinner -->
<span class="loading loading-spinner loading-xs"></span>
<span class="loading loading-spinner loading-sm"></span>
<span class="loading loading-spinner loading-md"></span>
<span class="loading loading-spinner loading-lg"></span>

<!-- Dots -->
<span class="loading loading-dots loading-xs"></span>
<span class="loading loading-dots loading-sm"></span>
<span class="loading loading-dots loading-md"></span>
<span class="loading loading-dots loading-lg"></span>

<!-- Ring -->
<span class="loading loading-ring loading-xs"></span>
<span class="loading loading-ring loading-sm"></span>
<span class="loading loading-ring loading-md"></span>
<span class="loading loading-ring loading-lg"></span>
```

## Best Practices

1. **Always use DaisyUI classes**: Don't create custom CSS when DaisyUI provides a component
2. **Combine with Tailwind utilities**: DaisyUI works seamlessly with Tailwind utility classes
3. **Responsive design**: Use Tailwind's responsive prefixes (sm:, md:, lg:, xl:)
4. **Accessibility**: Always include proper ARIA labels and semantic HTML
5. **Consistency**: Use the same component variants throughout the application
6. **Theme support**: DaisyUI supports multiple themes - ensure components work across themes

## Resources

- [DaisyUI Documentation](https://daisyui.com/)
- [DaisyUI Components](https://daisyui.com/components/)
- [Tailwind CSS Documentation](https://tailwindcss.com/docs)

