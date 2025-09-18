# Universal Global Project Context

## Problem Definition & Scope

 **Problem Statement** : It is difficult for individuals to discover and participate in niche, spontaneous, or hyper-local activities (e.g., pickup basketball, music jam sessions, drumline practices). Existing platforms are geared towards formal, pre-planned events, leaving a gap for real-time, community-driven discovery.

 **Project Scope** : This project is a mobile-first social network designed for activity-first discovery.

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

 **Architecture Pattern** : The backend will follow a Backend-as-a-Service (BaaS) pattern to accelerate development, leveraging a modular, service-oriented structure for any custom logic. The frontend will be a component-based, single-page application.

 **Technology Stack** :

* **Frontend (Mobile App):** React Native with Expo
* **Frontend (Web):** Next.js (for a marketing landing page and future web app)
* **Backend & Database:** Supabase (utilizing its integrated PostgreSQL database, Authentication, and Realtime services)
* **Infrastructure:** Hosted by Supabase (backend) and Vercel (frontend).

 **Key Architectural Decisions** :

* **Mobile-First Strategy** : The primary user experience is the mobile app to cater to the on-the-go, spontaneous nature of the platform's use case.
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

 **Shared Dependencies** : Supabase Client Library, Stripe SDK, React Navigation, Mapbox/MapLibre GL JS.

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

* The mobile application must be cross-platform (iOS and Android) from a single codebase.
* The system must be able to handle real-time location queries efficiently.
* All payment processing must be PCI compliant by offloading to a certified third party (Stripe).

 **Business Constraints** :

* The initial launch will be geo-fenced or focused on a single niche community (e.g., a specific college campus or city neighborhood) to solve the chicken-and-egg problem.
* The platform must be free for users to join and post free activities to encourage initial adoption.

 **Coding Standards** :

* Code will be formatted using Prettier.
* JavaScript/TypeScript will be linted using ESLint with a standard ruleset.
* All API endpoints must be documented (e.g., using OpenAPI/Swagger).
* Components should be functional and utilize hooks, adhering to modern React best practices.

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
- **Authentication & Authorization** (Supabase Auth)
- **Database Operations** (Supabase Client, PostgreSQL)
- **API Design** (RESTful principles)
- **Frontend Components** (React Native, Expo)
- **State Management** (Zustand/Redux)
- **Geolocation & Mapping** (Mapbox, PostGIS)
- **Payments** (Stripe Connect)

## Decision History

 **September 16, 2025** : Chose Supabase as the BaaS provider to accelerate MVP development. -  **Rationale** : Reduces the need to build a custom backend from scratch, providing auth, database, and real-time capabilities out-of-the-box. -  **Impact** : Faster time-to-market; introduces a dependency on the Supabase ecosystem.

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
