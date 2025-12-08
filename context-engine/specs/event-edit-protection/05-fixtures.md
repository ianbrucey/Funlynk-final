# Event Edit Protection - Test Fixtures & Examples

## Change Classification Examples

### Title Changes

| Old Title | New Title | Similarity | Classification |
|-----------|-----------|------------|----------------|
| "Beach Party" | "Beach Party 2024" | 85% | `minor` |
| "Beach Party" | "Beach Bonfire" | 70% | `minor` |
| "Beach Party" | "Tax Seminar" | 15% | `significant` |
| "Friday Night Jazz" | "Friday Night Jazz - MOVED TO SATURDAY" | 65% | `significant` |
| "Yoga in the Park" | "Yoga in the Park " | 99% | `cosmetic` (whitespace) |

### Time Changes

| Old Time | New Time | Difference | Classification |
|----------|----------|------------|----------------|
| Dec 15, 7:00 PM | Dec 15, 7:30 PM | 30 min | `minor` |
| Dec 15, 7:00 PM | Dec 15, 9:00 PM | 2 hrs | `minor` (boundary) |
| Dec 15, 7:00 PM | Dec 15, 10:00 PM | 3 hrs | `significant` |
| Dec 15, 7:00 PM | Dec 16, 7:00 PM | 24 hrs | `significant` |
| Dec 15, 7:00 PM | Dec 15, 6:00 PM | 1 hr (earlier) | `minor` |

### Location Changes

| Old Location | New Location | Classification |
|--------------|--------------|----------------|
| "Room A, Convention Center" | "Room B, Convention Center" | `minor` |
| "123 Main St, Austin TX" | "123 Main St, Austin TX" | `cosmetic` (no change) |
| "Downtown Venue, Austin" | "Suburbs Venue, Round Rock" | `significant` |
| "Central Park" | "Central Park, NYC" | `minor` (clarification) |
| NULL | "123 Main St" | `minor` (adding info) |

### Price Changes

| Old Price | New Price | Classification |
|-----------|-----------|----------------|
| $50 | $45 | `minor` (decrease allowed) |
| $50 | $0 | `blocked` (paid→free) |
| $50 | $55 | `blocked` (increase) |
| $0 | $25 | `minor` (free→paid OK) |

---

## Sample Audit Log Entries

```json
[
  {
    "id": "uuid-1",
    "activity_id": "activity-uuid",
    "editor_id": "host-uuid",
    "field_name": "start_time",
    "old_value": "\"2024-12-15T19:00:00Z\"",
    "new_value": "\"2024-12-22T19:00:00Z\"",
    "change_category": "significant",
    "reason": "Original venue had a scheduling conflict",
    "triggered_refund_window": true,
    "paid_attendee_count": 45,
    "created_at": "2024-12-10T15:30:00Z"
  },
  {
    "id": "uuid-2",
    "activity_id": "activity-uuid",
    "editor_id": "host-uuid",
    "field_name": "description",
    "old_value": "\"Join us for a fun evening...\"",
    "new_value": "\"Join us for an amazing evening...\"",
    "change_category": "cosmetic",
    "reason": null,
    "triggered_refund_window": false,
    "paid_attendee_count": 45,
    "created_at": "2024-12-10T15:30:00Z"
  }
]
```

---

## Sample Refund Window

```json
{
  "id": "window-uuid",
  "activity_id": "activity-uuid",
  "triggered_at": "2024-12-10T15:30:00Z",
  "expires_at": "2024-12-13T15:30:00Z",
  "trigger_edit_log_id": "uuid-1",
  "changes_summary": [
    {
      "field": "start_time",
      "old_display": "December 15, 2024 at 7:00 PM",
      "new_display": "December 22, 2024 at 7:00 PM"
    }
  ],
  "status": "active"
}
```

---

## Sample Change Responses

```json
[
  {
    "rsvp_id": "rsvp-1",
    "refund_window_id": "window-uuid",
    "response": "accepted",
    "responded_at": "2024-12-10T18:45:00Z",
    "refund_id": null
  },
  {
    "rsvp_id": "rsvp-2",
    "refund_window_id": "window-uuid",
    "response": "refunded",
    "responded_at": "2024-12-11T09:20:00Z",
    "refund_id": "refund-uuid-123"
  },
  {
    "rsvp_id": "rsvp-3",
    "refund_window_id": "window-uuid",
    "response": null,
    "responded_at": null,
    "refund_id": null
  }
]
```

---

## Notification Content Examples

### Email: Event Changed (Significant)

```
Subject: Important: "Beach Party" has been updated

Hi [Name],

The host has made changes to an event you're attending:

Event: Beach Party
━━━━━━━━━━━━━━━━━━

📅 Date Changed:
   Was: Saturday, December 15 at 7:00 PM
   Now: Saturday, December 22 at 7:00 PM

📍 Location Changed:
   Was: Downtown Venue, 123 Main St
   Now: Suburbs Venue, 456 Oak Ave

Host's Note:
"The original venue had a scheduling conflict. The new location has better parking!"

━━━━━━━━━━━━━━━━━━

You have 72 hours to request a full refund if these changes don't work for you.

[Accept Changes]  [Request Refund]

Refund window expires: December 13, 2024 at 3:30 PM
```

### In-App Notification

```json
{
  "type": "event_changed",
  "title": "Beach Party has been updated",
  "body": "Date changed to Dec 22. You have 71 hours to request a refund.",
  "action_url": "/activities/activity-uuid",
  "data": {
    "activity_id": "activity-uuid",
    "refund_window_id": "window-uuid",
    "expires_at": "2024-12-13T15:30:00Z"
  }
}
```

