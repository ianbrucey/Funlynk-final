# Investigation Workflow

**Purpose:** Document how the `project-understanding/` folder was created, to reverse-engineer investigation protocols.

## Overview

This document traces the **actual investigation process** used to create the understanding folder for FunLynk, which will inform the design of formal investigation protocols for sub-agents.

## Investigation Process (Actual)

### Phase 1: Project Identity Discovery (30 min)

**Goal:** Understand what the project is and what tech stack it uses.

**Files Examined:**
1. `composer.json` - PHP dependencies, Laravel version, key packages
2. `package.json` - Frontend dependencies, build tools
3. `.` (root directory) - Project structure overview
4. `README.md` - Project description (if exists)
5. `app/` directory - Application structure

**Information Extracted:**
- Framework: Laravel 12
- Key packages: Filament v4, Livewire v3, PostGIS, Meilisearch, Stripe
- Project structure: Standard Laravel with Livewire components
- Business domain: Activity discovery platform

**Output:** `00-project-identity.md`

**Key Questions Answered:**
- What is this project?
- What tech stack does it use?
- What are the core concepts?
- How is the code organized?

### Phase 2: Architecture Discovery (45 min)

**Goal:** Understand system architecture and data flow.

**Files Examined:**
1. `app/Models/` - Core domain models
2. `app/Services/` - Business logic layer
3. `app/Events/` - Event-driven patterns
4. `app/Listeners/` - Event handlers
5. `config/` - Configuration files (database, queue, cache)

**Information Extracted:**
- Layered architecture (Presentation → Application → Business → Data)
- Core entities: User, Post, Activity, Group, Conversation
- Data flow patterns: Post creation, Post-to-Event conversion, real-time chat
- Integration points: Stripe, Meilisearch, Reverb

**Output:** `01-architecture-overview.md`

**Key Questions Answered:**
- How is the system structured?
- What are the main components?
- How do they communicate?
- What external services are integrated?

### Phase 3: Database Schema Discovery (30 min)

**Goal:** Map complete database schema and relationships.

**Files Examined:**
1. `database/migrations/` - All migration files (chronological order)
2. `app/Models/` - Model relationships and casts
3. Key models: `User.php`, `Post.php`, `Activity.php`, `Group.php`

**Information Extracted:**
- Table structures (columns, types, constraints)
- PostGIS geography columns for spatial data
- Indexes (spatial, full-text, foreign keys)
- Relationships (belongsTo, hasMany, belongsToMany)

**Output:** `02-database-schema.md`

**Key Questions Answered:**
- What tables exist?
- What are the column definitions?
- How are tables related?
- What indexes are used?

### Phase 4: API Contracts Discovery (30 min)

**Goal:** Document all routes and API contracts.

**Files Examined:**
1. `routes/web.php` - Web routes
2. `routes/api.php` - API routes
3. `routes/channels.php` - WebSocket channels
4. `app/Livewire/` - Livewire component public methods

**Information Extracted:**
- Route structure (authentication, onboarding, features)
- Livewire component contracts (public properties/methods)
- WebSocket events and channels
- External API integrations

**Output:** `03-api-contracts.md`

**Key Questions Answered:**
- What routes exist?
- What are the Livewire component APIs?
- What WebSocket events are broadcast?
- How do external APIs integrate?

### Phase 5: UI Patterns Discovery (45 min)

**Goal:** Extract design system and UI component patterns.

**Files Examined:**
1. `resources/views/welcome.blade.php` - Landing page (full example)
2. `resources/views/components/galaxy-layout.blade.php` - Base layout
3. `resources/views/livewire/auth/login.blade.php` - Form example
4. `resources/css/app.css` - Global styles
5. `context-engine/domain-contexts/ui-design-standards.md` - Existing docs

**Information Extracted:**
- Galaxy theme with glass morphism
- Layout components (galaxy-layout, glass cards)
- Component patterns (buttons, forms, cards, navigation)
- Livewire component structure
- Responsive design patterns

**Output:** `04-ui-patterns.md`

