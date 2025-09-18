# Funlynk Design System & Style Guide

## Brand Identity

### Logo Analysis
The Funlynk logo establishes our core visual identity:
- **Gradient**: Cyan (#00FFFF) to Purple (#8B5CF6)
- **Typography**: Bold, modern sans-serif with rounded edges
- **Style**: Tech-forward, energetic, social-first
- **Personality**: Fun, inclusive, dynamic, trustworthy

## Color System

### Primary Brand Colors

#### Brand Gradient
```css
/* Primary Brand Gradient */
--gradient-primary: linear-gradient(135deg, #00FFFF 0%, #8B5CF6 100%);
--gradient-primary-vertical: linear-gradient(180deg, #00FFFF 0%, #8B5CF6 100%);

/* Individual Brand Colors */
--cyan-primary: #00FFFF;      /* Logo start */
--purple-primary: #8B5CF6;    /* Logo end */
--blue-mid: #3B82F6;          /* Gradient middle */
```

#### Extended Brand Palette
```css
/* Cyan Family */
--cyan-50: #ECFEFF;
--cyan-100: #CFFAFE;
--cyan-200: #A5F3FC;
--cyan-300: #67E8F9;
--cyan-400: #22D3EE;
--cyan-500: #06B6D4;  /* Primary cyan */
--cyan-600: #0891B2;
--cyan-700: #0E7490;
--cyan-800: #155E75;
--cyan-900: #164E63;

/* Purple Family */
--purple-50: #FAF5FF;
--purple-100: #F3E8FF;
--purple-200: #E9D5FF;
--purple-300: #D8B4FE;
--purple-400: #C084FC;
--purple-500: #A855F7;  /* Primary purple */
--purple-600: #9333EA;
--purple-700: #7C3AED;
--purple-800: #6B21A8;
--purple-900: #581C87;
```

### Semantic Colors

#### Success, Warning, Error
```css
/* Success - Green with cyan tint */
--success-50: #ECFDF5;
--success-500: #10B981;
--success-600: #059669;
--success-700: #047857;

/* Warning - Orange with energy */
--warning-50: #FFFBEB;
--warning-500: #F59E0B;
--warning-600: #D97706;
--warning-700: #B45309;

/* Error - Red with purple tint */
--error-50: #FEF2F2;
--error-500: #EF4444;
--error-600: #DC2626;
--error-700: #B91C1C;
```

#### Neutral Grays
```css
/* Neutral Grays - Cool tinted */
--gray-50: #F8FAFC;
--gray-100: #F1F5F9;
--gray-200: #E2E8F0;
--gray-300: #CBD5E1;
--gray-400: #94A3B8;
--gray-500: #64748B;
--gray-600: #475569;
--gray-700: #334155;
--gray-800: #1E293B;
--gray-900: #0F172A;
```

## Typography

### Font Stack
```css
/* Primary Font - Modern Sans-Serif */
--font-primary: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;

/* Display Font - For headers matching logo style */
--font-display: 'Poppins', 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;

/* Monospace - For code/technical content */
--font-mono: 'SF Mono', Monaco, 'Cascadia Code', 'Roboto Mono', monospace;
```

### Type Scale
```css
/* Display Sizes - For hero text, logo text */
--text-display-xl: 4.5rem;    /* 72px */
--text-display-lg: 3.75rem;   /* 60px */
--text-display-md: 3rem;      /* 48px */
--text-display-sm: 2.25rem;   /* 36px */

/* Heading Sizes */
--text-h1: 2rem;      /* 32px */
--text-h2: 1.75rem;   /* 28px */
--text-h3: 1.5rem;    /* 24px */
--text-h4: 1.25rem;   /* 20px */
--text-h5: 1.125rem;  /* 18px */
--text-h6: 1rem;      /* 16px */

/* Body Sizes */
--text-lg: 1.125rem;  /* 18px */
--text-base: 1rem;    /* 16px */
--text-sm: 0.875rem;  /* 14px */
--text-xs: 0.75rem;   /* 12px */
```

### Font Weights
```css
--font-thin: 100;
--font-light: 300;
--font-normal: 400;
--font-medium: 500;
--font-semibold: 600;
--font-bold: 700;
--font-extrabold: 800;
--font-black: 900;
```

## Spacing System

### Base Unit: 4px
```css
/* Spacing Scale - 4px base unit */
--space-0: 0;
--space-1: 0.25rem;   /* 4px */
--space-2: 0.5rem;    /* 8px */
--space-3: 0.75rem;   /* 12px */
--space-4: 1rem;      /* 16px */
--space-5: 1.25rem;   /* 20px */
--space-6: 1.5rem;    /* 24px */
--space-8: 2rem;      /* 32px */
--space-10: 2.5rem;   /* 40px */
--space-12: 3rem;     /* 48px */
--space-16: 4rem;     /* 64px */
--space-20: 5rem;     /* 80px */
--space-24: 6rem;     /* 96px */
```

### Component Spacing
```css
/* Common spacing patterns */
--spacing-component-xs: var(--space-2);  /* 8px - tight spacing */
--spacing-component-sm: var(--space-4);  /* 16px - normal spacing */
--spacing-component-md: var(--space-6);  /* 24px - comfortable spacing */
--spacing-component-lg: var(--space-8);  /* 32px - loose spacing */
--spacing-component-xl: var(--space-12); /* 48px - section spacing */
```

## Border Radius

### Rounded Corners
```css
/* Border Radius - Friendly, modern feel */
--radius-none: 0;
--radius-sm: 0.25rem;    /* 4px */
--radius-base: 0.5rem;   /* 8px */
--radius-md: 0.75rem;    /* 12px */
--radius-lg: 1rem;       /* 16px */
--radius-xl: 1.5rem;     /* 24px */
--radius-2xl: 2rem;      /* 32px */
--radius-full: 9999px;   /* Full circle */
```

## Shadows & Elevation

### Shadow System
```css
/* Shadows - Subtle, modern */
--shadow-xs: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
--shadow-sm: 0 1px 3px 0 rgba(0, 0, 0, 0.1), 0 1px 2px 0 rgba(0, 0, 0, 0.06);
--shadow-base: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
--shadow-md: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
--shadow-lg: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
--shadow-xl: 0 25px 50px -12px rgba(0, 0, 0, 0.25);

/* Colored shadows for brand elements */
--shadow-cyan: 0 10px 15px -3px rgba(6, 182, 212, 0.2), 0 4px 6px -2px rgba(6, 182, 212, 0.1);
--shadow-purple: 0 10px 15px -3px rgba(168, 85, 247, 0.2), 0 4px 6px -2px rgba(168, 85, 247, 0.1);
```

## Component Styles

### Buttons

#### Primary Button (Brand Gradient)
```css
.btn-primary {
  background: var(--gradient-primary);
  color: white;
  font-weight: var(--font-semibold);
  padding: var(--space-3) var(--space-6);
  border-radius: var(--radius-lg);
  border: none;
  box-shadow: var(--shadow-md);
  transition: all 0.2s ease;
}

.btn-primary:hover {
  transform: translateY(-1px);
  box-shadow: var(--shadow-lg);
}

.btn-primary:active {
  transform: translateY(0);
  box-shadow: var(--shadow-base);
}
```

#### Secondary Button
```css
.btn-secondary {
  background: white;
  color: var(--purple-600);
  border: 2px solid var(--purple-200);
  font-weight: var(--font-semibold);
  padding: var(--space-3) var(--space-6);
  border-radius: var(--radius-lg);
  transition: all 0.2s ease;
}

.btn-secondary:hover {
  border-color: var(--purple-400);
  background: var(--purple-50);
}
```

### Form Elements

#### Input Fields
```css
.input-field {
  background: white;
  border: 2px solid var(--gray-200);
  border-radius: var(--radius-md);
  padding: var(--space-3) var(--space-4);
  font-size: var(--text-base);
  transition: all 0.2s ease;
}

.input-field:focus {
  outline: none;
  border-color: var(--cyan-400);
  box-shadow: 0 0 0 3px rgba(6, 182, 212, 0.1);
}

.input-field.error {
  border-color: var(--error-500);
  box-shadow: 0 0 0 3px rgba(239, 68, 68, 0.1);
}
```

### Cards

#### Activity Card
```css
.card-activity {
  background: white;
  border-radius: var(--radius-xl);
  padding: var(--space-6);
  box-shadow: var(--shadow-base);
  border: 1px solid var(--gray-100);
  transition: all 0.2s ease;
}

.card-activity:hover {
  transform: translateY(-2px);
  box-shadow: var(--shadow-lg);
}
```

## Mobile-First Responsive Design

### Breakpoints
```css
/* Mobile First Approach */
--breakpoint-sm: 640px;   /* Small tablets */
--breakpoint-md: 768px;   /* Tablets */
--breakpoint-lg: 1024px;  /* Small laptops */
--breakpoint-xl: 1280px;  /* Desktops */
--breakpoint-2xl: 1536px; /* Large screens */
```

### Touch Targets
```css
/* Minimum touch target size */
--touch-target-min: 44px;

/* Common touch target sizes */
--touch-target-sm: 44px;
--touch-target-md: 48px;
--touch-target-lg: 56px;
```

## Accessibility

### Focus States
```css
/* Focus ring for keyboard navigation */
.focus-ring:focus {
  outline: none;
  box-shadow: 0 0 0 3px var(--cyan-400), 0 0 0 6px rgba(6, 182, 212, 0.2);
}
```

### Color Contrast
- All text meets WCAG AA standards (4.5:1 ratio minimum)
- Interactive elements meet WCAG AAA standards (7:1 ratio)
- Brand colors tested for accessibility compliance

## Animation & Transitions

### Timing Functions
```css
--ease-out: cubic-bezier(0.0, 0.0, 0.2, 1);
--ease-in: cubic-bezier(0.4, 0.0, 1, 1);
--ease-in-out: cubic-bezier(0.4, 0.0, 0.2, 1);
--ease-bounce: cubic-bezier(0.68, -0.55, 0.265, 1.55);
```

### Duration
```css
--duration-fast: 150ms;
--duration-normal: 200ms;
--duration-slow: 300ms;
--duration-slower: 500ms;
```

## Usage Guidelines

### Do's
- Use the brand gradient for primary actions and key brand moments
- Maintain consistent spacing using the 4px grid system
- Ensure all interactive elements meet minimum touch target sizes
- Use the established color palette for consistency

### Don'ts
- Don't use the brand gradient excessively - reserve for important elements
- Don't create custom colors outside the established palette
- Don't ignore accessibility requirements for contrast and focus states
- Don't break the spacing system with arbitrary values

---

**Next Steps**: 
1. Create component library in Figma/Storybook
2. Export design tokens for development
3. Create React Native component implementations
4. Test accessibility compliance
