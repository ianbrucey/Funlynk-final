# Agent Swarm Architecture for FunLynk

## Philosophy

**Primary Agent (Orchestrator)**: Strategic thinking, planning, coordination, context management
**Sub-Agents (Workers)**: Tactical execution, focused implementation, isolated tasks

## The Problem We're Solving

### Current Issues with Single-Agent Development:
1. **Cognitive Overload**: One agent handling everything from planning to implementation
2. **Context Window Exhaustion**: Long conversations eat up available context
3. **Credit Consumption**: Paid tier usage for both strategic and tactical work
4. **Lost Context**: Important details get buried in long conversation histories

### The Solution: Hierarchical Agent Swarm
- **Primary Agent**: Uses paid tier for complex reasoning, planning, and orchestration
- **Sub-Agents**: Use Gemini CLI free tier for focused, well-defined tasks
- **Result**: Better quality, lower cost, distributed cognitive load

## Core Principles

### 1. Stateless Sub-Agents
- Sub-agents have **NO memory** of previous conversations
- Each prompt must be **100% self-contained**
- Always provide **explicit file paths**, not references like "the file we discussed"
- Never assume sub-agents know project context

### 2. Context Injection Strategy

Sub-agents need **THREE types of context**:

#### A. Project Identity Context (Always Include)
```
Project: FunLynk - Laravel 12 activity discovery platform
Tech Stack: Laravel 12, Filament v4, Livewire v3, PostgreSQL + PostGIS, DaisyUI, Pest v4
Core Feature: Posts (ephemeral, 24-48h) convert to Events (persistent) based on engagement
```

#### B. Domain Context (When Relevant)
Point to specific files in `context-engine/domain-contexts/`:
- UI work? → Reference `ui-design-standards.md`
- Database work? → Reference `database-context.md`
- Auth work? → Reference `auth-context.md`
- Post-to-Event logic? → Reference epic overviews

#### C. Task-Specific Context
- **Exact file paths** to read (absolute paths)
- **Specific requirements** and constraints
- **Expected output format** and location
- **Reference implementations** to follow

### 3. The Golden Rule of Sub-Agent Prompts

**A sub-agent prompt should be readable by a developer who just joined the project and has never seen the codebase before.**

If they couldn't complete the task with just the prompt, it's not detailed enough.

## Common Delegation Patterns

### Pattern 1: Code Analysis & Reporting

**Use Case**: Need to understand how a system works before modifying it

**Primary Agent Role**: 
- Identify what needs analysis
- Define specific questions to answer
- Determine which files are relevant

**Sub-Agent Role**: 
- Read specified files
- Analyze code structure and logic
- Produce structured report

**Example Prompt**:
```
TASK: Analyze the Post Reaction System

PROJECT CONTEXT:
- FunLynk: Laravel 12 activity discovery platform
- Tech Stack: Laravel 12, Filament v4, Livewire v3, PostgreSQL + PostGIS
- Core Feature: Posts can receive reactions ("I'm down", "Invite friends")
- Business Logic: 5+ reactions suggest conversion, 10+ auto-convert to Event

FILES TO ANALYZE:
1. /Users/ianbruce/Herd/funlynk/app/Models/PostReaction.php
2. /Users/ianbruce/Herd/funlynk/app/Services/PostService.php
3. /Users/ianbruce/Herd/funlynk/database/migrations/2025_11_23_create_post_reactions_table.php
4. /Users/ianbruce/Herd/funlynk/app/Models/Post.php (focus on reaction-related methods)

ANALYSIS REQUIREMENTS:
1. List all reaction types supported (check enum or constants)
2. Describe the database schema for post_reactions table
3. Explain the business logic in PostService related to reactions
4. Identify how reaction counts are tracked
5. Find any integration points with notifications
6. Note any PostGIS spatial queries used
7. Identify the conversion threshold logic (5 reactions, 10 reactions)

OUTPUT FORMAT:
Write a markdown report to: /Users/ianbruce/Herd/funlynk/subagent_runs/post_reaction_analysis.md

Use this structure:
# Post Reaction System Analysis

## Reaction Types
- [List each type with description]

## Database Schema
```sql
[Show table structure]
```

## Business Logic
### Key Methods
- [Method name]: [Purpose and behavior]

### Reaction Counting
- [How counts are tracked and updated]

### Conversion Thresholds
- [5 reaction threshold behavior]
- [10 reaction threshold behavior]

## Integration Points
- [Notifications]
- [Other systems]

## Spatial Queries
- [Any PostGIS usage, or "None found"]

## Key Findings
- [Important observations]
- [Potential issues or improvements]
```

