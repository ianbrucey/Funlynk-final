# FunLynk

**Spontaneous, niche activity discovery — where ephemeral posts become real events.**

FunLynk is a Laravel 12 web application that connects people through shared interests and local activities. Users post spontaneous activity ideas that last 24–48 hours. When enough people react, a Post graduates into a structured Event — complete with RSVPs, location, and optional payments.

---

## Core Concept: Posts vs Events

The platform is built around a **dual model**:

| | Post | Event |
|---|---|---|
| **Lifespan** | 24–48 hours (ephemeral) | Persistent |
| **Radius** | 5–10 km | 25–50 km |
| **Engagement** | Reactions ("I'm down", "Join me") | RSVPs + Payments |
| **Purpose** | Spontaneous discovery | Structured activity |

**Conversion flow:** 5+ reactions → suggest conversion → 10+ reactions → auto-convert to Event (stored with `originated_from_post_id`).

---

## Tech Stack

| Layer | Technology |
|---|---|
| Backend | Laravel 12 |
| Admin UI | Filament v4 |
| Frontend | Livewire v3 + DaisyUI |
| Database | PostgreSQL + PostGIS |
| Testing | Pest v4 |

---

## Implementation Status

| Epic | Module | Status |
|---|---|---|
| E01 | Core Infrastructure (DB, Auth, Notifications) | ✅ Complete |
| E02 | User & Profile Management | 🔄 Ready |
| E03 | Activity Management + Post-to-Event Conversion | 🔄 Ready |
| E04 | Discovery Engine + Feeds | 🔄 Ready |
| E05 | Social Interaction (Comments, Communities) | ⏳ Planned |
| E06 | Payments & Monetization (Stripe Connect) | ⏳ Planned |
| E07 | Administration & Moderation | ⏳ Planned |

---

## Getting Started

```bash
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate
php artisan db:seed
php artisan serve
```

> **Requires:** PHP 8.2+, PostgreSQL with PostGIS extension, Composer

---

## For New Developers & Agents

The `context-engine/` directory is the single source of truth for all project knowledge. **Always read it before writing code.**

### Where to Start

```
context-engine/
├── global-context.md               ← Start here — universal project context
├── domain-contexts/                ← Deep-dive knowledge per feature area
│   ├── auth-context.md             ← Authentication & authorization patterns
│   ├── database-context.md         ← PostGIS, spatial queries, schema decisions
│   ├── ui-design-standards.md      ← Galaxy theme, glass morphism (CRITICAL for UI)
│   ├── api-context.md              ← API design patterns
│   └── post-social-interactions.md ← Posts, reactions, conversion logic
├── specs/                          ← Full feature specs (brief → schema → plan)
│   ├── guest-event-experience/     ← Guest access to public events
│   ├── event-edit-protection/      ← Edit lock rules for active events
│   └── group-member-experience/    ← Group membership flows
└── standards/                      ← Mandatory coding standards
    ├── 01-FRONTEND-STANDARDS/      ← UI/UX, component design rules
    ├── 02-BACKEND-STANDARDS/       ← Service architecture, DB patterns
    └── 03-CODE-QUALITY/            ← Testing, versioning, quality gates
```

### Domain Contexts

Read the relevant domain context **before** touching any feature area. Each file explains:
- **WHY** the business rules exist
- **WHERE** the key files are and how they relate
- **HOW** to trace through code for common modifications

| File | When to read it |
|---|---|
| `domain-contexts/ui-design-standards.md` | Before writing any Blade/Livewire UI |
| `domain-contexts/database-context.md` | Before writing migrations or spatial queries |
| `domain-contexts/auth-context.md` | Before touching auth, policies, or permissions |
| `domain-contexts/api-context.md` | Before adding or modifying API endpoints |
| `domain-contexts/post-social-interactions.md` | Before working on posts, reactions, or conversion |

### Feature Specs

Complex features are fully specced in `context-engine/specs/`. Each spec folder contains:

```
specs/<feature-name>/
├── 00-brief.md              # Problem statement & success criteria
├── 01-database-schema.md    # Tables, columns, relationships
├── 02-service-architecture.md
├── 03-ui-components.md
├── 04-implementation-plan.md
└── 05-fixtures.md           # Real data examples for testing
```

> **Rule:** No logic shall be written to handle data unless a fixtures file with real-world examples exists.

### Coding Standards

All code **must** comply with `context-engine/standards/` before being considered complete:

- **Frontend:** Galaxy theme (glass morphism, aurora effects, gradient buttons) — no plain HTML pages
- **Backend:** `casts()` method not `$casts` property (Laravel 12); `->components([])` not `->schema([])` (Filament v4)
- **Location:** All spatial queries use PostGIS geography columns via `matanyadaev/laravel-eloquent-spatial`
- **Testing:** Every feature requires Pest v4 tests — no task is done without a passing test verdict

---

## Development Protocol

This project follows a **Zero Ambiguity** state machine. Jumping phases causes broken integrations.

```
STATE 1: DISCOVERY    → 00-brief.md approved by stakeholder
STATE 2: ARCHITECTURE → specs generated (schema, API contract, fixtures)
STATE 3: PLANNING     → implementation plan written as atomic tickets
STATE 4: EXECUTION    → tickets coded, tested, verdict confirmed
```

**Never write code before the spec is approved. Never mark a ticket done without a green test.**

For the full agent protocol — including the 5 Commandments, sub-agent workflow, and multi-agent orchestration — see [`AGENTS.md`](./AGENTS.md).

---

## Key Commands

```bash
# Generate resources
php artisan make:filament-resource Name --generate --no-interaction
php artisan make:livewire Namespace/Component --no-interaction
php artisan make:test --pest Feature/TestName --no-interaction

# Run tests
php artisan test
php artisan test --filter=TestName

# Format code
vendor/bin/pint --dirty

# Multi-agent execution
python3 spawn_sub_agent.py gemini "YOUR DETAILED PROMPT"
python3 context-engine/scripts/executor.py --list
```

---

## UI Theme

Every page uses the **Galaxy theme** — dark gradient background, aurora layers, glass-morphism cards, and gradient buttons. Before building any UI component, read `context-engine/domain-contexts/ui-design-standards.md` and reference:

- `resources/views/welcome.blade.php` — full page example
- `resources/views/livewire/auth/login.blade.php` — form example
