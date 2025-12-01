# Recommended Additions to .augment/rules/AGENTS.md

## Summary
The existing `.augment/rules/AGENTS.md` Multi-Agent Workflow section (lines 220-570) is already comprehensive. This document identifies specific enhancements from `AGENT_SWARM_ARCHITECTURE.md` that would add value without duplication.

---

## 1. Add "The Golden Rule" (Insert after line 270)

**Location**: After "Critical Constraint" section

**Content to Add**:
```markdown
**The Golden Rule of Sub-Agent Prompts:**
> A sub-agent prompt should be readable by a developer who just joined the project and has never seen the codebase before. If they couldn't complete the task with just the prompt, it's not detailed enough.
```

**Why**: Provides a simple heuristic for evaluating prompt quality.

---

## 2. Add "Three Types of Context" Framework (Insert after line 272)

**Location**: Before "Template Structure" section

**Content to Add**:
```markdown
#### The Three Types of Context

Every sub-agent prompt must include these three context layers:

**A. Project Identity Context (Always Include)**
```
Project: FunLynk - Laravel 12 activity discovery platform
Tech Stack: Laravel 12, Filament v4, Livewire v3, PostgreSQL + PostGIS, DaisyUI, Pest v4
Core Feature: Posts (ephemeral, 24-48h) convert to Events (persistent) based on engagement
```

**B. Domain Context (When Relevant)**
Point to specific files in `context-engine/domain-contexts/`:
- UI work? → Reference `ui-design-standards.md`
- Database work? → Reference `database-context.md`
- Auth work? → Reference `auth-context.md`
- Post-to-Event logic? → Reference epic overviews

**C. Task-Specific Context**
- **Exact file paths** to read (absolute paths)
- **Specific requirements** and constraints
- **Expected output format** and location
- **Reference implementations** to follow
```

**Why**: More structured approach than current "read these files" pattern.

---

## 3. Add Cost Optimization Strategy (Insert after line 500)

**Location**: After "Decision Matrix" section

**Content to Add**:
```markdown
### Cost Optimization Strategy

#### When to Use Sub-Agents (Gemini CLI Free Tier):
✅ **Code Generation**: Migrations, models, services, controllers, tests, factories
✅ **Code Analysis**: System architecture reports, dependency analysis
✅ **Documentation**: API docs, feature docs, code comments
✅ **Batch Operations**: Multiple similar tasks, repetitive transformations
✅ **Isolated Tasks**: Well-defined scope, clear inputs/outputs

#### When to Use Primary Agent (Paid Tier):
✅ **Strategic Planning**: Architecture decisions, feature design
✅ **Complex Debugging**: Multi-system issues, requires conversation history
✅ **Multi-Step Workflows**: Tasks requiring state, conditional logic
✅ **User Interaction**: Clarifying requirements, presenting options
✅ **Integration & Orchestration**: Combining outputs, resolving conflicts

**Optimization Tip**: Use sub-agents for 70-80% of implementation work (code generation, tests, docs), reserve primary agent for 20-30% strategic work (planning, integration, debugging).
```

**Why**: Explicitly addresses the user's concern about credit consumption and provides clear guidance.

---

## 4. Add Monitoring Commands (Insert after line 372)

**Location**: After "Execution" section, before "Review & Integration"

**Content to Add**:
```markdown
#### Step 4.5: Monitoring Sub-Agent Jobs

**Check Job Status**:
```bash
cat subagent_runs/$JOB_ID/status.json
```

**Example Output**:
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

**View Output**:
```bash
cat subagent_runs/$JOB_ID/report.md
```

**Check for Errors**:
```bash
cat subagent_runs/$JOB_ID/run.log
```

**Quick Status Check**:
```bash
# Check if job is complete
if [ "$(jq -r '.status' subagent_runs/$JOB_ID/status.json)" = "completed" ]; then
  echo "Job complete!"
  cat subagent_runs/$JOB_ID/report.md
else
  echo "Job still running..."
fi
```
```

**Why**: Provides practical commands for monitoring jobs, which is missing from current doc.

---

## 5. Enhance Pattern 2 (Service Classes) with Complete Example

**Location**: Replace/enhance lines 420-439

**Content to Add**:
```markdown
#### Pattern 2: Creating Service Classes

**Complete Example Prompt**:
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
```

**Why**: Current pattern is good but this provides a complete, real-world example specific to FunLynk.

---

## 6. Add Best Practices Section Enhancements

**Location**: Enhance existing "Best Practices" section (around line 501)

**Content to Add**:
```markdown
### Context Injection Best Practices

#### ✅ DO:

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

6. **Use structured output formats**
   ```
   Output format:
   # Section 1
   - Item A
   - Item B
   
   # Section 2
   - Item C
   ```

#### ❌ DON'T:

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
```

**Why**: More comprehensive and actionable than current best practices list.

---

## 7. Add Troubleshooting Enhancement

**Location**: Enhance existing "Troubleshooting" section (around line 549)

**Content to Add**:
```markdown
**Common Issues and Solutions:**

| Issue | Cause | Solution |
|-------|-------|----------|
| Sub-agent produces irrelevant output | Insufficient context in prompt | Add project identity, reference files, specific requirements |
| Sub-agent fails with timeout | Task too complex or prompt too long | Break into smaller sub-tasks, simplify prompt |
| Output doesn't match project patterns | Missing reference files | Always include 1-2 reference implementations |
| Sub-agent can't find files | Incorrect file paths | Use absolute paths, verify files exist first |
| Output has syntax errors | Missing coding standards specification | Explicitly specify Laravel 12, PHP 8.2+, PSR-12 |
| Business logic is incorrect | Vague requirements | Provide step-by-step logic, edge cases, validation rules |
```

**Why**: Table format is easier to scan than current paragraph format.

---

## Summary of Additions

### High Priority (Add These):
1. ✅ **The Golden Rule** - Simple heuristic for prompt quality
2. ✅ **Three Types of Context** - Structured framework
3. ✅ **Cost Optimization Strategy** - Addresses user's main concern
4. ✅ **Monitoring Commands** - Practical job tracking

### Medium Priority (Nice to Have):
5. ⚠️ **Enhanced Service Pattern** - Complete real-world example
6. ⚠️ **Best Practices DO/DON'T** - More actionable guidance
7. ⚠️ **Troubleshooting Table** - Easier to scan

### Low Priority (Reference Only):
- Keep `AGENT_SWARM_ARCHITECTURE.md` as a comprehensive reference
- Link to it from `.augment/rules/AGENTS.md` if desired

---

## Implementation Approach

**Option 1: Minimal Integration**
Add only items 1-4 (High Priority) to `.augment/rules/AGENTS.md`

**Option 2: Comprehensive Integration**
Add all 7 enhancements to `.augment/rules/AGENTS.md`

**Option 3: Reference Approach**
Keep `.augment/rules/AGENTS.md` as-is, add a link to `AGENT_SWARM_ARCHITECTURE.md` for detailed examples

**Recommendation**: Option 1 (Minimal Integration) - Adds the most value without bloating the existing doc.
