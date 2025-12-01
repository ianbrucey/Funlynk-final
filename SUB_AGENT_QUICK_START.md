# Sub-Agent Quick Start Guide

## TL;DR
```bash
# Spawn a sub-agent task
python3 spawn_sub_agent.py gemini "YOUR DETAILED PROMPT HERE"

# Returns job_id immediately (e.g., 20251201_112636_90d0ea)

# Check status after 30-60 seconds
cat subagent_runs/{job_id}/status.json

# Read output
cat subagent_runs/{job_id}/report.md
```

## When to Use Sub-Agents

✅ **Delegate to Sub-Agent:**
- Creating migrations, models, factories
- Generating service classes (with detailed specs)
- Writing tests from clear requirements
- Analyzing specific files
- Creating documentation from templates

❌ **Handle Yourself (Primary Agent):**
- Reading/analyzing documentation
- Making architectural decisions
- Complex refactoring across files
- Interactive debugging
- Final code integration and testing

## Prompt Template

```
Read the following files for context:
- {exact file path 1}
- {exact file path 2}
- {documentation path}

{Clear, specific task description}

Requirements:
- Requirement 1 (with specific details)
- Requirement 2 (with specific details)
- Follow {specific pattern/convention}

Output format: {exact format description}
Include: {specific elements to include}
```

## FunLynk-Specific Examples

### Example 1: Create Migration
```bash
python3 spawn_sub_agent.py gemini "Read the following files:
- context-engine/epics/E02_User_Profile_Management/database-schema.md
- context-engine/domain-contexts/database-context.md
- database/migrations/2024_01_01_000001_create_users_table.php

Create a Laravel 12 migration for the user_profiles table.

Requirements:
- Migration name: create_user_profiles_table
- Columns: id (bigint), user_id (foreign key), bio (text), avatar_url (string), created_at, updated_at
- Indexes: user_id (unique)
- Foreign keys: user_id references users.id (cascade on delete)
- Follow the pattern from the example migration

Output: Complete migration file content with up() and down() methods"
```

### Example 2: Create Service Class
```bash
python3 spawn_sub_agent.py gemini "Read the following files:
- context-engine/epics/E02_User_Profile_Management/epic-overview.md
- context-engine/tasks/E02_User_Profile_Management/F01_Profile_CRUD/README.md
- app/Models/User.php
- context-engine/domain-contexts/service-architecture.md

Create a UserProfileService class in app/Services/.

Requirements:
- Namespace: App\Services
- Methods: createProfile(User \$user, array \$data), updateProfile(UserProfile \$profile, array \$data), getProfile(int \$userId), deleteProfile(int \$profileId)
- Use Laravel 12 conventions with proper type hints
- Include PHPDoc blocks for each method
- Handle validation using Laravel's validator
- Throw appropriate exceptions for error cases

Output: Complete PHP service class with namespace, use statements, and all methods implemented"
```

### Example 3: Create Tests
```bash
python3 spawn_sub_agent.py gemini "Read the following files:
- app/Services/UserProfileService.php
- app/Models/User.php
- context-engine/tasks/E02_User_Profile_Management/F01_Profile_CRUD/README.md

Create Pest v4 tests for UserProfileService.

Requirements:
- Test file: tests/Feature/UserProfileServiceTest.php
- Use Pest v4 syntax (test() function, expect() assertions)
- Test cases: create profile, update profile, get profile, delete profile, validation errors
- Use factories for test data
- Follow AAA pattern (Arrange, Act, Assert)

Output: Complete Pest test file"
```

## Parallel Execution

```bash
# Spawn multiple tasks at once
JOB1=$(python3 spawn_sub_agent.py gemini "Create migration...")
JOB2=$(python3 spawn_sub_agent.py gemini "Create model...")
JOB3=$(python3 spawn_sub_agent.py gemini "Create service...")

echo "Jobs: $JOB1, $JOB2, $JOB3"

# Wait 30-60 seconds, then review all outputs
cat subagent_runs/$JOB1/report.md
cat subagent_runs/$JOB2/report.md
cat subagent_runs/$JOB3/report.md
```

## Critical Rules

1. **Stateless**: Sub-agents have NO memory. Include ALL context in the prompt.
2. **File Paths**: Always specify exact file paths to read.
3. **Explicit Requirements**: Be specific about what you want.
4. **Output Format**: Define exactly what format you expect.
5. **Laravel 12 / Filament v4**: Always specify framework versions.
6. **Review Before Integration**: Always review sub-agent output before using it.

## Troubleshooting

**Job still running after 60 seconds?**
- Check `run.log` for errors
- Gemini CLI may be waiting for input (check prompt for ambiguity)

**Output doesn't match expectations?**
- Prompt may be too vague - add more specific requirements
- Reference example files that follow the pattern
- Include explicit output format specification

**Sub-agent can't find files?**
- Verify file paths are correct
- Ensure files exist before delegating
- Use exact paths from project root

## Output Structure

```
subagent_runs/{job_id}/
├── prompt.txt          # Your prompt (for reference)
├── status.json         # Job status, timing, exit code
├── output.jsonl        # Event log (start, final, error)
├── report.md           # Sub-agent's output (THE IMPORTANT FILE)
└── run.log            # Execution logs (for debugging)
```

## Next Steps

After reviewing sub-agent output:
1. Copy code to appropriate files
2. Run `vendor/bin/pint --dirty` to format
3. Run tests: `php artisan test --filter=YourTest`
4. Iterate if needed (spawn new sub-agent task with corrections)

---

**For full documentation, see AGENTS.md section "Multi-Agent Workflow Pattern"**

