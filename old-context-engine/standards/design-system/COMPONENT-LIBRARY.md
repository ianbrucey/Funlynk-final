# Funlynk Component Library

## Priority Components for Week 3 Implementation

Based on the upcoming E02.F01 User Registration & Onboarding tasks, here are the critical components needed:

## 1. Form Components

### Input Field
**Usage**: Registration forms, profile editing
**States**: Default, Focus, Error, Success, Disabled
**Variants**: Text, Email, Password, Phone

```typescript
interface InputFieldProps {
  label: string;
  placeholder?: string;
  value: string;
  onChangeText: (text: string) => void;
  error?: string;
  success?: boolean;
  disabled?: boolean;
  type?: 'text' | 'email' | 'password' | 'phone';
  leftIcon?: string;
  rightIcon?: string;
}
```

### Button Components
**Primary Button**: Brand gradient, main actions
**Secondary Button**: Outlined, secondary actions  
**Text Button**: Minimal, tertiary actions
**Icon Button**: Social login, utility actions

```typescript
interface ButtonProps {
  title: string;
  onPress: () => void;
  variant: 'primary' | 'secondary' | 'text' | 'icon';
  size?: 'sm' | 'md' | 'lg';
  disabled?: boolean;
  loading?: boolean;
  leftIcon?: string;
  rightIcon?: string;
  fullWidth?: boolean;
}
```

### Form Validation
**Error Messages**: Consistent styling and positioning
**Success States**: Confirmation feedback
**Field Groups**: Related input grouping

## 2. Navigation Components

### Header/Navigation Bar
**Usage**: Screen headers, back navigation
**Variants**: Default, Search, Profile, Settings

```typescript
interface HeaderProps {
  title?: string;
  leftAction?: {
    icon: string;
    onPress: () => void;
  };
  rightAction?: {
    icon: string;
    onPress: () => void;
  };
  showSearch?: boolean;
  gradient?: boolean;
}
```

### Tab Navigation
**Usage**: Main app navigation
**Style**: Bottom tabs with brand gradient indicators

### Progress Indicator
**Usage**: Onboarding flow, multi-step forms
**Style**: Gradient progress bar with step indicators

## 3. Content Display

### User Avatar
**Usage**: Profile pictures, user lists
**Variants**: Sizes (xs, sm, md, lg, xl), Online status, Placeholder

```typescript
interface AvatarProps {
  imageUrl?: string;
  size: 'xs' | 'sm' | 'md' | 'lg' | 'xl';
  online?: boolean;
  initials?: string;
  onPress?: () => void;
}
```

### Card Components
**Activity Card**: Main content display
**User Card**: Profile previews, search results
**Info Card**: Onboarding, help content

```typescript
interface CardProps {
  children: React.ReactNode;
  variant?: 'default' | 'elevated' | 'outlined';
  padding?: 'sm' | 'md' | 'lg';
  onPress?: () => void;
  gradient?: boolean;
}
```

### Tag/Badge Components
**Usage**: Interests, categories, status indicators
**Variants**: Default, Selected, Removable

## 4. Feedback Components

### Loading States
**Spinner**: Brand gradient animated spinner
**Skeleton**: Content placeholders
**Progress**: Linear and circular progress

### Alert/Toast
**Success**: Green with checkmark
**Error**: Red with warning icon
**Info**: Blue with info icon
**Warning**: Orange with caution icon

### Modal/Bottom Sheet
**Usage**: Confirmations, selections, detailed views
**Variants**: Full screen, Half screen, Alert style

## 5. Social Components

### Follow Button
**Usage**: User profiles, search results
**States**: Follow, Following, Requested
**Style**: Brand gradient when active

```typescript
interface FollowButtonProps {
  userId: string;
  isFollowing: boolean;
  isRequested?: boolean;
  onPress: (userId: string) => void;
  size?: 'sm' | 'md' | 'lg';
}
```

### Social Stats
**Usage**: Profile screens, activity cards
**Display**: Followers, Following, Activities count