---

### Pattern 2: Code Generation (Service Classes)

**Use Case**: Need to create a new service class following project patterns

**Primary Agent Role**:
- Define service requirements
- Identify reference implementations
- Specify method signatures

**Sub-Agent Role**:
- Generate code following exact specifications
- Match coding style of reference files
- Include proper error handling and type hints

**Example Prompt**:
```
TASK: Create FlareService for Post/Activity Boosting

PROJECT CONTEXT:
- FunLynk: Laravel 12 activity discovery platform
- Tech Stack: Laravel 12, Filament v4, Livewire v3, PostgreSQL + PostGIS
- New Feature: "Flares" allow users to boost Posts or Activities for visibility

REFERENCE FILES (read these for patterns):
1. /Users/ianbruce/Herd/funlynk/app/Services/PostService.php
2. /Users/ianbruce/Herd/funlynk/app/Models/Flare.php
3. /Users/ianbruce/Herd/funlynk/app/Models/Post.php
4. /Users/ianbruce/Herd/funlynk/app/Models/Activity.php

REQUIREMENTS:
Create FlareService.php in /Users/ianbruce/Herd/funlynk/app/Services/

Implement these methods:

1. createFlare(User $user, string $type, ?Post $post = null, ?Activity $activity = null, float $amount, Carbon $expiresAt): Flare
   - Validate that either $post OR $activity is set (not both, not neither)
   - Validate $type is 'post_boost' or 'activity_boost'
   - Create flare record
   - Deduct credits from user balance
   - Use DB transaction for atomicity
   - Throw exception if insufficient credits

2. getActiveFlares(string $type, ?int $limit = 10): Collection
   - Return flares that haven't expired
   - Order by created_at DESC
   - Filter by type if provided

3. getUserFlares(User $user, ?int $limit = 10): Collection
   - Return user's flares
   - Include expired ones
   - Order by created_at DESC

4. expireFlare(Flare $flare): bool
   - Mark flare as expired
   - Return true on success

CODING STANDARDS:
- Use strict types (declare(strict_types=1);)
- PHP 8.2+ syntax
- Type hints for all parameters and return types
- DocBlocks for all methods
- Follow PSR-12 coding standards
- Use Laravel's DB::transaction() for atomic operations
- Throw descriptive exceptions (use existing Laravel exceptions where appropriate)
- Follow the same structure and style as PostService.php

OUTPUT:
Print the complete file contents to stdout so it can be saved to:
/Users/ianbruce/Herd/funlynk/app/Services/FlareService.php
```

---

### Pattern 3: Test Generation

**Use Case**: Need comprehensive Pest tests for a feature

**Primary Agent Role**:
- Identify what needs testing
- Define test scenarios (happy path, edge cases, failures)
- Specify assertions

**Sub-Agent Role**:
- Write Pest v4 tests following project patterns
- Include setup/teardown
- Cover all scenarios

