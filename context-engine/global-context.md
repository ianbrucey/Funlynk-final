# Universal Global Project Context

## Problem Definition & Scope

 **Problem Statement** : It is difficult for individuals to discover and participate in niche, spontaneous, or hyper-local activities (e.g., pickup basketball, music jam sessions, drumline practices). Existing platforms are geared towards formal, pre-planned events, leaving a gap for real-time, community-driven discovery.

 **Project Scope** : This project is a Laravel web application designed for activity-first discovery.

* **In Scope:** User profiles, a follower-based social graph, creation of free and paid activities, a map-based discovery engine, a "Flare" system for activity inquiries, and integrated payments for hosts.
* **Out of Scope (for MVP):** Advanced event management tools (e.g., complex ticketing tiers, assigned seating), formal group/club management pages, in-app advertising platforms.

 **Target Users** :

1. **Attendees/Seekers:** Socially active individuals (ages 18-35), particularly college students and residents of dense urban areas, looking for spontaneous things to do.
2. **Hosts/Creators:** Organizers of niche communities (e.g., local musicians, sports enthusiasts, dance instructors, workshop leaders) who need a simple way to broadcast their activities and manage payments.

 **Success Metrics** :

* **Engagement:** High ratio of Daily Active Users (DAU) to Monthly Active Users (MAU).
* **Content Liquidity:** A consistently growing number of new activities posted per day in a target launch area.
* **Network Effect:** High number of RSVPs per activity and a growing number of follows between users.
* **Validation:** Successful processing of paid transactions for ticketed events.

## Solution Architecture

 **Architecture Pattern** : Laravel web application with Filament for CRUD operations and custom views for specialized functionality.

 **Technology Stack** :

* **Backend & Frontend:** Laravel 12 with Filament v4, Livewire v3, and DaisyUI
* **Database:** PostgreSQL with PostGIS for geolocation
* **Testing:** Pest v4 with browser testing capabilities
* **Future Plans:** React Native mobile app after Laravel completion

 **Key Architectural Decisions** :

* **Laravel-First Strategy** : The primary focus is building a robust Laravel web application. All CRUD operations use Filament framework. Custom views are only created for specialized functionality that Filament cannot handle.
* **Asymmetrical Follower Model** : A one-way "follower" system (like Instagram) is used over a two-way "friend" system to reduce social friction and empower hosts to build audiences.
* **Centralized Payments via Stripe Connect** : All payment complexities (payouts, KYC, security) are delegated to Stripe Connect (Express model) to minimize development overhead and legal/compliance risk.

## Module Breakdown & Responsibilities

 **Core Modules** :

1. **Core Infrastructure** : The foundational layer, including the Database Schema (PostgreSQL), Authentication Service (Supabase Auth), Geolocation Service (PostGIS), and a centralized Notification Service.
2. **User & Profile Management** : Handles user identity, profiles (bios, interests), and the Social Graph (follower relationships).
3. **Activity Management** : Manages the full lifecycle (CRUD) of activities, including tagging, location, and attendance (RSVPs).
4. **Discovery & Engagement** : Powers the user-facing experience through a dynamic Feed Generation Service, a Search/Filter Service, and social interaction modules like Comments and the "Flare" inquiry system.
5. **Payments & Monetization** : Integrates with Stripe Connect to handle host onboarding, ticket purchasing, and platform fees.
6. **Administration** : Includes backend tools for content moderation and a dashboard for platform oversight.

 **Integration Points** : Modules communicate primarily via internal API calls. Key external integration points are REST APIs and webhooks for Stripe (payments) and push notification services (APNS/FCM).

 **Shared Dependencies** : Laravel Framework, Filament, Livewire, DaisyUI, Stripe SDK, PostGIS.

## Project Acceptance Criteria

* [X] Users can create an account, build a profile, and follow other users.
* [X] Users can create, view, and RSVP to free and paid activities.
* [X] A dynamic feed displays relevant activities based on location and interests.
* [X] Hosts can securely set up an account to receive payments via Stripe.
* [X] Attendees can securely purchase a ticket for a paid activity.
* [X] The application is performant, with core content loading in under 3 seconds on a standard mobile connection.
* [X] The platform is secure, with all user data protected and payment information handled exclusively by Stripe.

## Key Constraints & Guidelines

 **Technical Constraints** :

* The Laravel web application must be performant and scalable.
* The system must be able to handle real-time location queries efficiently using PostGIS.
* All payment processing must be PCI compliant by offloading to a certified third party (Stripe).

 **Business Constraints** :

* The initial launch will be geo-fenced or focused on a single niche community (e.g., a specific college campus or city neighborhood) to solve the chicken-and-egg problem.
* The platform must be free for users to join and post free activities to encourage initial adoption.

 **Coding Standards** :

* PHP code will be formatted using Laravel Pint.
* All tests must be written using Pest v4.
* Follow Laravel best practices and conventions.
* Use Filament for all CRUD operations; custom views only for specialized functionality.
* Use DaisyUI for all UI components and styling.

## Architectural & Coding Standards
**Mandatory Adherence**: Before writing or modifying any code, you **MUST** consult the documents within the `context-engine/standards/` directory. These documents define the mandatory coding standards, design patterns, and architectural guidelines for this project.

- **Adherence is not optional.**
- If a standard for a specific situation is not defined, you must follow the established conventions in the existing codebase.
- The standards directory is the single source of truth for all coding and architectural decisions.

## Enhanced Context Engineering Workflow

