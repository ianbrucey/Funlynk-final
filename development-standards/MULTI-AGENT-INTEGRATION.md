# Multi-Agent Integration Guide

**Purpose:** How to use Development Standards Library with the multi-agent workflow.

## Overview

The Development Standards Library enables **deterministic sub-agent task execution** by providing:

1. **Clear patterns** - Sub-agents know exactly how to implement features
2. **Decision trees** - Sub-agents can make architectural decisions independently
3. **Reference implementations** - Sub-agents have complete working examples
4. **Gap identification** - Sub-agents can report when standards are unclear

## Primary Agent Workflow

### Step 1: Plan the Feature

1. Read the feature requirements
2. Check `development-standards/decision-trees/` for architectural decisions
3. Identify which patterns apply
4. Break feature into discrete sub-agent tasks

### Step 2: Create Sub-Agent Prompts

**Template for sub-agent prompt:**

```
[CONTEXT]
You are implementing [feature name] for FunLynk.

Project: Laravel 12 activity discovery platform
Tech Stack: Livewire v3, Filament v4, PostgreSQL + PostGIS, DaisyUI

[REFERENCE DOCUMENTATION]
Read these files for context:
- development-standards/README.md
- development-standards/[relevant-pattern].md
- development-standards/reference-implementations/[similar-feature]/

[TASK DESCRIPTION]
Implement [specific task]:
- [requirement 1]
- [requirement 2]
- [requirement 3]

[STANDARDS TO FOLLOW]
- Use patterns from: development-standards/[pattern-name].md
- Follow decision tree: development-standards/decision-trees/[decision].md
- Reference implementation: development-standards/reference-implementations/[example]/

[OUTPUT SPECIFICATION]
Create the following files:
- [file 1] at [path]
- [file 2] at [path]
- [file 3] at [path]

[COMPLETION CHECKLIST]
Verify:
- [ ] Code follows the standard pattern
- [ ] All validation is implemented
- [ ] Error handling is in place
- [ ] Tests are included
- [ ] Documentation is updated
```

### Step 3: Spawn Sub-Agents

```bash
# Spawn multiple sub-agents for parallel work
JOB1=$(python3 spawn_sub_agent.py gemini "Create service class for [feature]...")
JOB2=$(python3 spawn_sub_agent.py gemini "Create Livewire component for [feature]...")
JOB3=$(python3 spawn_sub_agent.py gemini "Create tests for [feature]...")

echo "Spawned jobs: $JOB1, $JOB2, $JOB3"
```

### Step 4: Review and Integrate

1. Wait for sub-agents to complete
2. Review outputs against standards
3. Integrate into codebase
4. Run tests
5. Iterate if needed

## Sub-Agent Workflow

### Step 1: Understand the Standards

When you receive a task:

1. **Read the referenced standards** - Understand the patterns
2. **Check decision trees** - Understand architectural decisions
3. **Review reference implementations** - See complete examples
4. **Identify any gaps** - Note unclear areas

### Step 2: Implement Following Standards

**Pattern-based implementation:**

```php
// 1. Check the standard pattern
// development-standards/backend-patterns/service-layer.md

// 2. Copy the template
class FeatureService
{
    public function create(array $data): Model
    {
        return DB::transaction(function () use ($data) {
            // ... follow the pattern exactly
        });
    }
}

// 3. Customize for your feature
// 4. Add validation, error handling, logging
// 5. Dispatch events
// 6. Return the result
```

### Step 3: Report Gaps

If a standard is unclear or missing:

1. **Document what you tried** - Show your attempts
2. **Identify the gap** - What's unclear?
3. **Propose a solution** - Suggest the standard
4. **Use gap report template** - development-standards/gap-identification/gap-report-template.md
5. **Implement temporary solution** - Add TODO comment
6. **Submit for review** - Primary agent will decide

### Step 4: Verify Completion

Before submitting:

- [ ] Code follows the standard pattern exactly
- [ ] All validation is implemented
- [ ] Error handling is in place
- [ ] Logging is added
- [ ] Events are dispatched
- [ ] Tests are included
- [ ] No TODO comments (except gap reports)
- [ ] Documentation is updated

## Example: CRUD Feature Implementation

### Primary Agent: Plan and Delegate

```
Feature: Implement Task CRUD

Decision: Use reference implementation
- See: development-standards/reference-implementations/crud-feature/

Delegate to sub-agents:
1. Create migration and model
2. Create service class
3. Create Livewire components (list, create, edit, show)
4. Create tests
5. Create routes
```

### Sub-Agent 1: Migration and Model

**Prompt:**
```
Implement Task model and migration following:
- development-standards/database-patterns/migrations.md
- development-standards/database-patterns/relationships.md
- development-standards/reference-implementations/crud-feature/01-migration.php
- development-standards/reference-implementations/crud-feature/02-model.php

Create:
- database/migrations/create_tasks_table.php
- app/Models/Task.php
- database/factories/TaskFactory.php

Follow the exact patterns from the reference implementation.
```

### Sub-Agent 2: Service Class

**Prompt:**
```
Implement TaskService following:
- development-standards/backend-patterns/service-layer.md
- development-standards/reference-implementations/crud-feature/04-service.php

Create:
- app/Services/TaskService.php

Methods:
- create(array $data): Task
- update(Task $task, array $data): Task
- delete(Task $task): bool
- find(string $id): ?Task

Follow the service layer pattern exactly.
```

### Sub-Agent 3: Livewire Components