**Example Prompt**:
```
TASK: Create Pest Tests for Activity RSVP System

PROJECT CONTEXT:
- FunLynk: Laravel 12 activity discovery platform
- Testing Framework: Pest v4
- Feature: Users can RSVP to activities with capacity limits

REFERENCE FILES:
1. /Users/ianbruce/Herd/funlynk/app/Models/Activity.php
2. /Users/ianbruce/Herd/funlynk/app/Models/Rsvp.php
3. /Users/ianbruce/Herd/funlynk/app/Services/RsvpService.php (if exists)
4. /Users/ianbruce/Herd/funlynk/tests/Feature/Feature/ActivityManagementTest.php (for patterns)
5. /Users/ianbruce/Herd/funlynk/database/factories/ActivityFactory.php
6. /Users/ianbruce/Herd/funlynk/database/factories/UserFactory.php

TEST SCENARIOS:

Happy Path:
1. User can RSVP to an activity
2. RSVP creates database record with correct data
3. Activity participant count increments
4. User receives confirmation

Edge Cases:
5. User cannot RSVP twice to same activity
6. RSVP respects capacity limits (reject when full)
7. User can cancel their RSVP
8. Canceling RSVP decrements participant count
9. Activity owner receives notification on new RSVP
10. RSVP to activity with unlimited capacity works

Failure Cases:
11. Cannot RSVP to past activity
12. Cannot RSVP to cancelled activity
13. Cannot cancel non-existent RSVP

REQUIREMENTS:
- Use Pest v4 syntax (test() and expect())
- Use factories for all test data
- Use RefreshDatabase trait
- Group related tests with describe() blocks
- Use beforeEach() for common setup
- Test both success and failure cases
- Assert database state changes
- Assert notification dispatch where applicable
- Follow the structure in ActivityManagementTest.php

OUTPUT:
Print the complete test file contents to stdout so it can be saved to:
/Users/ianbruce/Herd/funlynk/tests/Feature/RsvpManagementTest.php
```

---

### Pattern 4: Migration Generation

**Use Case**: Need database migrations with proper schema

**Primary Agent Role**:
- Define table structure
- Specify indexes and foreign keys
- Identify constraints

**Sub-Agent Role**:
- Generate migration file
- Include proper rollback
- Follow Laravel conventions

**Example Prompt**:
```
TASK: Create Migration for Flares Table

PROJECT CONTEXT:
- FunLynk: Laravel 12 activity discovery platform
- Database: PostgreSQL with PostGIS extension
- Feature: Flares boost visibility of Posts or Activities

REFERENCE FILES:
1. /Users/ianbruce/Herd/funlynk/database/migrations/2025_11_23_144442_create_transactions_table.php
2. /Users/ianbruce/Herd/funlynk/database/migrations/2025_11_20_create_posts_table.php

SCHEMA REQUIREMENTS:

Table: flares

Columns:
- id: uuid, primary key, default gen_random_uuid()
- user_id: uuid, not null, foreign key to users.id, cascade on delete
- post_id: uuid, nullable, foreign key to posts.id, cascade on delete
- activity_id: uuid, nullable, foreign key to activities.id, cascade on delete
- type: string (enum: 'post_boost', 'activity_boost'), not null
- amount: decimal(10,2), not null (cost in credits)
- expires_at: timestamp, not null
- created_at: timestamp
- updated_at: timestamp

Indexes:
- user_id (for user's flare history)
- post_id (for post's active flares)
- activity_id (for activity's active flares)
- expires_at (for cleanup queries)
- composite: (type, expires_at) (for active flares by type)

Constraints:
- Check constraint: Either post_id OR activity_id must be set (not both, not neither)
- Check constraint: type must be 'post_boost' or 'activity_boost'
- Check constraint: amount must be > 0

REQUIREMENTS:
- Use Laravel 12 migration syntax
- Use uuid columns (not ulid)
- Include proper up() and down() methods
- Add descriptive comments for complex constraints
- Follow the style of the reference migrations

OUTPUT:
Print the complete migration file contents to stdout.
Use timestamp: 2025_12_01_create_flares_table.php
```

---