### User List Item
**Usage**: Followers list, search results
**Components**: Avatar, Name, Bio, Follow button

## Implementation Priority

### Week 1-2 (Foundation)
1. **Design Tokens**: Colors, typography, spacing
2. **Base Components**: Button, Input, Card
3. **Layout Components**: Container, Stack, Grid

### Week 3 (Registration Ready)
4. **Form Components**: Complete form system
5. **Navigation**: Header, progress indicator
6. **Feedback**: Loading, error states

### Week 4+ (Social Features)
7. **Social Components**: Follow button, user cards
8. **Advanced**: Modals, complex interactions
9. **Refinement**: Animations, micro-interactions

## React Native Implementation Structure

```
src/components/
├── base/
│   ├── Button/
│   │   ├── Button.tsx
│   │   ├── Button.styles.ts
│   │   ├── Button.test.tsx
│   │   └── index.ts
│   ├── Input/
│   └── Card/
├── form/
│   ├── FormField/
│   ├── FormGroup/
│   └── FormValidation/
├── navigation/
│   ├── Header/
│   ├── TabBar/
│   └── ProgressIndicator/
├── social/
│   ├── Avatar/
│   ├── FollowButton/
│   └── UserCard/
└── feedback/
    ├── Loading/
    ├── Toast/
    └── Modal/
```

## Design Token Integration

### Colors (React Native)
```typescript
export const colors = {
  primary: {
    gradient: ['#00FFFF', '#8B5CF6'],
    cyan: '#06B6D4',
    purple: '#A855F7',
  },
  semantic: {
    success: '#10B981',
    warning: '#F59E0B',
    error: '#EF4444',
  },
  neutral: {
    50: '#F8FAFC',
    100: '#F1F5F9',
    // ... rest of grays
  }
};
```

### Typography
```typescript
export const typography = {
  fonts: {
    primary: 'Inter',
    display: 'Poppins',
  },
  sizes: {
    xs: 12,
    sm: 14,
    base: 16,
    lg: 18,
    xl: 20,
    // ... display sizes
  },
  weights: {
    normal: '400',
    medium: '500',
    semibold: '600',
    bold: '700',
  }
};
```

### Spacing
```typescript
export const spacing = {
  0: 0,
  1: 4,
  2: 8,
  3: 12,
  4: 16,
  5: 20,
  6: 24,
  8: 32,
  // ... larger sizes
};
```

## Accessibility Implementation

### Focus Management
```typescript
// Ensure proper focus order and visibility
const Button = ({ onPress, children, ...props }) => {
  return (
    <TouchableOpacity
      onPress={onPress}
      accessible={true}
      accessibilityRole="button"
      accessibilityLabel={props.accessibilityLabel}
      {...props}
    >
      {children}
    </TouchableOpacity>
  );
};
```

### Screen Reader Support
- All interactive elements have proper labels
- Content hierarchy with proper heading structure
- Form fields have associated labels and error messages

### Color Contrast
- All text meets WCAG AA standards
- Interactive elements have sufficient contrast
- Focus indicators are clearly visible

## Testing Strategy

### Visual Testing
- Storybook for component isolation
- Chromatic for visual regression testing
- Multiple device sizes and orientations

### Accessibility Testing
- React Native Accessibility Inspector
- Screen reader testing (TalkBack, VoiceOver)
- Color contrast validation

### Performance Testing
- Component render performance
- Memory usage monitoring
- Animation frame rate testing

## Documentation

### Component Documentation
Each component includes:
- Purpose and usage guidelines
- Props interface with descriptions
- Code examples and variations
- Accessibility considerations
- Design specifications

### Design System Documentation
- Figma component library
- Design token documentation
- Usage guidelines and patterns
- Brand guidelines and voice

---

**Next Actions**:
1. Set up Storybook for component development
2. Create base design tokens in TypeScript
3. Implement priority components for registration flow
4. Set up accessibility testing tools
