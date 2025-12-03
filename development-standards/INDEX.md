# Development Standards Library - Complete Index

**Purpose:** Comprehensive reference for all development standards in FunLynk.

## Quick Navigation

### 🚀 Getting Started
- **New to this library?** → Start with `QUICK-START.md`
- **Want the full picture?** → Read `README.md`
- **Need a decision?** → Check `decision-trees/`
- **Building a feature?** → See `reference-implementations/`

### 📋 By Task Type

#### UI/UX Tasks
| Task | Document |
|------|----------|
| Create a form | `ui-components/forms.md` |
| Add a button | `ui-components/buttons.md` |
| Create a card | `ui-components/cards.md` |
| Create a modal | `ui-components/modals.md` |
| Add navigation | `ui-components/navigation.md` |
| Show loading state | `ui-components/loading-states.md` |
| Show notification | `behavioral-standards/notifications.md` |
| Handle errors | `behavioral-standards/error-handling.md` |

#### Livewire Tasks
| Task | Document |
|------|----------|
| Create a component | `livewire-patterns/component-structure.md` |
| Build a form | `livewire-patterns/form-components.md` |
| Add real-time updates | `livewire-patterns/real-time-components.md` |
| Add validation | `livewire-patterns/validation-patterns.md` |
| Handle events | `livewire-patterns/event-handling.md` |

#### Backend Tasks
| Task | Document |
|------|----------|
| Create a service | `backend-patterns/service-layer.md` |
| Create a model | `database-patterns/relationships.md` |
| Add authorization | `backend-patterns/authorization.md` |
| Create a job | `backend-patterns/background-jobs.md` |
| Handle errors | `backend-patterns/error-handling.md` |
| Create an event | `backend-patterns/event-driven.md` |

#### Database Tasks
| Task | Document |
|------|----------|
| Create a migration | `database-patterns/migrations.md` |
| Define relationships | `database-patterns/relationships.md` |
| Query with PostGIS | `database-patterns/spatial-queries.md` |
| Add indexes | `database-patterns/indexes.md` |

#### Testing Tasks
| Task | Document |
|------|----------|
| Test a service | `testing-patterns/service-tests.md` |
| Test a component | `testing-patterns/livewire-tests.md` |
| Test a feature | `testing-patterns/feature-tests.md` |
| Create a factory | `testing-patterns/factory-patterns.md` |

### 🤔 By Decision

#### Architectural Decisions
| Question | Document |
|----------|----------|
| Should I create a new component? | `decision-trees/component-architecture.md` |
| Where should this logic go? | `decision-trees/logic-placement.md` |
| Should I use a job queue? | `decision-trees/async-vs-sync.md` |
| Should I use WebSockets? | `decision-trees/websocket-vs-polling.md` |
| Should I modify a migration? | `decision-trees/migration-strategy.md` |

#### Behavioral Decisions
| Question | Document |
|----------|----------|
| When should I show a notification? | `behavioral-standards/notifications.md` |
| How should I handle errors? | `behavioral-standards/error-handling.md` |
| When should I show loading? | `behavioral-standards/loading-states.md` |
| How should I do real-time updates? | `behavioral-standards/real-time-updates.md` |
| How should I handle authorization? | `behavioral-standards/authorization-ux.md` |

### 📚 By Feature Type

#### Complete Features
| Feature | Document |
|---------|----------|
| CRUD (Create/Read/Update/Delete) | `reference-implementations/crud-feature/` |
| Form with validation | `reference-implementations/form-with-validation/` |
| Real-time updates | `reference-implementations/real-time-feature/` |
| Search functionality | `reference-implementations/search-feature/` |
| Payment processing | `reference-implementations/payment-flow/` |

## Document Structure