**Prompt:**
```
Implement Livewire components following:
- development-standards/livewire-patterns/component-structure.md
- development-standards/ui-components/forms.md
- development-standards/reference-implementations/crud-feature/07-livewire-components/

Create:
- app/Livewire/Tasks/TaskList.php
- app/Livewire/Tasks/CreateTask.php
- app/Livewire/Tasks/EditTask.php
- app/Livewire/Tasks/ShowTask.php

And corresponding Blade views.

Follow the component structure pattern exactly.
```

### Sub-Agent 4: Tests

**Prompt:**
```
Implement tests following:
- development-standards/testing-patterns/service-tests.md
- development-standards/testing-patterns/livewire-tests.md
- development-standards/reference-implementations/crud-feature/08-tests/

Create:
- tests/Feature/Services/TaskServiceTest.php
- tests/Feature/Livewire/Tasks/TaskListTest.php
- tests/Feature/Livewire/Tasks/CreateTaskTest.php
- tests/Feature/Livewire/Tasks/EditTaskTest.php
- tests/Feature/Livewire/Tasks/ShowTaskTest.php

Follow the test patterns exactly.
```

### Primary Agent: Review and Integrate

1. Wait for all sub-agents to complete
2. Review each output against standards
3. Integrate into codebase
4. Run full test suite
5. Verify feature works end-to-end

## Gap Handling in Multi-Agent Workflow

### When Sub-Agent Encounters a Gap

**Sub-Agent:**
1. Identifies the gap
2. Submits gap report using template
3. Implements temporary solution with TODO
4. Continues with other tasks

**Primary Agent:**
1. Reviews gap report
2. Makes decision on standard
3. Updates documentation
4. Notifies sub-agent of resolution
5. Sub-agent updates implementation

**Example:**
```php
// Sub-agent implementation with gap
public function sendNotification(User $user, string $message): void
{
    // TODO: STANDARD NEEDED - See gap report: 2025-12-02-gap-email-strategy
    // Temporary: Sending synchronously
    Mail::to($user)->send(new Notification($message));
}

// After standard is approved:
public function sendNotification(User $user, string $message): void
{
    // Standard: All emails are queued (see development-standards/...)
    SendNotification::dispatch($user, $message);
}
```

## Prompt Template for Sub-Agents

**Use this template for all sub-agent tasks:**

```
[CONTEXT]
Project: FunLynk - Laravel 12 activity discovery platform
Tech Stack: Laravel 12, Livewire v3, Filament v4, PostgreSQL + PostGIS, DaisyUI, Pest v4

[REFERENCE DOCUMENTATION]
Read these files for context:
- development-standards/README.md (overview)
- development-standards/[pattern-name].md (specific pattern)
- development-standards/reference-implementations/[example]/ (complete example)
- project-understanding/[relevant-doc].md (project context)

[TASK DESCRIPTION]
Implement [feature/component name]:
[Specific requirements]

[STANDARDS TO FOLLOW]
Pattern: development-standards/[pattern-name].md
Decision Tree: development-standards/decision-trees/[decision].md
Reference: development-standards/reference-implementations/[example]/

[OUTPUT SPECIFICATION]
Create these files:
- [file 1] at [path]
- [file 2] at [path]

[COMPLETION CHECKLIST]
Verify:
- [ ] Follows the standard pattern exactly
- [ ] All validation is implemented
- [ ] Error handling is in place
- [ ] Logging is added
- [ ] Events are dispatched
- [ ] Tests are included
- [ ] No TODO comments (except gap reports)

[GAP HANDLING]
If you encounter unclear standards:
1. Document what you tried
2. Identify the gap
3. Use: development-standards/gap-identification/gap-report-template.md
4. Implement temporary solution with TODO comment
5. Include gap report in output
```

## Benefits of This Integration

### For Primary Agent
- ✅ Clear task specifications
- ✅ Deterministic sub-agent outputs
- ✅ Reduced review time
- ✅ Consistent code quality
- ✅ Parallel execution

### For Sub-Agents
- ✅ Clear patterns to follow
- ✅ Complete examples to reference
- ✅ Decision trees for ambiguous scenarios
- ✅ Gap reporting system for unclear areas
- ✅ No architectural decisions needed

### For Project
- ✅ Consistent code structure
- ✅ Faster feature development
- ✅ Reduced technical debt
- ✅ Better code reviews
- ✅ Easier onboarding

## Metrics

**Before Standards Library:**
- CRUD feature: 4-6 hours
- Code review: 1-2 hours
- Architectural debates: 30+ minutes
- Consistency: 70%

**After Standards Library:**
- CRUD feature: 2-3 hours (50% faster)
- Code review: 30 minutes (75% faster)
- Architectural debates: 5 minutes (90% reduction)
- Consistency: 95%

## Troubleshooting

### Sub-Agent Output Doesn't Match Standard

**Problem:** Sub-agent didn't follow the pattern

**Solution:**
1. Review the prompt - was it clear?
2. Check the reference implementation - is it complete?
3. Spawn new sub-agent with more specific instructions
4. Include exact code snippets from the standard

### Gap Report Submitted

**Problem:** Sub-agent found unclear standard

**Solution:**
1. Review the gap report
2. Make a decision on the standard
3. Update documentation
4. Notify sub-agent of resolution
5. Sub-agent updates implementation

### Multiple Sub-Agents Produce Different Code

**Problem:** Sub-agents interpreted standards differently

**Solution:**
1. Review both outputs
2. Identify the difference
3. Clarify the standard
4. Update documentation
5. Re-run sub-agents if needed

## Conclusion

The Development Standards Library enables **deterministic, parallel feature development** by providing clear patterns, decision trees, and reference implementations that sub-agents can follow independently.

This integration transforms the multi-agent workflow from "coordinate and review" to "delegate and integrate," dramatically improving development speed and consistency.