**Key Questions Answered:**
- What design system is used?
- What are the standard UI components?
- How are Livewire components structured?
- What are the responsive design patterns?

### Phase 6: Backend Patterns Discovery (30 min)

**Goal:** Document backend conventions and patterns.

**Files Examined:**
1. `app/Services/PostService.php` - Service layer example
2. `app/Models/User.php` - Model example
3. `app/Events/PostCreated.php` - Event example
4. `app/Listeners/SendPostNotification.php` - Listener example
5. `app/Policies/PostPolicy.php` - Policy example
6. `app/Jobs/ExpirePostsJob.php` - Job example

**Information Extracted:**
- Service layer architecture
- Model conventions (including PostGIS)
- Event-driven patterns
- Validation patterns
- Authorization patterns
- Transaction patterns
- Error handling

**Output:** `05-backend-patterns.md`

**Key Questions Answered:**
- How is business logic organized?
- What are the model conventions?
- How are events and listeners used?
- What validation patterns are used?

### Phase 7: Testing Conventions Discovery (30 min)

**Goal:** Document testing approach and conventions.

**Files Examined:**
1. `tests/Pest.php` - Pest configuration
2. `tests/Feature/Services/PostServiceTest.php` - Service test example
3. `tests/Feature/Livewire/` - Livewire test examples
4. `database/factories/PostFactory.php` - Factory example

**Information Extracted:**
- Pest v4 testing framework
- Test structure (Feature vs Unit)
- Test patterns (services, Livewire, events, database)
- Factory patterns
- Assertion patterns

**Output:** `06-testing-conventions.md`

**Key Questions Answered:**
- What testing framework is used?
- How are tests structured?
- What are the test patterns?
- How are factories used?

### Phase 8: Deployment Infrastructure Discovery (30 min)

**Goal:** Document deployment and infrastructure setup.

**Files Examined:**
1. `.env.example` - Environment configuration
2. `composer.json` - Scripts and dependencies
3. `vite.config.js` - Asset compilation
4. `config/database.php` - Database configuration
5. `config/queue.php` - Queue configuration

**Information Extracted:**
- Development environment setup
- Database management (migrations, PostGIS)
- Queue system configuration
- Search infrastructure (Meilisearch)
- WebSocket server (Reverb)
- Production deployment steps

**Output:** `07-deployment-infrastructure.md`

**Key Questions Answered:**
- How do I set up the development environment?
- What services are required?
- How is the project deployed?
- What monitoring/logging is used?

## Total Time Investment

**Actual time:** ~4 hours  
**Breakdown:**
- Phase 1 (Identity): 30 min
- Phase 2 (Architecture): 45 min
- Phase 3 (Database): 30 min
- Phase 4 (API): 30 min
- Phase 5 (UI): 45 min
- Phase 6 (Backend): 30 min
- Phase 7 (Testing): 30 min
- Phase 8 (Deployment): 30 min

## Key Insights for Protocol Design

### 1. Investigation is Sequential
Each phase builds on previous phases:
- Identity → Architecture → Database → API → UI/Backend → Testing → Deployment

### 2. File Selection is Critical
Knowing which files to examine is 80% of the work:
- `composer.json` reveals tech stack
- `app/Models/` reveals domain entities
- `database/migrations/` reveals schema
- `routes/web.php` reveals features

### 3. Pattern Recognition is Key
Looking for patterns, not exhaustive documentation:
- One service class example → service layer pattern
- One Livewire component → component structure pattern
- One test file → testing conventions

### 4. Context Matters
Understanding the project's domain helps interpret code:
- "Posts vs Events" dual model is unique to FunLynk
- PostGIS usage indicates location-first design
- Livewire indicates server-rendered approach

## Next Steps: Protocol Design

Based on this investigation workflow, we can now design:

1. **Investigation Protocol Templates** - Formal procedures for each phase
2. **File Discovery Heuristics** - How to find the right files to examine
3. **Pattern Extraction Guidelines** - How to identify and document patterns
4. **Output Format Standards** - Consistent structure for investigation reports

See next document: `PROTOCOL-DESIGN.md` (to be created)