```
development-standards/
│
├── README.md                          # Overview and principles
├── QUICK-START.md                     # 5-minute onboarding
├── INDEX.md                           # This file
│
├── ui-components/                     # UI component patterns
│   ├── buttons.md                     # Button variants
│   ├── forms.md                       # Form patterns
│   ├── cards.md                       # Card layouts
│   ├── modals.md                      # Modal patterns
│   ├── navigation.md                  # Navigation components
│   └── loading-states.md              # Loading UI patterns
│
├── livewire-patterns/                 # Livewire patterns
│   ├── component-structure.md         # Standard component template
│   ├── form-components.md             # Form handling
│   ├── real-time-components.md        # WebSocket integration
│   ├── validation-patterns.md         # Validation approaches
│   └── event-handling.md              # Event listeners
│
├── backend-patterns/                  # Backend patterns
│   ├── service-layer.md               # Service class template
│   ├── event-driven.md                # Event/listener patterns
│   ├── authorization.md               # Policy patterns
│   ├── background-jobs.md             # Job queue patterns
│   └── error-handling.md              # Exception handling
│
├── database-patterns/                 # Database patterns
│   ├── migrations.md                  # Migration templates
│   ├── relationships.md               # Eloquent relationships
│   ├── spatial-queries.md             # PostGIS queries
│   └── indexes.md                     # Index strategies
│
├── behavioral-standards/              # How features behave
│   ├── notifications.md               # Notification system
│   ├── error-handling.md              # Error messages
│   ├── loading-states.md              # Loading UI
│   ├── real-time-updates.md           # WebSocket events
│   └── authorization-ux.md            # Permission handling
│
├── decision-trees/                    # Decision flowcharts
│   ├── component-architecture.md      # New component?
│   ├── logic-placement.md             # Where does logic go?
│   ├── async-vs-sync.md               # Job queue?
│   ├── websocket-vs-polling.md        # Real-time strategy?
│   └── migration-strategy.md          # New migration?
│
├── reference-implementations/         # Complete examples
│   ├── crud-feature/                  # Full CRUD example
│   ├── form-with-validation/          # Form example
│   ├── real-time-feature/             # WebSocket example
│   ├── search-feature/                # Meilisearch example
│   └── payment-flow/                  # Stripe example
│
├── testing-patterns/                  # Testing standards
│   ├── service-tests.md               # Service tests
│   ├── livewire-tests.md              # Component tests
│   ├── feature-tests.md               # Feature tests
│   └── factory-patterns.md            # Factory templates
│
└── gap-identification/                # Gap reporting
    ├── gap-report-template.md         # How to report gaps
    ├── escalation-guide.md            # When to escalate
    └── reports/                       # Resolved gap reports
```

## How to Use This Index

### Method 1: By Task
1. Find your task in the "By Task Type" section
2. Open the referenced document
3. Copy the template
4. Customize for your feature

### Method 2: By Decision
1. Find your question in the "By Decision" section
2. Open the referenced document
3. Follow the decision tree
4. Implement the recommended approach

### Method 3: By Feature
1. Find your feature type in the "By Feature Type" section
2. Open the reference implementation
3. Follow the steps
4. Copy each file template

### Method 4: Search
Use Ctrl+F to search for keywords:
- "form" → Find form patterns
- "notification" → Find notification patterns
- "service" → Find service patterns
- "test" → Find testing patterns

## Key Principles

### 1. Deterministic
One recommended way to do things, not multiple options.

### 2. Complete
All code examples are complete and immediately usable.

### 3. Context-Aware
References galaxy theme, PostGIS, Livewire v3, Laravel 12.

### 4. Tested
Only patterns proven to work in FunLynk.

### 5. Gap-Aware
Explicitly identifies missing standards.

## Common Workflows

### Workflow 1: Implement a New CRUD Feature

1. Open: `reference-implementations/crud-feature/README.md`
2. Follow the 10 implementation steps
3. Copy each file template
4. Customize for your resource
5. Run tests
6. Done!

**Time:** 2-3 hours

### Workflow 2: Add a Form to Existing Feature

1. Open: `ui-components/forms.md`
2. Copy the "Standard Form Structure"
3. Customize fields and validation
4. Add to Livewire component
5. Done!

**Time:** 30 minutes

### Workflow 3: Implement Real-time Feature

1. Open: `decision-trees/websocket-vs-polling.md`
2. Decide on strategy (WebSocket vs polling)
3. Open: `reference-implementations/real-time-feature/`
4. Follow the implementation steps
5. Done!

**Time:** 2-4 hours

### Workflow 4: Report a Gap

1. Open: `gap-identification/gap-report-template.md`
2. Fill out the template
3. Submit for review
4. Implement temporary solution with TODO
5. Wait for standard approval
6. Update implementation

**Time:** 30 minutes (initial), varies (resolution)

## Success Metrics

You're using this library correctly if:

✅ You rarely make architectural decisions  
✅ You copy patterns and customize them  
✅ Your code looks similar to other code  
✅ You know where to find answers  
✅ You submit gap reports when needed  
✅ You don't debate "how should we do this?"

## Relationship to Other Documentation

```
project-understanding/          # DESCRIPTIVE: What exists
    ↓ transforms into
development-standards/          # PRESCRIPTIVE: What to build
    ↓ used by
context-engine/tasks/           # IMPLEMENTATION: Specific features
```

## Contributing

When you implement a feature and discover a missing standard:

1. Document the pattern you used
2. Submit a gap report
3. Propose the standard
4. Get approval
5. Add to library

## Feedback

This library is a living document. As you use it:

- Report gaps when standards are missing
- Suggest improvements when patterns don't work
- Share what works well
- Help refine standards based on real usage

## Quick Links

- **Start here:** `QUICK-START.md`
- **Full overview:** `README.md`
- **Make a decision:** `decision-trees/`
- **Copy a template:** `reference-implementations/`
- **Report a gap:** `gap-identification/gap-report-template.md`
- **Find a pattern:** Use Ctrl+F to search

---

**Last Updated:** 2025-12-02  
**Total Documents:** 30+  
**Total Patterns:** 100+  
**Coverage:** UI, Livewire, Backend, Database, Testing, Behavioral Standards

