# Project Understanding Folder - Summary

**Created:** 2025-12-02  
**Purpose:** Reference template for building understanding folders for any codebase

## What We Built

A **comprehensive reference documentation system** that captures everything needed to understand a codebase, organized into 8 core documents:

1. **00-project-identity.md** - What is this project? (tech stack, structure, concepts)
2. **01-architecture-overview.md** - How is it structured? (layers, data flow, integrations)
3. **02-database-schema.md** - What's in the database? (tables, relationships, indexes)
4. **03-api-contracts.md** - What are the APIs? (routes, endpoints, WebSocket events)
5. **04-ui-patterns.md** - How does the UI work? (design system, components, patterns)
6. **05-backend-patterns.md** - How is backend code organized? (services, models, events)
7. **06-testing-conventions.md** - How is testing done? (framework, patterns, factories)
8. **07-deployment-infrastructure.md** - How does it run? (setup, deployment, monitoring)

## Why This Matters

### Problem Statement
When a primary agent (or developer) encounters a new codebase, they need to:
1. Understand the project's purpose and tech stack
2. Learn architectural patterns and conventions
3. Know where to find things and how they're organized
4. Reference standards when building new features

**Current approach:** Read code, ask questions, trial and error  
**New approach:** Systematic investigation → structured documentation → reusable reference

### Solution Benefits

**For Primary Agents:**
- Reduces cognitive load (don't need to remember everything)
- Provides structured context for sub-agent prompts
- Enables consistent delegation (reference standards in prompts)

**For Sub-Agents:**
- Complete, self-contained context in every prompt
- Clear patterns to follow (not guessing conventions)
- Reduces hallucination (concrete examples to reference)

**For Development Teams:**
- Onboarding documentation for new developers
- Living documentation that evolves with the project
- Shared understanding of patterns and conventions

## How It Was Created

### Investigation Process (4 hours)

**Phase 1: Project Identity** (30 min)
- Examined: `composer.json`, `package.json`, root directory
- Extracted: Tech stack, dependencies, project structure
- Output: `00-project-identity.md`

**Phase 2: Architecture** (45 min)
- Examined: `app/Models/`, `app/Services/`, `app/Events/`
- Extracted: System layers, data flow, integration points
- Output: `01-architecture-overview.md`

**Phase 3: Database Schema** (30 min)
- Examined: `database/migrations/`, model relationships
- Extracted: Tables, columns, indexes, relationships
- Output: `02-database-schema.md`

**Phase 4: API Contracts** (30 min)
- Examined: `routes/`, Livewire components
- Extracted: Routes, endpoints, WebSocket events
- Output: `03-api-contracts.md`

**Phase 5: UI Patterns** (45 min)
- Examined: Blade templates, CSS, Livewire components
- Extracted: Design system, component patterns
- Output: `04-ui-patterns.md`

**Phase 6: Backend Patterns** (30 min)
- Examined: Services, models, events, policies
- Extracted: Service layer, validation, authorization
- Output: `05-backend-patterns.md`

**Phase 7: Testing** (30 min)
- Examined: Test files, factories, Pest config
- Extracted: Test structure, patterns, conventions
- Output: `06-testing-conventions.md`

**Phase 8: Deployment** (30 min)
- Examined: `.env.example`, config files, scripts
- Extracted: Setup, deployment, infrastructure
- Output: `07-deployment-infrastructure.md`

### Key Insights

1. **Sequential Investigation** - Each phase builds on previous understanding
2. **File Selection is Critical** - Knowing which files to examine is 80% of the work
3. **Pattern Recognition** - Looking for patterns, not exhaustive documentation
4. **Context Matters** - Domain knowledge helps interpret code

## What's Next

### Immediate Next Steps

1. **Design Investigation Protocols** - Formalize the investigation process
   - Create protocol templates for each phase
   - Define file discovery heuristics
   - Establish pattern extraction guidelines

2. **Build Sub-Agent Prompts** - Create standardized prompts
   - "Investigate Project Identity" prompt template
   - "Analyze Architecture" prompt template
   - "Extract Database Schema" prompt template
   - etc.

3. **Test on New Projects** - Validate the approach
   - Apply protocols to different tech stacks (Next.js, Django, etc.)
   - Refine based on what works/doesn't work
   - Build a library of protocol variations

### Long-Term Vision

**Goal:** Automated project understanding generation

**Workflow:**
```
New Project
    ↓
Primary Agent: "Investigate this codebase"
    ↓
Spawn 8 Sub-Agents (parallel investigation)
    ↓
Each sub-agent follows investigation protocol
    ↓
Generate 8 understanding documents
    ↓
Primary Agent: Review and synthesize
    ↓
Complete project-understanding/ folder
    ↓
Ready for feature development
```

**Time Savings:**
- Manual: 4-6 hours (primary agent)
- Automated: 30-60 minutes (8 parallel sub-agents + review)

## How to Use This Reference

### For Building Investigation Protocols

1. **Study the investigation workflow** (`INVESTIGATION-WORKFLOW.md`)
2. **Identify the file discovery patterns** (which files were examined in each phase)
3. **Extract the key questions** (what information was sought)
4. **Formalize the process** (create step-by-step protocols)

### For Creating Understanding Folders

1. **Use as a template** - Copy the 8-document structure
2. **Follow the investigation phases** - Sequential discovery process
3. **Adapt to project specifics** - Different tech stacks need different focus
4. **Keep it pattern-focused** - Don't try to document everything

### For Sub-Agent Prompts

**Example prompt structure:**
```
[CONTEXT]
You are investigating a [tech stack] project to understand [aspect].

[INVESTIGATION PROTOCOL]
1. Examine these files: [file list]
2. Extract: [specific information]
3. Answer these questions: [question list]

[OUTPUT FORMAT]
Create a markdown document with these sections:
- [section 1]
- [section 2]
- [section 3]

[REFERENCE]
Follow the pattern from: project-understanding/0X-document-name.md
```

## Files in This Folder

```
project-understanding/
├── README.md                           # Overview and usage guide
├── SUMMARY.md                          # This file
├── INVESTIGATION-WORKFLOW.md           # How this was created
├── 00-project-identity.md              # Tech stack, structure, concepts
├── 01-architecture-overview.md         # System layers, data flow
├── 02-database-schema.md               # Tables, relationships
├── 03-api-contracts.md                 # Routes, endpoints, events
├── 04-ui-patterns.md                   # Design system, components
├── 05-backend-patterns.md              # Services, models, conventions
├── 06-testing-conventions.md           # Test structure, patterns
└── 07-deployment-infrastructure.md     # Setup, deployment, monitoring
```

## Success Metrics

**This reference is successful if:**

1. ✅ A new developer can understand the project in 1-2 hours (vs. 1-2 days)
2. ✅ Sub-agents can generate consistent, high-quality code by referencing these docs
3. ✅ The investigation process can be replicated for other projects
4. ✅ The documentation stays current as the project evolves

## Related Documentation

- **Multi-Agent Workflow:** `.augment/rules/AGENTS.md`
- **Project-Specific Context:** `context-engine/global-context.md`
- **Feature Documentation:** `context-engine/epics/` and `context-engine/tasks/`
- **Technical Standards:** `context-engine/domain-contexts/`

## Feedback & Iteration

This is a **living reference** that should evolve based on:
- What works well in practice
- What's missing or unclear
- What's redundant or unnecessary
- How different tech stacks require different approaches

**Next iteration should address:**
- Protocol templates for each investigation phase
- Sub-agent prompt templates
- Validation on non-Laravel projects
- Automation opportunities

