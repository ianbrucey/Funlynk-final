# Groups Feature Bug Fix - Agent Task Distribution

## Overview
Based on user feedback in `context-engine/scratch.md`, we've identified multiple issues with the Groups feature that need parallel development.

## Agent Assignments

### Agent 0 (Primary/Me): Group Creation Logic & Data Structure
**Focus:** The "Create Group" form flow and core data attributes.

| Task | Priority | Status |
|------|----------|--------|
| Database Migration - Add location columns to groups | HIGH | Not Started |
| Dynamic Tags Input (type & enter) | HIGH | Not Started |
| Mandatory Location Field | HIGH | Not Started |
| Avatar Upload During Creation | MEDIUM | Not Started |

**Files I'll Modify:**
- `database/migrations/2025_12_07_*_add_location_to_groups.php` (new)
- `app/Livewire/Groups/CreateGroup.php`
- `resources/views/livewire/groups/create-group.blade.php`
- `app/Services/GroupService.php`
- `app/Models/Group.php`

---

### Agent 1: UI/UX & Frontend Display
**Prompt:** `context-engine/agent-prompts/agent-1-ui-frontend.md`

| Task | Priority |
|------|----------|
| Private Group Tab Visibility Fix | HIGH |
| Members List - Display Profile Pictures | MEDIUM |
| Groups Index - Default Filter to "All" | LOW |
| Group Settings - Image Preview Before Save | MEDIUM |

---

### Agent 2: Notifications & Real-Time
**Prompt:** `context-engine/agent-prompts/agent-2-notifications.md`

| Task | Priority |
|------|----------|
| Fix Join Request Notification - Missing User Info | HIGH |
| Audit All Group Notifications for Actor Data | MEDIUM |
| Investigate Real-Time Notification Delivery | LOW |
| Update Notification Display Component | HIGH |

---

### Agent 3: Permissions & Image Handling
**Prompt:** `context-engine/agent-prompts/agent-3-permissions-images.md`

| Task | Priority |
|------|----------|
| Fix 403 Error When Creator Accesses Settings | CRITICAL |
| Use Policy Instead of Manual Check | MEDIUM |
| Verify Image Upload in Group Creation | HIGH |
| Verify Image Storage Path | LOW |

---

## Coordination Notes

### No Conflict Zones
Each agent works on separate files. Potential overlap:
- **CreateGroup.php**: Agent 0 (logic) + Agent 3 (images) → Agent 0 handles both
- **GroupSettings.php**: Agent 1 (preview) + Agent 3 (403 fix) → Separate concerns, minimal conflict

### Shared Dependencies
- All agents should run `php artisan test --filter=Group` before/after changes
- Current baseline: 97 tests passing

### Communication
After completing tasks, each agent should:
1. List files modified
2. Note any additional issues discovered
3. Confirm tests still pass

## Quick Start for Other Agents
```bash
# Read your prompt file
cat context-engine/agent-prompts/agent-{N}-{topic}.md

# Run tests before starting
php artisan test --filter=Group

# After changes
php artisan test --filter=Group
```

