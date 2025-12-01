
# FunLynk Agent Navigation Guide

## Project Identity

**FunLynk**: Laravel 12 web app for spontaneous, niche activity discovery. Users discover activities through ephemeral "Posts" (24-48h) that can evolve into structured "Events" based on engagement.

**Tech Stack**: Laravel 12, Filament v4, Livewire v3, PostgreSQL + PostGIS, DaisyUI, Pest v4

## Core Architecture Principle

**Posts vs Events Dual Model** - The platform's defining feature:

- **Posts**: Ephemeral (24-48h), spontaneous, 5-10km radius, reactions ("I'm down", "Join me")
- **Events**: Structured, persistent, 25-50km radius, RSVPs, payments
- **Conversion**: Posts with 5+ reactions → suggest conversion → 10+ reactions → auto-convert to Event
- **Flow**: E04 detects engagement → E03 creates Event with `originated_from_post_id`

## Project Structure

### Documentation Hierarchy

```

context-engine/

├── global-context.md           # Universal project context (READ FIRST)

├── epics/                      # 7 major modules (E01-E07)

│   └── E0X_Name/

│       ├── epic-overview.md    # Epic purpose & scope

│       ├── database-schema.md  # Tables & relationships

│       ├── api-contracts.md    # API endpoints

│       └── service-architecture.md

├── tasks/                      # Feature-level implementation docs

│   └── E0X_Name/

│       └── F0X_Feature_Name/

│           └── README.md       # 5-7 tasks, Artisan commands, time estimates

└── domain-contexts/            # Cross-cutting concerns

    ├── ui-design-standards.md  # Galaxy theme, glass morphism (CRITICAL for UI)

    ├── database-context.md     # PostGIS, spatial queries

    └── auth-context.md         # Laravel Auth, Filament

```

### Implementation Status

- ✅ **E01 Core Infrastructure**: Database, migrations, models, Filament resources COMPLETE
- 🔄 **E02-E04**: Task documentation rebuilt for Laravel (Nov 2025), ready for implementation
- ⏳ **E05-E07**: Epic planning complete, task documentation pending

## 7 Epics Overview

1. **E01 Core Infrastructure**: Database (PostGIS), Auth, Notifications - **COMPLETE**
2. **E02 User & Profile Management**: Profiles, Privacy, User Discovery
3. **E03 Activity Management**: Event CRUD, RSVPs, Tagging, **Post-to-Event Conversion (receiving)**
4. **E04 Discovery Engine**: Feeds, Recommendations, **Post-to-Event Conversion (initiating)**
5. **E05 Social Interaction**: Comments, Reactions, Communities
6. **E06 Payments & Monetization**: Stripe Connect, Subscriptions
7. **E07 Administration**: Analytics, Moderation, Monitoring

## Critical Integration Points

### E01 Foundation (Available Now)

**Tables**: users, posts, activities, post_reactions, post_conversions, rsvps, tags, follows, notifications, comments, flares, reports

**Models**: User, Post, Activity, PostReaction, PostConversion, Rsvp, Tag, Follow, Notification, Comment, Flare, Report

**Filament Resources**: UserResource, PostResource, ActivityResource, RsvpResource, TagResource, PostReactionResource, CommentResource

### PostGIS Spatial Queries (E02/F03, E04/F01)

```php

// Posts: 5-10km radius

Post::whereDistance('location_coordinates', $point, '<=', 10000)->get();


// Events: 25-50km radius

Activity::whereDistance('location_coordinates', $point, '<=', 50000)->get();

```

### Post-to-Event Conversion (E03/F01, E04/F03)

```php

// E04 detects engagement threshold

if ($post->reactions()->count() >= 5) {

    // E04 calls E03's service

    app(ActivityConversionService::class)->createFromPost($post);


    // E03 creates activity

    Activity::create([

        'originated_from_post_id' => $post->id,

        // ... copy location, time hints

    ]);

}

```

## Development Workflow

### Before Starting Any Task

