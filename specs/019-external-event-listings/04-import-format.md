# 019 — Listing import format

`php artisan listings:import {path}` reads a JSON file produced by
Muse AI research and creates external listings as **drafts**
(`kind = external`, `status = draft`). It can never publish. A
listing becomes visible only when an admin approves it in the
Filament Activity resource (approval records who and when).

Re-running the same file is safe: rows dedupe on
(source_url, title, start_time), so weekly research runs can overlap.

## Shape

The file is a JSON array of listing objects.

| Field | Required | Rules |
|---|---|---|
| `title` | yes | Non-empty string. |
| `description` | yes | Non-empty string. Plain text; it is escaped on render. |
| `source_name` | yes | Who runs the event, as shown on the card (for example "Long Beach Run Club"). |
| `source_url` | yes | The organizer's own page. Must be a valid `https` URL. `http:` and `javascript:` are rejected. |
| `start_time` | yes | ISO 8601 date/time with timezone, for example `2026-11-14T10:00:00-08:00`. |
| `end_time` | no | ISO 8601; must be after `start_time`. Omit for events with no known end. |
| `location_name` | yes | Venue or meeting point name. |
| `location_address` | no | Street address if known. |
| `latitude` | yes | Number, -90 to 90. Listings are public venues, never personal addresses. |
| `longitude` | yes | Number, -180 to 180. |
| `activity_type` | no | Free-form category string; defaults to `social`. |
| `payment_type` | no | `free` (default), `online` or `at_door`. |
| `price_cents` | no | Must be empty for `free`; a positive integer (cents) for `online` / `at_door`. Unknown price: use `free` with no `price_cents` and say the real price in the description instead. |
| `recurring_schedule_id` | no | UUID of an existing `recurring_schedules` row. Listings sharing one collapse in the feed to the next upcoming occurrence. |

No image fields exist on purpose: listings never download or
hot-link source images ([O-04]). Cards render the date strip over a
plain placeholder.

## Example

```json
[
  {
    "title": "Saturday Open Mat",
    "description": "All levels welcome, non-members too. Gi and no-gi. Free for first-time visitors, regulars check the source page for mat fees.",
    "source_name": "10th Planet Long Beach",
    "source_url": "https://example.com/open-mat",
    "start_time": "2026-11-14T10:00:00-08:00",
    "end_time": "2026-11-14T11:30:00-08:00",
    "location_name": "10th Planet Jiu Jitsu",
    "location_address": "1757 Pacific Ave, Long Beach, CA",
    "latitude": 33.7805,
    "longitude": -118.194,
    "activity_type": "sports",
    "payment_type": "free"
  },
  {
    "title": "Bluff Park Run Club",
    "description": "Weekly social run, every Tuesday at 7 PM. Walk, jog or run, any skill level.",
    "source_name": "Long Beach Run Club",
    "source_url": "https://example.com/run-club",
    "start_time": "2026-11-17T19:00:00-08:00",
    "location_name": "Bluff Park",
    "latitude": 33.763,
    "longitude": -118.164,
    "activity_type": "sports",
    "payment_type": "free"
  }
]
```

Output reports one line per rejected row with the reason, then a
summary: imported, duplicates skipped, rejected. Exit code is 0
when the file was read and processed; a missing file or a file that
is not a JSON array exits non-zero.
