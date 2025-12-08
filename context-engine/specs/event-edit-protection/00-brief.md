# Event Edit Protection System - Brief

## Problem Statement
Hosts can edit event details after attendees have paid, enabling potential fraud:
- Change date from Saturday night → Tuesday 6am
- Change location from local → 500 miles away  
- Change title from "Beach Party" → "Tax Seminar"

Attendees are stuck with something they never signed up for.

## Success Verdict
1. Hosts CANNOT increase price or convert paid→free after first payment
2. Hosts CAN make cosmetic changes freely
3. Significant changes (date/location) trigger 48-72hr refund window for attendees
4. Attendees receive clear notifications of changes with easy refund option
5. Full audit trail of all changes visible to attendees
6. Hosts see clear warnings before editing paid events

## Core Rules

### Field Categories

| Category | Fields | Rule |
|----------|--------|------|
| **Always Editable** | description, cover_image, additional_notes, what_to_bring | No restrictions |
| **Notify Only** | end_time (±2hrs), capacity_increase, minor title edit | Notify attendees, no refund window |
| **Refund Window** | start_time (>2hr change), location (different address), major title change | 72hr automatic refund window |
| **Locked** | price (cannot increase), paid→free conversion | Blocked entirely after first payment |

### Change Detection Thresholds

| Field | "Minor" (Notify Only) | "Significant" (Refund Window) |
|-------|----------------------|------------------------------|
| **Title** | <30% character change | ≥30% character change |
| **Start Time** | ±2 hours | >2 hours shift |
| **End Time** | Any change | N/A (always notify-only) |
| **Location** | Same address, room change | Different address |
| **Price** | Decrease allowed | Increase = BLOCKED |

### Refund Window Mechanics
- **Duration:** 72 hours from notification
- **Scope:** Per-change, not per-attendee (one window per edit batch)
- **Default:** If attendee takes no action, changes are accepted
- **Refund:** Full refund, automatic, no questions asked
- **Multiple Changes:** Each significant edit resets/extends window

## User Flows

### Flow 1: Host Edits Paid Event
```
Host clicks "Edit" on event with paid attendees
    ↓
System shows warning modal:
  "This event has X paid attendees. Some changes may trigger refund windows."
  [Continue to Edit] [Cancel]
    ↓
Edit form shows field indicators:
  🟢 Description (always editable)
  🟡 End Time (will notify attendees)
  🔴 Start Date (will trigger 72hr refund window)
  🔒 Price (locked - cannot increase)
    ↓
Host makes changes and submits
    ↓
System validates changes, creates audit log
    ↓
If significant changes: Create refund window, notify all paid attendees
If minor changes: Notify attendees (info only)
```

### Flow 2: Attendee Receives Change Notification
```
Attendee receives notification:
  "Beach Party has been updated"
  - Date changed: Dec 15 → Dec 22
  - Location changed: Downtown → Suburbs
  
  [View Changes] [Accept] [Request Refund - 71hrs remaining]
    ↓
If "Request Refund": Automatic full refund, RSVP cancelled
If "Accept" or timeout: Changes accepted, RSVP maintained
```

### Flow 3: Attendee Views Audit Trail
```
On event detail page, attendee sees:
  "ℹ️ This event was modified 2 times since your purchase"
  [View History]
    ↓
Modal shows:
  Dec 10, 3:45 PM - Location changed
    Before: 123 Main St
    After: 456 Oak Ave
    Reason: "Original venue had a scheduling conflict"
  
  Dec 8, 1:20 PM - Description updated
    (cosmetic change - no details shown)
```

## Out of Scope (v1)
- Partial refunds (change affects part of ticket value)
- Attendee voting on changes
- Host appeals process
- Escrow until event completion (future consideration)
- Free event restrictions (more lenient for v1)

## Tech Stack Context
- Laravel 12, Livewire v3
- Existing: Activity model, Rsvp model, RefundService, NotificationService
- Payments: Stripe Connect (refunds via existing RefundService)