1. **Read epic-overview.md** for business context
2. **Read task README.md** for implementation details (5-7 tasks with Artisan commands)
3. **Check E01 foundation** for available tables/models/resources
4. **Review domain-contexts/** for UI standards, database patterns, auth patterns

### Implementation Pattern

```bash

# Task structure (from README.md)

T01: Database/Model Setup (migrations, models, factories)

T02: Service Classes (business logic)

T03: Filament Resources (admin CRUD)

T04: Livewire Components (user-facing UI)

T05: Policies (authorization)

T06: Jobs (async processing)

T07: Tests (Pest v4)

```

### Always Use

- `php artisan make:*` commands with `--no-interaction` flag
- `casts()` method (not `$casts` property) - Laravel 12
- `->components([])` (not `->schema([])`) - Filament v4
- PostGIS for location queries via matanyadaev/laravel-eloquent-spatial
- DaisyUI classes for UI components
- Galaxy theme with glass morphism for all pages (see ui-design-standards.md)

### UI Styling - CRITICAL RULES

**EVERY page/component MUST follow the galaxy theme**. No exceptions.

**Step 1: Use the Galaxy Layout Component**

```blade

<x-galaxy-layout>

    <x-slot name="title">Page Title</x-slot>


    <!-- Your content here -->


</x-galaxy-layout>

```

**Step 2: Wrap Content in Glass Cards**

```blade

<div class="container mx-auto px-6 py-8">

    <div class="relative p-8 glass-card max-w-4xl mx-auto">

        <div class="top-accent-center"></div>

        <!-- Your content -->

    </div>

</div>

```

**Step 3: Use Gradient Buttons**

```blade

<!-- Primary -->

<button class="px-6 py-3 bg-gradient-to-r from-pink-500 to-purple-500 rounded-xl font-semibold hover:scale-105 transition-all">

    Submit

</button>


<!-- Secondary -->

<button class="px-6 py-3 bg-slate-800/50 border border-white/10 rounded-xl hover:border-cyan-500/50 transition">

    Cancel

</button>

```

**Step 4: Reference Files**

Before creating UI, review:

- `resources/views/welcome.blade.php` - Full page example
- `resources/views/livewire/auth/login.blade.php` - Form example
- `context-engine/domain-contexts/ui-design-standards.md` - Complete guide

**Step 5: Verify Checklist**

- [ ] Galaxy gradient background
- [ ] Aurora layers visible
- [ ] Stars twinkling
- [ ] Content in glass cards
- [ ] Buttons have gradients
- [ ] Forms have cyan focus glow
- [ ] Text is white/gray (readable)
- [ ] Hover effects work

## Development Progress Tracking

### Purpose

Maintain timestamped progress logs to serve as a development journal. These logs help new agents quickly understand project history, current state, and planned work without re-reading entire conversation history.

### Log File Management

- **Location**: `dev-logs/` directory at project root
- **Naming**: `YYYY-MM-DD-HH.md` (e.g., `2025-01-20-14.md` for January 20, 2025 at 2 PM)
- **Structure**: Each log file must contain exactly 3 sections:

  1. **Previously Completed** - Recent accomplishments (last 2-3 sessions)
  2. **Currently Working On** - Active tasks and current focus
  3. **Next Steps** - Planned upcoming work and priorities

### When to Update

- At the beginning of each new work session
- After completing major tasks or milestones
- Before ending a work session
- When switching between major features or epics

### Format Guidelines

- Use clear, concise bullet points
- Keep each section to 5-10 bullets maximum for readability
- Include specific file paths, feature names, and epic references
- Note any blockers or important decisions made

## Quick Reference Commands

```bash

# Documentation

cat context-engine/global-context.md                    # Start here

cat context-engine/epics/E0X_Name/epic-overview.md      # Epic context

cat context-engine/tasks/E0X_Name/F0X_Feature/README.md # Task details


# Implementation

php artisan make:filament-resource Name --generate --no-interaction

php artisan make:livewire Namespace/Component --no-interaction

php artisan make:test --pest Feature/TestName --no-interaction


# Testing

php artisan test --filter=TestName

vendor/bin/pint --dirty  # Format code before committing

```

## Critical Rules

1. **NO React Native/Supabase/TypeScript** - This is Laravel only
2. **Filament First** - Use Filament for CRUD, custom views only when necessary
3. **PostGIS for Location** - All spatial queries use PostGIS geography columns
4. **Galaxy Theme** - All UI must follow ui-design-standards.md (glass cards, aurora effects)
5. **Posts vs Events** - Always respect the dual model architecture
6. **E01 Foundation** - Always reference completed tables/models/resources
7. **Test Everything** - Write Pest tests for all features

## Multi-Agent Workflow Pattern

### Overview

FunLynk uses a **swarm intelligence** architecture where the primary agent (Claude/Augment) acts as architect/orchestrator, and sub-agents (Gemini via `spawn_sub_agent.py`) act as specialized workers executing well-defined tasks.

**Benefits:**

- Reduces primary agent context window usage
- Preserves AI credits through efficient resource allocation
- Enables parallel execution of independent tasks
- Reduces cognitive load on primary agent
- Allows specialized focus on discrete problems

### Agent Roles

#### Primary Agent (You - Claude/Augment)

**Responsibilities:**

- High-level planning and orchestration
- Reading and understanding project documentation (context-engine/, AGENTS.md)
- Breaking down complex features into discrete, self-contained tasks
- Crafting detailed, context-rich prompts for sub-agents
- Reviewing sub-agent outputs and integrating them into the codebase
- Making final decisions on architecture and implementation approach
- Handling tasks requiring deep context or cross-cutting concerns

**When to Handle Tasks Yourself:**

- Reading/analyzing documentation (you have better context retention)
- Making architectural decisions
- Tasks requiring knowledge of previous conversation history
- Complex refactoring across multiple files
- Tasks requiring interactive debugging or iteration
- Final code integration and testing
- Reviewing and approving sub-agent outputs

#### Sub-Agent (Gemini via spawn_sub_agent.py)

**Responsibilities:**

- Executing specific, well-defined tasks with complete instructions
- Generating code, migrations, services, tests based on detailed specs
- Analyzing specific files and producing structured reports
- Creating documentation from templates
- Performing repetitive or parallelizable work

**When to Delegate to Sub-Agents:**

- Creating boilerplate code (migrations, models, factories)
- Generating service classes from detailed specifications
- Writing tests based on clear requirements
- Analyzing specific files for patterns or issues
- Creating documentation from structured data
- Tasks that can be fully specified without conversation history

**Critical Constraint:**

Sub-agents are **STATELESS** - they have NO memory of previous conversations or context. Every prompt must be completely self-contained.

### Crafting Effective Sub-Agent Prompts

#### Template Structure

```

[CONTEXT SECTION]

Read the following files for context:

- {exact file path 1}

- {exact file path 2}

- {documentation path}


[TASK DESCRIPTION]

{Clear, specific task description}


[REQUIREMENTS]

- Requirement 1 (with specific details)

- Requirement 2 (with specific details)

- Follow {specific pattern/convention}


[OUTPUT SPECIFICATION]

Output format: {exact format description}

File location: {where to save if applicable}

Include: {specific elements to include}

```

#### Good Prompt Example

```

Read the following files for context:

- context-engine/epics/E02_User_Profile_Management/epic-overview.md

- context-engine/tasks/E02_User_Profile_Management/F01_Profile_CRUD/README.md

- app/Models/User.php

- context-engine/domain-contexts/service-architecture.md


Create a UserProfileService class that implements profile CRUD operations following Laravel 12 conventions.


Requirements:

- Class location: app/Services/UserProfileService.php

- Namespace: App\Services

- Methods to implement:

  * createProfile(User $user, array $data): UserProfile

  * updateProfile(UserProfile $profile, array $data): UserProfile

  * getProfile(int $userId): ?UserProfile

  * deleteProfile(int $profileId): bool

- Use the User model from app/Models/User.php

- Follow the service architecture pattern from service-architecture.md

- Include proper type hints and return types (Laravel 12 style)

- Add PHPDoc blocks for each method

- Handle validation using Laravel's validator

- Throw appropriate exceptions for error cases


Output format: Complete PHP class code with proper formatting

Include: namespace, use statements, class definition, all methods with implementation

```

#### Bad Prompt Example (DO NOT USE)

```

Create the user profile service we discussed earlier.

```

**Why it's bad:** No context files, no specific requirements, references "earlier" conversation that sub-agent can't access.

### Workflow Process

#### Step 1: Planning Phase (Primary Agent)

1. Read relevant documentation (epic-overview.md, task README.md)
2. Understand the full scope of the feature
3. Identify discrete, independent tasks suitable for delegation
4. Determine which tasks you'll handle vs. delegate

#### Step 2: Task Decomposition (Primary Agent)

Break down complex features into sub-agent-friendly tasks:

**Example: E02/F01 Profile CRUD Feature**

- Task 1 (Sub-agent): Create UserProfile migration
- Task 2 (Sub-agent): Create UserProfile model with relationships
- Task 3 (Sub-agent): Create UserProfileService class
- Task 4 (Sub-agent): Create UserProfileFactory for testing
- Task 5 (Primary): Create Filament resource (requires UI decisions)
- Task 6 (Sub-agent): Create Pest tests for service class
- Task 7 (Primary): Integration testing and review

#### Step 3: Prompt Crafting (Primary Agent)

For each delegated task:

1. Identify all files the sub-agent needs to read
2. Specify exact requirements and constraints
3. Define output format and location
4. Include references to FunLynk documentation
5. Add Laravel 12 / Filament v4 specific conventions

#### Step 4: Execution (Sub-Agent)

```bash

python3 spawn_sub_agent.py gemini "YOUR DETAILED PROMPT HERE"

```

The script returns a job_id immediately. Output will be in:

```

subagent_runs/{job_id}/

├── prompt.txt          # Your prompt

├── status.json         # Job status and timing

├── output.jsonl        # Event log

├── report.md           # Sub-agent's output

└── run.log            # Execution logs

```

#### Step 5: Review & Integration (Primary Agent)

1. Wait for job completion (check status.json)
2. Read report.md to review sub-agent output
3. Validate output meets requirements
4. Integrate code into codebase (copy to appropriate files)
5. Run tests and fix any issues
6. Iterate if needed (spawn new sub-agent task with corrections)

### Parallel Execution Pattern

For independent tasks, spawn multiple sub-agents simultaneously:

```bash

# Spawn 3 parallel tasks

JOB1=$(python3 spawn_sub_agent.py gemini "Create UserProfile migration...")

JOB2=$(python3 spawn_sub_agent.py gemini "Create UserProfile model...")

JOB3=$(python3 spawn_sub_agent.py gemini "Create UserProfileFactory...")


echo "Spawned jobs: $JOB1, $JOB2, $JOB3"


# Primary agent continues with other work while sub-agents execute

# Check results later: cat subagent_runs/$JOB1/report.md

```

### FunLynk-Specific Prompt Patterns

#### Pattern 1: Creating Migrations

```

Read the following files:

- context-engine/epics/E0X_Name/database-schema.md

- context-engine/domain-contexts/database-context.md

- database/migrations/2024_01_01_000001_create_users_table.php (example)


Create a Laravel 12 migration for the {table_name} table.


Requirements:

- Migration name: create_{table_name}_table

- Columns: {list all columns with types}

- Indexes: {specify indexes}

- Foreign keys: {specify relationships}

- Use PostGIS geography type for location columns

- Follow the pattern from the example migration


Output: Complete migration file content with up() and down() methods

```

#### Pattern 2: Creating Service Classes

```

Read the following files:

- context-engine/epics/E0X_Name/epic-overview.md

- context-engine/tasks/E0X_Name/F0X_Feature/README.md

- context-engine/domain-contexts/service-architecture.md

- app/Models/{RelatedModel}.php


Create a {ServiceName} class in app/Services/.


Requirements:

- Implement business logic for {specific feature}

- Methods: {list methods with signatures}

- Use dependency injection for repositories/models

- Follow Laravel 12 conventions

- Include proper error handling

- Add PHPDoc blocks


Output: Complete PHP service class

```

#### Pattern 3: Creating Livewire Components

```

Read the following files:

- context-engine/domain-contexts/ui-design-standards.md

- resources/views/welcome.blade.php (galaxy theme example)

- resources/views/livewire/auth/login.blade.php (form example)

- context-engine/tasks/E0X_Name/F0X_Feature/README.md


Create a Livewire v3 component for {feature description}.


Requirements:

- Component name: {ComponentName}

- Location: app/Livewire/{ComponentName}.php

- View: resources/views/livewire/{component-name}.blade.php

- Must use galaxy theme with glass morphism

- Include: {specific UI elements}

- Follow DaisyUI classes for components

- Use gradient buttons (pink-500 to purple-500)

- Forms must have cyan focus glow


Output: Two files - PHP component class and Blade view

```

#### Pattern 4: Creating Tests

```

Read the following files:

- app/Services/{ServiceName}.php

- app/Models/{ModelName}.php

- context-engine/tasks/E0X_Name/F0X_Feature/README.md


Create Pest v4 tests for {ServiceName}.


Requirements:

- Test file: tests/Feature/{ServiceName}Test.php

- Use Pest v4 syntax (test() function, expect() assertions)

- Test cases: {list specific scenarios}

- Use factories for test data

- Include edge cases and error scenarios

- Follow AAA pattern (Arrange, Act, Assert)


Output: Complete Pest test file

```

### Decision Matrix: Delegate or Handle?

| Task Type | Delegate to Sub-Agent? | Reason |

|-----------|----------------------|---------|

| Creating migrations | ✅ Yes | Well-defined schema, boilerplate code |

| Creating models | ✅ Yes | Clear relationships, standard patterns |

| Creating service classes | ✅ Yes (with detailed specs) | Business logic can be fully specified |

| Creating Filament resources | ⚠️ Maybe | Requires UI decisions, but can delegate if specs are complete |

| Creating Livewire components | ⚠️ Maybe | UI requires judgment, but can delegate with detailed mockups |

| Creating tests | ✅ Yes | Clear requirements from implementation |

| Architectural decisions | ❌ No | Requires deep context and judgment |

| Debugging complex issues | ❌ No | Requires iteration and context |

| Refactoring across files | ❌ No | Requires understanding of dependencies |

| Reading documentation | ❌ No | Primary agent has better context retention |

| Code review | ❌ No | Requires judgment and project knowledge |

| Integration tasks | ❌ No | Requires understanding of how pieces fit |

### Best Practices

1. **Always Include File Paths**: Sub-agents need exact paths to read context
2. **Be Explicit About Conventions**: Specify Laravel 12, Filament v4, Pest v4 syntax
3. **Reference Examples**: Point to existing files that follow the pattern
4. **Define Output Format**: Specify exactly what format you expect
5. **Include All Context**: Don't assume sub-agent knows anything about the project
6. **Test Sub-Agent Output**: Always review and test before integrating
7. **Iterate if Needed**: Spawn new tasks with corrections rather than manual fixes
8. **Track Job IDs**: Keep a list of spawned jobs for later review

### Example: Full Feature Implementation

**Feature: E02/F01 Profile CRUD**

**Primary Agent Planning:**

```

1. Read context-engine/epics/E02_User_Profile_Management/epic-overview.md

2. Read context-engine/tasks/E02_User_Profile_Management/F01_Profile_CRUD/README.md

3. Identify 7 tasks (T01-T07)

4. Determine delegation strategy

```

**Delegation Strategy:**

- T01 (Migration): Delegate to sub-agent
- T02 (Model): Delegate to sub-agent
- T03 (Service): Delegate to sub-agent
- T04 (Filament Resource): Handle myself (UI decisions)
- T05 (Factory): Delegate to sub-agent
- T06 (Tests): Delegate to sub-agent
- T07 (Integration): Handle myself

**Execution:**

```bash

# Spawn parallel tasks

JOB1=$(python3 spawn_sub_agent.py gemini "Create UserProfile migration...")

JOB2=$(python3 spawn_sub_agent.py gemini "Create UserProfile model...")

JOB3=$(python3 spawn_sub_agent.py gemini "Create UserProfileService...")

JOB4=$(python3 spawn_sub_agent.py gemini "Create UserProfileFactory...")


# Primary agent works on Filament resource while sub-agents execute


# After 30-60 seconds, review outputs

cat subagent_runs/$JOB1/report.md  # Review migration

cat subagent_runs/$JOB2/report.md  # Review model

# ... integrate code, run tests, iterate if needed

```

### Troubleshooting

**Sub-agent output is incomplete:**

- Prompt may be too vague - add more specific requirements
- May need to break task into smaller pieces
- Check run.log for errors

**Sub-agent doesn't follow conventions:**

- Explicitly specify Laravel 12 / Filament v4 syntax
- Reference example files that follow the pattern
- Include links to documentation

**Sub-agent can't find files:**

- Verify file paths are correct and absolute
- Ensure files exist before delegating
- Use exact paths from project root

**Output doesn't match expectations:**

- Review prompt for ambiguity
- Add more explicit output format specification
- Include examples of expected output

## When Lost

1. Check `context-engine/global-context.md` for big picture
2. Check epic `epic-overview.md` for module context
3. Check task `README.md` for specific implementation steps
4. Check `domain-contexts/` for cross-cutting patterns
5. Check E01 implementation for working examples
