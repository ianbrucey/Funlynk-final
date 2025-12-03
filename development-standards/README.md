# Development Standards Library

**Purpose:** Deterministic, copy-paste-ready patterns for implementing features in FunLynk.

**Last Updated:** 2025-12-02

## What is This?

This library provides **THE ONE WAY** to implement features in FunLynk. It eliminates guesswork by providing:

- ✅ **Complete code templates** - Copy, customize, done
- ✅ **Decision trees** - Clear answers to "should I...?" questions
- ✅ **Behavioral standards** - How features should behave
- ✅ **Reference implementations** - Working examples of complete features
- ✅ **Gap identification** - How to report when standards are unclear

## Core Principle

> **"Don't think, follow the pattern."**

If you're making architectural decisions while implementing a feature, you're doing it wrong. This library should provide the answer.

## How to Use This Library

### For Implementing Features

**Step 1: Identify the Feature Type**
- CRUD feature? → See `reference-implementations/crud-feature/`
- Form? → See `ui-components/forms.md`
- Real-time feature? → See `reference-implementations/real-time-feature/`
- Background processing? → See `backend-patterns/background-jobs.md`

**Step 2: Copy the Template**
- Find the relevant pattern document
- Copy the complete code template
- Customize with your feature-specific details

**Step 3: Follow the Checklist**
- Each pattern includes a completion checklist
- Verify all steps are complete
- Run tests to validate

### For Making Decisions

**When you ask "Should I...?"**
1. Check `decision-trees/` for your scenario
2. Follow the flowchart to get a deterministic answer
3. If no decision tree exists, report a gap (see below)

### For Reporting Gaps

**When standards are unclear:**
1. Use `gap-identification/gap-report-template.md`
2. Document what you tried and why it's ambiguous
3. Implement a reasonable temporary solution with `// TODO: STANDARD NEEDED` comment
4. Submit gap report for review

## Library Structure

```
development-standards/
├── README.md                          # This file
│
├── ui-components/                     # UI component patterns
│   ├── buttons.md                     # Button variants (primary, secondary, icon)
│   ├── forms.md                       # Form inputs, validation, error states
│   ├── cards.md                       # Card layouts and styles
│   ├── modals.md                      # Modal patterns
│   ├── navigation.md                  # Navigation components
│   └── loading-states.md              # Spinners, skeletons, optimistic UI
│
├── livewire-patterns/                 # Livewire component patterns
│   ├── component-structure.md         # Standard component template
│   ├── form-components.md             # Form handling patterns
│   ├── real-time-components.md        # WebSocket integration
│   ├── validation-patterns.md         # Validation approaches
│   └── event-handling.md              # Component events and listeners
│
├── backend-patterns/                  # Backend code patterns
│   ├── service-layer.md               # Service class templates
│   ├── event-driven.md                # Event/listener patterns
│   ├── authorization.md               # Policy patterns
│   ├── background-jobs.md             # Job queue patterns
│   └── error-handling.md              # Exception handling
│
├── database-patterns/                 # Database patterns
│   ├── migrations.md                  # Migration templates
│   ├── relationships.md               # Eloquent relationship patterns
│   ├── spatial-queries.md             # PostGIS query patterns
│   └── indexes.md                     # Index strategies
│
├── behavioral-standards/              # How features should behave
│   ├── notifications.md               # Notification system rules
│   ├── error-handling.md              # User-facing error messages
│   ├── loading-states.md              # When to show loading UI
│   ├── real-time-updates.md           # WebSocket event patterns
│   └── authorization-ux.md            # How to handle unauthorized access
│
├── decision-trees/                    # Decision flowcharts
│   ├── component-architecture.md      # New component vs existing?
│   ├── logic-placement.md             # Where does business logic go?
│   ├── async-vs-sync.md               # Job queue vs synchronous?
│   ├── websocket-vs-polling.md        # Real-time strategy?
│   └── migration-strategy.md          # New migration vs modify existing?
│
├── reference-implementations/         # Complete working examples
│   ├── crud-feature/                  # Full CRUD implementation
│   ├── form-with-validation/          # Form example
│   ├── real-time-feature/             # WebSocket example
│   ├── search-feature/                # Meilisearch integration
│   └── payment-flow/                  # Stripe integration
│
├── testing-patterns/                  # Testing standards
│   ├── service-tests.md               # Service class test patterns
│   ├── livewire-tests.md              # Livewire component tests
│   ├── feature-tests.md               # Feature test patterns
│   └── factory-patterns.md            # Factory templates
│
└── gap-identification/                # Gap reporting
    ├── gap-report-template.md         # How to report unclear standards
    └── escalation-guide.md            # When to ask for help
```