### Pattern 5: Documentation Generation

**Use Case**: Need to document a complex system or feature

**Primary Agent Role**:
- Identify what needs documentation
- Define documentation structure
- Specify audience and purpose

**Sub-Agent Role**:
- Read code and existing docs
- Generate comprehensive documentation
- Include examples and diagrams

**Example Prompt**:
```
TASK: Document the Post-to-Event Conversion System

PROJECT CONTEXT:
- FunLynk: Laravel 12 activity discovery platform
- Core Feature: Ephemeral Posts convert to persistent Events based on engagement
- Business Logic: 5+ reactions = suggest conversion, 10+ reactions = auto-convert

REFERENCE FILES:
1. /Users/ianbruce/Herd/funlynk/app/Models/Post.php
2. /Users/ianbruce/Herd/funlynk/app/Models/Activity.php
3. /Users/ianbruce/Herd/funlynk/app/Models/PostConversion.php
4. /Users/ianbruce/Herd/funlynk/app/Services/ActivityConversionService.php
5. /Users/ianbruce/Herd/funlynk/context-engine/epics/E03_Activity_Management/epic-overview.md
6. /Users/ianbruce/Herd/funlynk/context-engine/epics/E04_Discovery_Engine/epic-overview.md
7. /Users/ianbruce/Herd/funlynk/database/migrations/*post_conversions*.php

DOCUMENTATION REQUIREMENTS:

1. Explain the business logic and user experience
2. Describe the database schema (post_conversions table)
3. Document the conversion flow step-by-step
4. Show code examples of key methods
5. Explain integration between E03 (Activity Management) and E04 (Discovery Engine)
6. Include any PostGIS spatial considerations
7. Document notification flow
8. Explain edge cases and error handling

AUDIENCE:
- New developers joining the project
- Future maintainers
- Technical stakeholders

OUTPUT FORMAT:
Write to: /Users/ianbruce/Herd/funlynk/context-engine/domain-contexts/post-to-event-conversion.md

Use this structure:
# Post-to-Event Conversion System

## Overview
[High-level explanation of the feature and why it exists]

## User Experience Flow
1. [Step-by-step from user perspective]

## Business Logic
### Engagement Thresholds
- [5 reaction threshold]
- [10 reaction threshold]

### Conversion Rules
- [When conversion happens]
- [What data is copied]
- [What happens to original post]

## Database Schema
### post_conversions Table
```sql
[Table structure]
```

### Relationships
- [How it relates to posts, activities, users]

## Technical Implementation

### Key Models
#### Post.php
[Relevant methods and relationships]

#### Activity.php
[Relevant methods and relationships]

#### PostConversion.php
[Model structure]

### Services
#### ActivityConversionService
```php
[Key method signatures with explanations]
```

### Conversion Flow (Code Level)
1. [E04 detects threshold]
2. [E04 calls E03 service]
3. [E03 creates activity]
4. [Notifications sent]
5. [Post marked as converted]

## Integration Points
### E03 (Activity Management)
- [What E03 provides]

### E04 (Discovery Engine)
- [What E04 provides]

### Notifications
- [What notifications are sent and when]

## Spatial Considerations
[Any PostGIS usage, location handling during conversion]

## Edge Cases & Error Handling
- [What happens if conversion fails]
- [Duplicate conversion prevention]
- [Race conditions]

## Testing
[Key test scenarios to cover]

## Future Enhancements
[Potential improvements or known limitations]
```

---

## Pattern 6: Batch Operations

**Use Case**: Need to perform the same operation on multiple items

**Primary Agent Role**:
- Identify the pattern
- List all items to process
- Define success criteria

**Sub-Agent Role**:
- Execute operation on each item
- Report results
- Handle errors gracefully

