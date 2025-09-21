# T01 Activity Creation UX Design & Workflow - Enhanced Planning

## Planning Overview

**Task ID**: E03.F01.T01  
**Planning Phase**: Detailed UX/Backend/Frontend/Third-party specifications  
**Time Allocation**: 2 hours  
**Deliverables**: Complete specifications for all technical domains

## UX Specification

### User Journey Map

**Entry Points**
1. **Main Tab**: "Create" tab in bottom navigation
2. **Profile**: "Host Activity" button on user profile
3. **Feed**: "Create Activity" floating action button
4. **Quick Actions**: Widget or shortcut from home screen

**Core User Flow**
```
Entry Point → Template Selection (Optional) → Step 1: Basic Info → 
Step 2: Details → Step 3: Images → Step 4: Tags → Step 5: Review → 
Success State
```

### Wireframe Specifications

**Step 1: Basic Information**
```
┌─────────────────────────────────┐
│ ← Back    Create Activity  Skip │
│ ●○○○○ Progress (Step 1 of 5)    │
├─────────────────────────────────┤
│                                 │
│ Activity Title *                │
│ ┌─────────────────────────────┐ │
│ │ Enter activity title...     │ │
│ └─────────────────────────────┘ │
│                                 │
│ Description *                   │
│ ┌─────────────────────────────┐ │
│ │ What's this activity about? │ │
│ │                             │ │
│ │                             │ │
│ └─────────────────────────────┘ │
│                                 │
│ Location *                      │
│ ┌─────────────────────────────┐ │
│ │ 📍 Search for location...   │ │
│ └─────────────────────────────┘ │
│                                 │
│ Date & Time *                   │
│ ┌──────────────┬──────────────┐ │
│ │ 📅 Sep 20    │ 🕐 6:00 PM  │ │
│ └──────────────┴──────────────┘ │
│                                 │
├─────────────────────────────────┤
│              Continue           │
└─────────────────────────────────┘
```

**Step 2: Activity Details**
```
┌─────────────────────────────────┐
│ ← Back    Activity Details      │
│ ○●○○○ Progress (Step 2 of 5)    │
├─────────────────────────────────┤
│                                 │
│ Capacity                        │
│ ┌─────────────────────────────┐ │
│ │ ∞ Unlimited  │ 🔢 Set Limit │ │
│ └─────────────────────────────┘ │
│                                 │
│ Price                           │
│ ┌─────────────────────────────┐ │
│ │ 🆓 Free     │ 💰 Paid      │ │
│ └─────────────────────────────┘ │
│                                 │
│ ▼ Advanced Options              │
│   Requirements                  │
│   Equipment Provided            │
│   Skill Level                   │
│                                 │
├─────────────────────────────────┤
│              Continue           │
└─────────────────────────────────┘
```

### Component Specifications

**ActivityCreationHeader**
```typescript
interface ActivityCreationHeaderProps {
  currentStep: number;
  totalSteps: number;
  onBack: () => void;
  onSkip?: () => void;
  title: string;
}
```

**ProgressIndicator**
```typescript
interface ProgressIndicatorProps {
  currentStep: number;
  totalSteps: number;
  completedSteps: number[];
  variant: 'dots' | 'bar' | 'steps';
}
```

**SmartLocationInput**
```typescript
interface SmartLocationInputProps {
  value: LocationData;
  onChange: (location: LocationData) => void;
  useCurrentLocation?: boolean;
  recentLocations?: LocationData[];
  placeholder: string;
}
```

### Interaction Patterns

**Navigation**
- **Forward**: Swipe left or tap "Continue"
- **Backward**: Swipe right or tap back arrow
- **Skip**: Available for optional steps
- **Save Draft**: Auto-save every 30 seconds, manual save option

**Validation**
- **Real-time**: Validate as user types (debounced)
- **Step Validation**: Validate before allowing step progression
- **Visual Feedback**: Green checkmarks for valid fields, red indicators for errors

**Error Handling**
- **Inline Errors**: Show errors directly below fields
- **Error Summary**: List all errors at step level if multiple issues
- **Recovery Actions**: Provide clear steps to fix errors

## Backend Specification

### API Requirements

**Draft Management**
```typescript
// Save draft activity
POST /api/v1/activities/drafts
{
  "title": "Pickup Basketball",
  "description": "Casual game...",
  "location_name": "Central Park",
  "start_time": "2025-09-20T18:00:00Z",
  "status": "draft"
}

// Get user's drafts
GET /api/v1/activities/drafts?user_id={userId}

// Update draft
PUT /api/v1/activities/drafts/{draftId}
```

**Validation Services**
```typescript
// Validate activity data
POST /api/v1/activities/validate
{
  "step": "basic_info",
  "data": {
    "title": "Pickup Basketball",
    "location_name": "Central Park"
  }
}

// Response
{
  "valid": true,
  "errors": [],
  "suggestions": {
    "location_coordinates": { "lat": 40.7829, "lng": -73.9654 }
  }
}
```

**Smart Defaults Service**
```typescript
// Get smart defaults for user
GET /api/v1/activities/defaults?user_id={userId}&template_id={templateId}

// Response
{
  "location": { "name": "User's frequent location" },
  "time": { "start_time": "Next weekend 6PM" },
  "tags": ["basketball", "sports"],
  "settings": { "capacity": 10, "skill_level": "beginner" }
}
```

