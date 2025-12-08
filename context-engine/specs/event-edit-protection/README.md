# Event Edit Protection System

## Overview
Protects attendees from fraud by restricting what hosts can change after receiving payments, and providing automatic refund windows when significant changes occur.

## Status: 📋 PLANNED (Not Implemented)

## Documents

| File | Purpose |
|------|---------|
| [00-brief.md](./00-brief.md) | Problem statement, success criteria, core rules |
| [01-database-schema.md](./01-database-schema.md) | New tables, relationships, data flow |
| [02-service-architecture.md](./02-service-architecture.md) | Service layer design, method signatures |
| [03-ui-components.md](./03-ui-components.md) | Host and attendee UI mockups |
| [04-implementation-plan.md](./04-implementation-plan.md) | Phased tasks with time estimates |
| [05-fixtures.md](./05-fixtures.md) | Test data, classification examples, notifications |
| [06-edge-cases.md](./06-edge-cases.md) | Edge cases, business rules, open questions |

## Quick Reference

### Field Categories
```
🟢 Always Editable:  description, cover_image, additional_notes
🟡 Notify Only:      end_time (±2hrs), capacity_increase, minor title
🔴 Refund Window:    start_time (>2hr), location change, major title
🔒 Locked:           price increase, paid→free conversion
```

### Key Numbers
- **Refund Window Duration:** 72 hours
- **Title Change Threshold:** 30% character difference
- **Time Change Threshold:** 2 hours

### User Flows
1. **Host edits paid event** → Warning modal → Field indicators → Confirmation → Audit log → (Refund window if significant)
2. **Attendee sees changes** → Notification → Banner on event page → Accept or Request Refund
3. **Window expires** → Implicit acceptance → Window closed

## Dependencies
- Existing: `Activity`, `Rsvp`, `RefundService`, `NotificationService`
- New: 3 tables, 4 services, 4 Livewire components, 1 scheduled job

## Estimated Effort
**16-22 hours** across 6 phases

## Open Questions
See [06-edge-cases.md](./06-edge-cases.md#open-questions-decide-before-implementation) for decisions needed before implementation.

