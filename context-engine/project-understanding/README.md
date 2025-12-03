# Project Understanding Folder

**Purpose:** Comprehensive reference documentation for understanding the FunLynk codebase.

**Generated:** 2025-12-02  
**Project:** FunLynk (Laravel 12 Activity Discovery Platform)

## What is This?

This folder contains **reverse-engineered documentation** created by analyzing the FunLynk codebase. It serves as a **reference template** for building similar understanding folders for other projects.

The goal is to capture everything a new developer (or AI agent) needs to understand the project's:
- Identity and purpose
- Architecture and design patterns
- Database schema and relationships
- API contracts and routes
- UI patterns and design system
- Backend conventions and service layer
- Testing approach and conventions
- Deployment and infrastructure

## Document Structure

### 00-project-identity.md
**What it covers:**
- Project overview and business domain
- Tech stack (frameworks, libraries, tools)
- Project structure and directory layout
- Core architectural concepts
- Key dependencies
- Entry points and development commands

**When to read:** First document to read when joining the project

### 01-architecture-overview.md
**What it covers:**
- System architecture (layered monolith)
- Core domain models and entities
- Data flow patterns (creation, conversion, real-time)
- Integration points (external services, internal communication)
- Background job processing
- Caching strategy
- Security architecture

**When to read:** After understanding project identity, before diving into code

### 02-database-schema.md
**What it covers:**
- Complete database schema (all tables)
- Column definitions and types
- PostGIS spatial columns
- Indexes and constraints
- Relationships between models
- Pivot tables

**When to read:** When working with models, migrations, or database queries

### 03-api-contracts.md
**What it covers:**
- Route structure (web and API routes)
- Livewire component contracts (public methods/properties)
- WebSocket events and channels
- External API integrations (Stripe, Meilisearch)
- Response formats
- Authentication and middleware

**When to read:** When building new features or integrating with existing endpoints

### 04-ui-patterns.md
**What it covers:**
- Design system (galaxy theme, glass morphism)
- Layout components (galaxy-layout, glass cards)
- Component patterns (buttons, forms, cards, navigation, modals)
- Livewire component structure
- Typography and responsive design
- Animation and transitions

**When to read:** When building UI components or pages

### 05-backend-patterns.md
**What it covers:**
- Service layer architecture
- Model conventions (including PostGIS spatial models)
- Event-driven architecture (events and listeners)
- Validation patterns (form requests, Livewire validation)
- Authorization patterns (policies)
- Database transaction pattern
- Error handling
- Queue job pattern

**When to read:** When implementing business logic or backend features

### 06-testing-conventions.md
**What it covers:**
- Testing framework (Pest v4)
- Test structure and organization
- Feature test patterns (services, Livewire, events, database)
- Factory patterns
- Assertion patterns (Pest expectations, PHPUnit assertions)
- Test helpers and shared data
- Running tests

**When to read:** When writing tests or understanding test coverage

### 07-deployment-infrastructure.md
**What it covers:**
- Development environment setup
- Database management (migrations, PostGIS, seeders)
- Queue system configuration
- Search infrastructure (Meilisearch)
- WebSocket server (Reverb)
- Asset compilation (Vite)
- Production deployment steps
- Monitoring and logging
- Backup strategy

**When to read:** When setting up development environment or deploying to production

## How to Use This Folder

### For New Developers
1. Start with `00-project-identity.md` to understand what the project is
2. Read `01-architecture-overview.md` to understand how it's structured
3. Skim the other documents to know what's available
4. Reference specific documents as needed when working on features

### For AI Agents (Sub-Agents)
When crafting prompts for sub-agents, reference specific documents:

**Example prompt structure:**
```
Read the following files for context:
- project-understanding/00-project-identity.md
- project-understanding/05-backend-patterns.md
- app/Services/ExistingService.php

Create a new service class following the patterns documented in 05-backend-patterns.md...
```

### For Documentation Updates
As the project evolves, update these documents to reflect:
- New architectural patterns
- New dependencies or tech stack changes
- New conventions or best practices
- New deployment requirements

## What's Missing?

This folder intentionally does NOT include:
- **Feature-specific documentation** - See `context-engine/epics/` and `context-engine/tasks/`
- **Business logic details** - See service classes and models
- **API endpoint details** - See route files and controllers
- **Detailed code examples** - See actual implementation files

This folder provides **patterns and conventions**, not exhaustive documentation.

## Relationship to Other Documentation

```
project-understanding/          # Patterns, conventions, architecture
    ↓ complements
context-engine/
├── global-context.md          # Project-specific context
├── epics/                     # Feature modules
├── tasks/                     # Implementation tasks
└── domain-contexts/           # Cross-cutting technical standards
```

**Key difference:**
- `project-understanding/` = **How the project works** (patterns, architecture)
- `context-engine/` = **What to build** (features, tasks, requirements)

## Generating This Folder for Other Projects

To create a similar understanding folder for a new project:

1. **Analyze the codebase** - Examine key files, patterns, conventions
2. **Extract patterns** - Identify common architectural patterns
3. **Document conventions** - Capture naming, structure, style conventions
4. **Map relationships** - Understand how components interact
5. **Create reference docs** - Write 7-8 documents covering all aspects

**Time estimate:** 4-6 hours for a medium-sized project

## Next Steps

This folder serves as a **reference template** for building investigation protocols. The next phase is to:

1. **Reverse-engineer the process** - How would sub-agents generate these documents?
2. **Create investigation protocols** - Formal procedures for analyzing codebases
3. **Build prompt templates** - Standardized prompts for sub-agent investigations
4. **Test on new projects** - Validate the protocols on different codebases

See `.augment/rules/AGENTS.md` for the multi-agent workflow documentation.