### Data Models

**ActivityDraft**
```typescript
interface ActivityDraft {
  id: string;
  user_id: string;
  title?: string;
  description?: string;
  location_name?: string;
  location_coordinates?: { lat: number; lng: number };
  start_time?: string;
  end_time?: string;
  capacity?: number;
  price_cents?: number;
  requirements?: string;
  equipment_provided?: string;
  skill_level?: string;
  tags?: string[];
  images?: string[];
  step_completed: number;
  created_at: string;
  updated_at: string;
}
```

### Business Logic

**Validation Rules**
- Title: 5-100 characters, no profanity
- Description: 10-2000 characters, markdown supported
- Location: Valid address or coordinates
- Time: Must be in future, reasonable duration
- Capacity: 1-1000 or unlimited
- Price: 0-10000 cents, requires host verification for paid

**Smart Defaults Logic**
- Location: User's most frequent location or current location
- Time: Next available weekend evening slot
- Capacity: Based on activity type and location
- Tags: Suggested based on title/description analysis

## Frontend Specification

### Component Architecture

**Screen Components**
```typescript
// Main creation flow container
const ActivityCreationFlow = () => {
  const [currentStep, setCurrentStep] = useState(1);
  const [formData, setFormData] = useState<ActivityDraft>({});
  
  return (
    <NavigationContainer>
      <Stack.Navigator screenOptions={{ headerShown: false }}>
        <Stack.Screen name="BasicInfo" component={BasicInfoScreen} />
        <Stack.Screen name="Details" component={DetailsScreen} />
        <Stack.Screen name="Images" component={ImagesScreen} />
        <Stack.Screen name="Tags" component={TagsScreen} />
        <Stack.Screen name="Review" component={ReviewScreen} />
      </Stack.Navigator>
    </NavigationContainer>
  );
};
```

**Form Components**
```typescript
// Smart form input with validation
const SmartTextInput = ({ 
  label, 
  value, 
  onChange, 
  validation, 
  suggestions 
}: SmartTextInputProps) => {
  const [error, setError] = useState<string>();
  const [isValid, setIsValid] = useState(false);
  
  // Real-time validation logic
  // Suggestion display logic
  // Error handling
};
```

### State Management

**Creation State Store**
```typescript
interface ActivityCreationStore {
  // State
  currentStep: number;
  formData: ActivityDraft;
  validationErrors: Record<string, string>;
  isDraft: boolean;
  isSubmitting: boolean;
  
  // Actions
  updateFormData: (data: Partial<ActivityDraft>) => void;
  validateStep: (step: number) => Promise<boolean>;
  nextStep: () => void;
  previousStep: () => void;
  saveDraft: () => Promise<void>;
  submitActivity: () => Promise<void>;
  
  // Computed
  canProceed: boolean;
  completionPercentage: number;
}
```

### Navigation Logic

**Step Flow Management**
```typescript
const useStepNavigation = () => {
  const canProceedToStep = (targetStep: number) => {
    // Check if all previous steps are valid
    // Check if current step allows progression
    return previousStepsValid && currentStepComplete;
  };
  
  const navigateToStep = (step: number) => {
    if (canProceedToStep(step)) {
      setCurrentStep(step);
      navigation.navigate(getScreenForStep(step));
    }
  };
};
```

## Third-Party Services Specification

### Supabase Integration

**Database Operations**
```sql
-- Create draft activity
INSERT INTO activity_drafts (
  user_id, title, description, location_name, 
  start_time, step_completed, created_at
) VALUES ($1, $2, $3, $4, $5, $6, NOW());

-- Auto-save draft updates
UPDATE activity_drafts 
SET title = $2, description = $3, updated_at = NOW()
WHERE id = $1 AND user_id = $2;
```

**Real-time Subscriptions**
```typescript
// Listen for draft updates (for multi-device sync)
const subscription = supabase
  .channel('activity_drafts')
  .on('postgres_changes', {
    event: 'UPDATE',
    schema: 'public',
    table: 'activity_drafts',
    filter: `user_id=eq.${userId}`
  }, (payload) => {
    // Sync draft changes across devices
  })
  .subscribe();
```

### Location Services

**Geocoding Integration**
```typescript
// Use Supabase Edge Function for geocoding
const geocodeLocation = async (locationName: string) => {
  const { data } = await supabase.functions.invoke('geocode', {
    body: { location: locationName }
  });
  
  return {
    formatted_address: data.formatted_address,
    coordinates: data.coordinates,
    place_id: data.place_id
  };
};
```

### Analytics Integration

**User Behavior Tracking**
```typescript
// Track creation flow analytics
const trackCreationStep = (step: number, timeSpent: number) => {
  analytics.track('Activity Creation Step', {
    step_number: step,
    step_name: getStepName(step),
    time_spent_seconds: timeSpent,
    completion_rate: step / totalSteps
  });
};
```

---

**Planning Status**: ✅ Complete  
**Deliverables Created**:
- Complete UX wireframes and user flow
- Backend API specifications and data models
- Frontend component architecture and state management
- Third-party service integration patterns

**Next Phase**: 04-implementation-enhanced.md - Implementation tracking and progress monitoring  
**Estimated Time**: 30 minutes for implementation setup