### Hierarchical Development Approach

**Three-Level Hierarchy**:
1. **Epics**: Large-scale modules (e.g., "User & Profile Management", "Activity Management")
2. **Features**: Specific user-facing capabilities within an Epic (e.g., "User Follows Another User")
3. **Tasks**: Granular implementation steps that AI agents execute (e.g., "Create FollowButton component")

### Feature Development Process

When assigned a new feature, AI assistants should:

1. **Create Feature Workspace**: Establish dedicated feature folder using naming convention `F{NN}_{Epic_Category}_{Feature_Name}/`
2. **Reference Global Context**: Always start by reviewing this document and relevant domain contexts
3. **Follow 4-Phase Structure**: Problem Definition → Research → Enhanced Planning → Implementation Tracking
4. **Use Structured Planning**: Break down features into UX, Backend, Frontend, and Third-party service specifications
5. **Define Granular Tasks**: Create specific, testable tasks in the "Goldilocks Zone" (1-4 hours for human developer)
6. **Maintain Feature State**: Track progress across all technical domains
7. **Document Decisions**: Update decision logs during implementation
8. **Suggest Testing**: Always recommend comprehensive testing after implementation

## Enhanced Feature Workspace System

**Feature Folder Structure**: `context-engine/tasks/F{NN}_{Epic_Category}_{Feature_Name}/`

```
F01_User_Management_User_Follows_Another_User/
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

**Epic Categories for Funlynk**:
- **Core_Infrastructure**: Database, auth, geolocation, notifications
- **User_Management**: Profiles, social graph, follows
- **Activity_Management**: CRUD operations, tagging, location, RSVPs
- **Discovery_Engagement**: Feed, search, comments, flares
- **Payments_Monetization**: Stripe integration, tickets, fees
- **Administration**: Moderation, dashboard, analytics

## Task Granularity Guidelines

**Perfect Task Size** (1-4 hours for human developer):
- ✅ Single, clear responsibility
- ✅ Unambiguous inputs and outputs
- ✅ Can be completed and tested in isolation
- ✅ Delivers testable functionality

**Good Task Examples**:
- `BE-1: Create 'Follows' table with follower_id, following_id columns and unique constraint`
- `FE-1: Create FollowButton component with isFollowing and userId props`
- `INT-1: Integrate FollowButton into UserProfile page with state management`

**Avoid Tasks That Are**:
- ❌ Too Broad: "Build the user profile page"
- ❌ Too Small: "Add console.log statement"

## Available Templates & Contexts

**Enhanced Task Templates**:
- **Problem Definition Template**: Define the "what" and "why"
- **Research Template**: Explore solutions and technical approaches
- **Enhanced Planning Templates**:
  - UX Specification (user journey, components, accessibility)
  - Backend Specification (database, APIs, business logic)
  - Frontend Specification (components, state, navigation)
  - Third-party Services (integrations, APIs, webhooks)
- **Enhanced Implementation Tracker**: Progress tracking by technical domain

**Domain Contexts** (automatically applied based on task relevance):
- **Authentication & Authorization** (Laravel Auth, Filament)
- **Database Operations** (Eloquent ORM, PostgreSQL, PostGIS)
- **CRUD Operations** (Filament Resources)
- **Frontend Components** (Livewire, DaisyUI)
- **Testing** (Pest v4, Browser Testing)
- **Geolocation & Mapping** (PostGIS)
- **Payments** (Stripe Connect)

## Decision History

 **September 16, 2025** : Chose Laravel with Filament as the primary development framework. -  **Rationale** : Provides robust CRUD capabilities through Filament, reducing development time while maintaining flexibility for custom functionality. -  **Impact** : Faster time-to-market for web application; React Native mobile app planned for future phase.

 **September 17, 2025** : Enhanced context engineering framework with hierarchical Epic→Feature→Task structure. -  **Rationale** : Conversation analysis revealed need for more granular, domain-specific planning to improve AI agent effectiveness. -  **Impact** : Better task scoping, clearer separation of concerns across UX/Backend/Frontend/Third-party domains.

## Notes & Updates

Last Updated: September 17, 2025

Updated By: System Architect

Changes: Enhanced framework with hierarchical structure, domain-specific planning templates, and improved task granularity guidelines based on AI agent workflow optimization.

---

## Tool-Specific Instructions

### For Augment Users
- Reference this context with @global-context
- Use enhanced templates: @feature-folder-guide, @ux-specification, @backend-specification, @frontend-specification
- Domain contexts will auto-trigger based on keywords
- Create feature folders using F{NN}_{Epic}_{Feature} naming convention

### For Warp Users
- This context is automatically applied via WARP.md
- Subdirectory-specific rules provide additional context
- All terminal AI interactions include this guidance
- Use hierarchical Epic→Feature→Task approach for planning

### For Gemini CLI Users
- This context is loaded via GEMINI.md
- Context hierarchy provides progressive detail
- Memory management preserves conversation context
- Follow 4-phase feature development process

### Universal Guidelines
- Always use hierarchical Epic→Feature→Task structure
- Break features into UX, Backend, Frontend, and Third-party specifications
- Define tasks in the "Goldilocks Zone" (1-4 hours for human developer)
- Include explicit acceptance criteria and testing strategies
- Document architectural decisions in decision logs
- Consider forward compatibility and technical debt
- Update context documents when making architectural changes
- Use task management tools for complex multi-step work
- Suggest comprehensive testing after code changes