## Quick Reference

### Common Tasks

| Task | Reference Document |
|------|-------------------|
| Create a new form | `ui-components/forms.md` |
| Add a button | `ui-components/buttons.md` |
| Create Livewire component | `livewire-patterns/component-structure.md` |
| Add validation | `livewire-patterns/validation-patterns.md` |
| Create service class | `backend-patterns/service-layer.md` |
| Add database table | `database-patterns/migrations.md` |
| Show notification | `behavioral-standards/notifications.md` |
| Handle errors | `behavioral-standards/error-handling.md` |
| Add loading state | `behavioral-standards/loading-states.md` |
| Create background job | `backend-patterns/background-jobs.md` |
| Add authorization | `backend-patterns/authorization.md` |
| Write tests | `testing-patterns/` (by type) |

### Common Decisions

| Question | Decision Tree |
|----------|--------------|
| Should I create a new component? | `decision-trees/component-architecture.md` |
| Where should this logic go? | `decision-trees/logic-placement.md` |
| Should I use a job queue? | `decision-trees/async-vs-sync.md` |
| Should I use WebSockets? | `decision-trees/websocket-vs-polling.md` |
| Should I modify an existing migration? | `decision-trees/migration-strategy.md` |

## Key Principles

### 1. Deterministic over Flexible
❌ "You can do it this way or that way"  
✅ "Do it THIS way"

### 2. Complete over Minimal
❌ "Here's a snippet..."  
✅ "Here's the complete implementation"

### 3. Tested over Theoretical
❌ "This should work..."  
✅ "This is proven to work in FunLynk"

### 4. Specific over Generic
❌ "Follow Laravel conventions"  
✅ "Use this exact Laravel 12 + Livewire v3 pattern"

### 5. Gap-Aware over Comprehensive
❌ Pretend we have standards for everything  
✅ Explicitly identify what's missing

## Success Metrics

This library is successful if:

1. ✅ An agent can implement a CRUD feature without making architectural decisions
2. ✅ All forms look and behave consistently across the application
3. ✅ Notifications appear in the same place with the same styling everywhere
4. ✅ Agents know when to ask for clarification (gap identification works)
5. ✅ Code reviews focus on business logic, not "why did you structure it this way?"

## Relationship to Other Documentation

```
project-understanding/          # DESCRIPTIVE: What exists
    ↓ transforms into
development-standards/          # PRESCRIPTIVE: What to build
    ↓ used by
context-engine/tasks/           # IMPLEMENTATION: Specific features
```

**Key difference:**
- `project-understanding/` = "Here's how the project works"
- `development-standards/` = "Here's how to build features"
- `context-engine/tasks/` = "Here's what to build"

## Contributing to This Library

When you implement a feature and realize a standard is missing:

1. **Document the pattern you used** - Write it down while it's fresh
2. **Submit a gap report** - Use `gap-identification/gap-report-template.md`
3. **Propose the standard** - Include your implementation as the reference
4. **Get approval** - Primary agent reviews and approves
5. **Add to library** - Pattern becomes the official standard

## Next Steps

Start with the most common tasks:
1. Read `ui-components/forms.md` - Forms are everywhere
2. Read `livewire-patterns/component-structure.md` - Most features use Livewire
3. Read `behavioral-standards/notifications.md` - Consistent user feedback
4. Skim `decision-trees/` - Know what decisions are covered
5. Bookmark `reference-implementations/` - Complete examples when you need them

