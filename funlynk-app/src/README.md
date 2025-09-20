# Funlynk Mobile App Source Structure

This directory contains the source code for the Funlynk mobile application, organized according to the comprehensive architecture defined in the context-engine documentation.

## Directory Structure

```
src/
├── components/          # Reusable UI components
│   ├── common/         # Common components (buttons, inputs, etc.)
│   ├── activity/       # Activity-related components
│   ├── user/          # User profile components
│   ├── social/        # Social interaction components
│   └── payment/       # Payment and monetization components
├── screens/            # Screen components (pages)
│   ├── auth/          # Authentication screens
│   ├── activity/      # Activity management screens
│   ├── discovery/     # Discovery and search screens
│   ├── profile/       # User profile screens
│   ├── social/        # Social interaction screens
│   └── payment/       # Payment and subscription screens
├── services/           # API services and external integrations
│   ├── api/           # API client and endpoints
│   ├── auth/          # Authentication services
│   ├── location/      # Location services
│   ├── payment/       # Payment processing services
│   └── analytics/     # Analytics and tracking services
├── utils/              # Utility functions and helpers
│   ├── validation/    # Form validation utilities
│   ├── formatting/    # Data formatting utilities
│   ├── constants/     # App constants and configuration
│   └── helpers/       # General helper functions
├── types/              # TypeScript type definitions
│   ├── api/           # API response types
│   ├── user/          # User-related types
│   ├── activity/      # Activity-related types
│   └── common/        # Common/shared types
├── hooks/              # Custom React hooks
│   ├── useAuth.ts     # Authentication hook
│   ├── useLocation.ts # Location services hook
│   ├── useApi.ts      # API interaction hook
│   └── useAnalytics.ts # Analytics tracking hook
├── contexts/           # React Context providers
│   ├── AuthContext.tsx # Authentication context
│   ├── UserContext.tsx # User data context
│   └── ThemeContext.tsx # Theme and styling context
└── navigation/         # Navigation configuration
    ├── AppNavigator.tsx # Main app navigation
    ├── AuthNavigator.tsx # Authentication flow navigation
    └── TabNavigator.tsx # Bottom tab navigation
```

## Architecture Alignment

This structure aligns with the 7 epics defined in the Funlynk architecture:

### E01 Core Infrastructure
- `services/api/` - API Gateway & Communication
- `services/auth/` - Authentication & Security
- `utils/constants/` - Database & Storage configuration

### E02 User & Profile Management
- `screens/auth/` - User Registration & Authentication
- `screens/profile/` - Profile Management & Customization
- `components/user/` - User-related components
- `types/user/` - User type definitions

### E03 Activity Management
- `screens/activity/` - Activity CRUD Operations
- `components/activity/` - Activity components
- `types/activity/` - Activity type definitions
- `services/api/activities/` - Activity API services

### E04 Discovery Engine
- `screens/discovery/` - Search and discovery interfaces
- `services/api/search/` - Search Service APIs
- `hooks/useSearch.ts` - Search and recommendation hooks

### E05 Social Interaction
- `screens/social/` - Social interaction screens
- `components/social/` - Social components (comments, sharing, etc.)
- `services/api/social/` - Social API services

### E06 Payments & Monetization
- `screens/payment/` - Payment and subscription screens
- `components/payment/` - Payment components
- `services/payment/` - Payment processing services

### E07 Administration & Analytics
- `services/analytics/` - Analytics and tracking services
- `hooks/useAnalytics.ts` - Analytics hooks
- `utils/monitoring/` - Performance monitoring utilities

## Development Guidelines

### Component Organization
- Keep components small and focused on a single responsibility
- Use TypeScript for all components with proper type definitions
- Follow the established naming conventions
- Include proper documentation and examples

### State Management
- Use React Context for global state (auth, user, theme)
- Use local state for component-specific data
- Consider Redux Toolkit for complex state management needs

### API Integration
- Centralize API calls in the `services/` directory
- Use custom hooks for API interactions
- Implement proper error handling and loading states
- Cache API responses where appropriate

### Type Safety
- Define comprehensive TypeScript types in the `types/` directory
- Use strict TypeScript configuration
- Avoid `any` types - use proper type definitions
- Export types from a central index file

### Testing Strategy
- Write unit tests for utility functions
- Write integration tests for API services
- Write component tests for UI components
- Use React Native Testing Library for component testing

## Getting Started

1. **Install dependencies**: `npm install`
2. **Start development server**: `npm start`
3. **Run on iOS**: `npm run ios`
4. **Run on Android**: `npm run android`

## Next Steps

1. Implement authentication flow following E02 specifications
2. Set up API services following E01 specifications
3. Create core UI components following design system
4. Implement activity management following E03 specifications
5. Add discovery features following E04 specifications
6. Integrate social features following E05 specifications
7. Add payment processing following E06 specifications
8. Implement analytics following E07 specifications

For detailed implementation guidance, refer to the comprehensive task breakdowns in the `../context-engine/tasks/` directory.
