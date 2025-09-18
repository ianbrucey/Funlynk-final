
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

## Context Engineering Workflow

When assigned a new task, AI assistants should:

1. **Create Task Workspace** : Establish dedicated task folder with persistent context.
2. **Reference Global Context** : Always start by reviewing this document.
3. **Select Template** : Choose appropriate task template based on complexity.
4. **Gather Domain Context** : Apply relevant domain-specific knowledge.
5. **Structure Planning** : Follow Problem → Solution → Rationale → Research → Plan.
6. **Maintain Task State** : Track progress, decisions, and context across sessions.
7. **Document Decisions** : Update decision logs during implementation.
8. **Suggest Testing** : Always recommend writing unit and/or integration tests after implementation.

## Task Workspace System

 **Task Folder Structure** : `.contx/tasks/task-[ID]-[brief-description]/`

* Each task gets a dedicated workspace for context persistence.
* Maintains state across multiple work sessions.
* Includes progress tracking, decision logs, and next steps.
* Links to related code files and dependencies.

## Available Templates & Contexts

 **Task Templates** :

* Simple Task Template: For straightforward implementations.
* Complex Task Template: For multi-step architectural changes.
* Research Template: For investigation and discovery tasks.

 **Domain Contexts** : Automatically applied based on task relevance:

* Authentication & Authorization (Supabase Auth)
* Database Operations (Supabase Client, PostgreSQL)
* API Design (RESTful principles)
* Frontend Components (React Native, Expo)
* State Management (Zustand/Redux)
* Geolocation & Mapping (Mapbox, PostGIS)
* Payments (Stripe Connect)

## Decision History

 **September 16, 2025** : Chose Supabase as the BaaS provider to accelerate MVP development. -  **Rationale** : Reduces the need to build a custom backend from scratch, providing auth, database, and real-time capabilities out-of-the-box. -  **Impact** : Faster time-to-market; introduces a dependency on the Supabase ecosystem.

## Notes & Updates

Last Updated: September 16, 2025

Updated By: System Architect

Changes: Initial document creation.