**Example Prompt**:
```
TASK: Generate Factories for All Models Missing Them

PROJECT CONTEXT:
- FunLynk: Laravel 12 activity discovery platform
- Need factories for testing

REFERENCE FILES:
1. /Users/ianbruce/Herd/funlynk/database/factories/PostFactory.php (good example)
2. /Users/ianbruce/Herd/funlynk/database/factories/UserFactory.php (good example)

MODELS TO PROCESS:
1. /Users/ianbruce/Herd/funlynk/app/Models/Flare.php
2. /Users/ianbruce/Herd/funlynk/app/Models/PostConversion.php
3. /Users/ianbruce/Herd/funlynk/app/Models/Report.php

REQUIREMENTS:
For each model:
1. Read the model file to understand its structure
2. Identify all fillable fields
3. Generate appropriate fake data for each field
4. Handle relationships (use existing factories)
5. Follow the pattern in PostFactory.php

OUTPUT:
For each model, print:
--- START: [ModelName]Factory.php ---
[Complete factory code]
--- END: [ModelName]Factory.php ---

This allows easy extraction of each factory.
```

---

## Sub-Agent Spawning Workflow

### Step 1: Primary Agent Planning
```
1. Understand user request
2. Break down into sub-tasks
3. Identify tasks suitable for delegation
4. Prepare context for each sub-task
5. Determine dependencies (what must run sequentially vs parallel)
```

### Step 2: Spawn Sub-Agents
```bash
# Sequential example (Task 2 needs Task 1's output)
JOB1=$(python spawn_sub_agent.py gemini "Analyze Post Reaction system...")
# Wait for JOB1 to complete, review output
JOB2=$(python spawn_sub_agent.py gemini "Create FlareService based on analysis in subagent_runs/$JOB1/report.md...")

# Parallel example (independent tasks)
JOB1=$(python spawn_sub_agent.py gemini "Create Flare migration...") &
JOB2=$(python spawn_sub_agent.py gemini "Create Flare model...") &
JOB3=$(python spawn_sub_agent.py gemini "Create Flare factory...") &
wait
```

### Step 3: Monitor Progress
```bash
# Check status
cat subagent_runs/$JOB_ID/status.json

# View output
cat subagent_runs/$JOB_ID/report.md

# Check for errors
cat subagent_runs/$JOB_ID/run.log
```

### Step 4: Primary Agent Integration
```
1. Review all sub-agent outputs
2. Validate against requirements
3. Integrate components
4. Resolve conflicts or inconsistencies
5. Run tests
6. Refine as needed
```

---

## Context Injection Best Practices

### ✅ DO:

1. **Always include project identity**
   ```
   Project: FunLynk - Laravel 12 activity discovery platform
   Tech Stack: Laravel 12, Filament v4, Livewire v3, PostgreSQL + PostGIS
   ```

2. **Provide absolute file paths**
   ```
   Read: /Users/ianbruce/Herd/funlynk/app/Models/Post.php
   Not: Read the Post model
   ```

3. **Specify exact output locations**
   ```
   Write to: /Users/ianbruce/Herd/funlynk/app/Services/FlareService.php
   Not: Create a FlareService
   ```

4. **Include reference files for patterns**
   ```
   Follow the structure of: /Users/ianbruce/Herd/funlynk/app/Services/PostService.php
   ```

5. **Define success criteria**
   ```
   The service must:
   - Use DB transactions
   - Throw exceptions on errors
   - Include type hints
   - Follow PSR-12
   ```

6. **Use structured formats**
   ```
   Output format:
   # Section 1
   - Item A
   - Item B
   
   # Section 2
   - Item C
   ```

### ❌ DON'T:

1. **Reference previous conversations**
   ```
   ❌ "Analyze the file we discussed earlier"
   ✅ "Analyze /Users/ianbruce/Herd/funlynk/app/Models/Post.php"
   ```

2. **Assume project knowledge**
   ```
   ❌ "Create a service for flares"
   ✅ "Create FlareService for the flare boosting system (allows users to boost Posts/Activities for visibility)"
   ```

