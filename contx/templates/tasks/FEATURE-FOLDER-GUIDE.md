# Feature Folder Structure Guide

## Naming Convention

Use the hierarchical approach with clear categorization:

```
F{NN}_{Epic_Category}_{Feature_Name}/
```

**Examples**:
- `F01_User_Management_User_Follows_Another_User/`
- `F02_Activity_Management_Create_Free_Activity/`
- `F03_Payments_Stripe_Connect_Host_Onboarding/`
- `F04_Discovery_Map_Based_Activity_Search/`

## Folder Structure

```
F{NN}_{Epic_Category}_{Feature_Name}/
├── 01_Problem_Statement.md
├── 02_Research.md
├── 03_Plan/
│   ├── 01_UX_Specification.md
│   ├── 02_Backend_Specification.md
│   ├── 03_Frontend_Specification.md
│   └── 04_Third_Party_Services.md
├── 04_Implementation_Tracker.md
├── README.md
└── assets/
    ├── wireframes/
    ├── designs/
    └── diagrams/
```

## Epic Categories

Based on your Funlynk project architecture:

1. **Core_Infrastructure** - Database, auth, geolocation, notifications
2. **User_Management** - Profiles, social graph, follows
3. **Activity_Management** - CRUD operations, tagging, location, RSVPs
4. **Discovery_Engagement** - Feed, search, comments, flares
5. **Payments_Monetization** - Stripe integration, tickets, fees
6. **Administration** - Moderation, dashboard, analytics

## Task Granularity Guidelines

### The "Goldilocks Zone" for AI Agent Tasks

**Perfect Task Size** (1-4 hours for a human developer):
- ✅ Single, clear responsibility
- ✅ Unambiguous inputs and outputs  
- ✅ Can be completed and tested in isolation
- ✅ Delivers testable functionality

### Good Task Examples

**Backend Tasks**:
```
BE-1: Create a new table named 'Follows' with columns: follower_id (indexed), 
following_id (indexed), created_at (timestamp), with a unique constraint on 
the (follower_id, following_id) pair.

BE-2: Create a POST /api/users/{userId}/follow endpoint that:
- Requires authentication
- Creates a new record in the Follows table
- Increments following_count on follower's user record
- Increments follower_count on followed user's record
- Returns 201 on success, 409 if already following
```

**Frontend Tasks**:
```
FE-1: Create a reusable FollowButton.jsx component that:
- Accepts props: isFollowing (boolean), userId (string), onToggle (function)
- Displays "Follow" or "Following" based on state
- Handles click events with optimistic UI updates
- Shows loading state during API calls
- Handles error states gracefully

FE-2: Integrate FollowButton into UserProfile.jsx page:
- Fetch initial follow status from API
- Update local state when follow status changes
- Hide button when viewing own profile
```

### Tasks That Are Too Big ❌

```
❌ "Build the user profile page"
❌ "Implement the entire follow system"  
❌ "Create the activity feed"
```

### Tasks That Are Too Small ❌

```
❌ "Add a console.log statement"
❌ "Change button color to blue"
❌ "Import React in component"
```

## Feature Complexity Classification

### Small Features (1-2 days)
- Simple CRUD operations
- Basic UI components
- Straightforward API endpoints
- **Example**: User can update their bio

### Medium Features (3-5 days)  
- Multi-step user flows
- Complex state management
- Multiple API integrations
- **Example**: User follows another user

### Large Features (1-2 weeks)
- Cross-cutting concerns
- Multiple technical domains
- Complex business logic
- **Example**: Activity discovery with map integration

## AI Agent Prompt Patterns

### Effective Prompt Structure

```
Context: [Brief feature context]
Task: [Specific, actionable task]
Requirements: [Detailed specifications]
Constraints: [Technical/business limitations]
Expected Output: [What should be delivered]
Testing: [How to verify success]
```

### Example Prompt

```
Context: We're building a follow system for our social activity app.

Task: Create a React Native component for a follow button.

Requirements:
- Component name: FollowButton
- Props: isFollowing (boolean), userId (string), onToggle (function)
- Visual states: "Follow" (blue), "Following" (gray), "Loading" (spinner)
- Handle press events with haptic feedback
- Use our design system colors and typography

Constraints:
- Must work on both iOS and Android
- Follow our existing component patterns in /components
- Use TypeScript with proper prop types

Expected Output:
- FollowButton.tsx component file
- Proper TypeScript interfaces
- Styled using our theme system

Testing:
- Component should render correctly in different states
- Press events should call onToggle with userId
- Loading state should disable interaction
```

## Integration with Context Engine

### Automatic Context Loading

When working on a feature, AI agents should automatically load:

1. **Global Context** - Project overview and architecture
2. **Domain Context** - Relevant technical domain knowledge
3. **Feature Context** - Current feature specifications
4. **Standards** - Coding and architectural guidelines

### Task State Management

Use the active task pointer system:

```bash
# Set current feature
./contx/scripts/set-active-task.sh F01_User_Management_User_Follows_Another_User \
  --title "User Follows Another User" \
  --status in-progress \
  --priority P1
```

This ensures all AI agents know the current focus and can provide contextually relevant assistance.