3. **Use relative paths without context**
   ```
   ❌ "Read app/Models/Post.php"
   ✅ "Read /Users/ianbruce/Herd/funlynk/app/Models/Post.php"
   ```

4. **Give vague instructions**
   ```
   ❌ "Make it better"
   ✅ "Add error handling for insufficient credits, use DB transactions, add type hints"
   ```

5. **Forget output format**
   ```
   ❌ "Analyze the code"
   ✅ "Analyze the code and write a markdown report with sections: Overview, Key Methods, Integration Points"
   ```

6. **Omit error handling requirements**
   ```
   ❌ "Create a service"
   ✅ "Create a service with try-catch blocks, throw descriptive exceptions, use DB transactions"
   ```

---

## Cost Optimization Strategy

### When to Use Sub-Agents (Gemini CLI Free Tier):

✅ **Code Generation**
- Migrations
- Models
- Services
- Controllers
- Tests
- Factories

✅ **Code Analysis**
- System architecture reports
- Dependency analysis
- Code quality reviews

✅ **Documentation**
- API documentation
- Feature documentation
- Code comments

✅ **Batch Operations**
- Multiple similar tasks
- Repetitive transformations

✅ **Isolated Tasks**
- Well-defined scope
- Clear inputs/outputs
- No complex decision-making

### When to Use Primary Agent (Paid Tier):

✅ **Strategic Planning**
- Architecture decisions
- Feature design
- Breaking down complex requirements

✅ **Complex Debugging**
- Multi-system issues
- Requires conversation history
- Needs user clarification

✅ **Multi-Step Workflows**
- Tasks requiring state
- Conditional logic based on previous steps

✅ **User Interaction**
- Clarifying requirements
- Presenting options
- Explaining decisions

✅ **Integration & Orchestration**
- Combining sub-agent outputs
- Resolving conflicts
- Final validation

---

## Practical Example: Implementing Flare System

### User Request:
"I need to implement a flare system where users can boost posts or activities for visibility using credits"

### Primary Agent (Planning Phase):

```
1. Understand requirements
   - Users spend credits to boost content
   - Flares have expiration
   - Boost either Post OR Activity (not both)
   - Track active flares

2. Break down into tasks:
   T1: Database schema (migration)
   T2: Model with relationships
   T3: Service class for business logic
   T4: Factory for testing
   T5: Tests
   T6: Livewire component for UI
   T7: Integration with existing systems

3. Identify sub-agent tasks:
   - T1, T2, T3, T4, T5 can be delegated
   - T6, T7 need primary agent (integration complexity)

4. Determine dependencies:
   - T1 (migration) must complete first
   - T2, T3, T4 can run in parallel after T1
   - T5 needs T2, T3, T4 complete
```

### Sub-Agent Execution:

```bash
# Task 1: Migration (must complete first)
JOB1=$(python spawn_sub_agent.py gemini "$(cat <<'EOF'
TASK: Create Migration for Flares Table

PROJECT CONTEXT:
FunLynk - Laravel 12 activity discovery platform
Tech Stack: Laravel 12, PostgreSQL + PostGIS

REFERENCE: /Users/ianbruce/Herd/funlynk/database/migrations/2025_11_23_144442_create_transactions_table.php

SCHEMA:
Table: flares
- id: uuid primary key
- user_id: uuid, foreign key to users
- post_id: uuid nullable, foreign key to posts
- activity_id: uuid nullable, foreign key to activities
- type: enum('post_boost', 'activity_boost')
- amount: decimal(10,2)
- expires_at: timestamp
- timestamps

Indexes: user_id, post_id, activity_id, expires_at, (type, expires_at)
Constraint: Either post_id OR activity_id (not both, not neither)

OUTPUT: Print complete migration code
EOF
)")

echo "Migration job: $JOB1"
# Wait for completion, review output

# Tasks 2-4: Can run in parallel
JOB2=$(python spawn_sub_agent.py gemini "Create Flare model...") &
JOB3=$(python spawn_sub_agent.py gemini "Create FlareService...") &
JOB4=$(python spawn_sub_agent.py gemini "Create FlareFactory...") &
wait

# Task 5: Tests (needs 2-4 complete)
JOB5=$(python spawn_sub_agent.py gemini "Create Flare tests...")
```

### Primary Agent (Integration Phase):

```
1. Review all sub-agent outputs
2. Apply migration
3. Validate model relationships
4. Test service methods
5. Run test suite
6. Create Livewire component (primary agent handles this)
7. Integrate with credit system
8. Update documentation
```

---

## Monitoring & Debugging Sub-Agents

### Check Job Status
```bash
cat subagent_runs/20251201_113000_abc123/status.json
```

```json
{
  "job_id": "20251201_113000_abc123",
  "agent": "gemini",
  "started_at": "2025-12-01T16:30:00Z",
  "status": "completed",
  "exit_code": 0,
  "duration_ms": 15420,
  "finished_at": "2025-12-01T16:30:15Z"
}
```

### View Output
```bash
cat subagent_runs/20251201_113000_abc123/report.md
```

### Check Logs
```bash
cat subagent_runs/20251201_113000_abc123/run.log
```

### Common Issues

**Issue**: Sub-agent produces irrelevant output
**Cause**: Insufficient context in prompt
**Solution**: Add more specific requirements and reference files

**Issue**: Sub-agent fails with timeout
**Cause**: Task too complex or prompt too long
**Solution**: Break into smaller sub-tasks

**Issue**: Sub-agent output doesn't match project patterns
**Cause**: Missing reference files
**Solution**: Always include 1-2 reference implementations

---

## Future Enhancements

### Phase 1 (Current):
- ✅ Basic sub-agent spawning
- ✅ Job tracking with status.json
- ✅ Output capture in report.md

### Phase 2 (Proposed):
- [ ] Job queue management (list all jobs, filter by status)
- [ ] Result aggregation (combine outputs from multiple sub-agents)
- [ ] Retry logic (auto-retry failed jobs with adjustments)
- [ ] Template library (pre-built prompts for common tasks)

### Phase 3 (Future):
- [ ] Context caching (reuse common context snippets)
- [ ] Parallel execution manager (spawn multiple sub-agents, wait for all)
- [ ] Result validation (auto-check outputs against criteria)
- [ ] Learning system (improve prompts based on success/failure)

---

## Quick Reference

### Spawning a Sub-Agent
```bash
JOB_ID=$(python spawn_sub_agent.py gemini "YOUR COMPLETE SELF-CONTAINED PROMPT")
```

### Checking Status
```bash
cat subagent_runs/$JOB_ID/status.json
```

### Viewing Output
```bash
cat subagent_runs/$JOB_ID/report.md
```

### Prompt Template Checklist
- [ ] Project identity included
- [ ] Tech stack mentioned
- [ ] Absolute file paths provided
- [ ] Reference files specified
- [ ] Requirements clearly listed
- [ ] Output format defined
- [ ] Output location specified
- [ ] Success criteria defined
- [ ] Error handling requirements included

---

## Summary

**The agent swarm architecture allows you to:**
1. Distribute cognitive load across multiple agents
2. Use free tier for tactical work, paid tier for strategic work
3. Maintain context in primary agent while delegating execution
4. Scale development by parallelizing independent tasks
5. Reduce costs while maintaining quality

**Key to success:**
- **Self-contained prompts** with complete context
- **Clear task boundaries** between primary and sub-agents
- **Structured outputs** for easy integration
- **Reference implementations** for consistency
- **Validation** of sub-agent outputs before integration

This architecture transforms development from a single-agent bottleneck into a distributed, efficient system where each agent operates in its optimal role.
