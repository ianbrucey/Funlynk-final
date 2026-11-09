# 020 — Listing import format v2 (images)

This updates 019's `04-import-format.md`. Everything in the
019 format stands unchanged; this file specifies only what
020 adds: the `image_url` field on `listings:import`, the
image behavior behind it, and the images-only backfill file
for `listings:import-images`. The design and the reasoning
live in `03-images-pipeline.md`.

## `listings:import` — one new optional field

| Field | Required | Rules |
|---|---|---|
| `image_url` | no | The source's own photo of the activity (see "For researchers"). Must be a valid `https` URL when present. It is downloaded once at import, processed, and stored in FunLynk's own storage; the URL itself is kept on the row as `image_source_url` for attribution. |

All other fields are exactly as in 019's table (title,
description, source_name, source_url, start_time, end_time,
location_name, location_address, latitude, longitude,
activity_type, payment_type, price_cents,
recurring_schedule_id).

### Image behavior (summary of `03-images-pipeline.md`)

- The image step runs **after** the dedupe check: a duplicate
  row is skipped without any download.
- Download caps: 10 MB maximum (config
  `listings.image_max_bytes`), content type must be
  image/jpeg, image/png, image/webp or image/gif, and the
  bytes must actually decode as an image. Stored output is
  always a WebP produced by `ImageProcessor` (directory
  `activities`, preset `cover`, uploads disk), and its path
  becomes `images[0]`.
- A bad `image_url` (not https, unreachable, wrong type, too
  big, undecodable) **never rejects the row**. The listing
  imports without an image and the import report flags it:
  one line for the row, and image counters in the summary
  (`Images: 13 stored, 3 failed.`).
- Omitting `image_url` is always fine. An imageless listing
  renders the date-strip placeholder, as in 019.

## For researchers (batch producers)

Per activity, capture the **single best image URL**:

- Prefer the source's own photo of the actual activity or
  venue, from the organizer's or venue's own page.
- Not a stock photo, not a logo or banner graphic, not a
  social-media avatar, not a map screenshot.
- The URL must point at the image file itself (the address
  the page's `<img>` loads), and it must be `https`.
- One image per listing. If the source has no usable photo,
  omit the field rather than substituting something generic.

## Example (019's example, extended)

```json
[
  {
    "title": "Saturday Open Mat",
    "description": "All levels welcome, non-members too. Gi and no-gi. Free for first-time visitors, regulars check the source page for mat fees.",
    "source_name": "10th Planet Long Beach",
    "source_url": "https://example.com/open-mat",
    "image_url": "https://example.com/images/open-mat.jpg",
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

The second row has no `image_url` on purpose: it imports
imageless and can be repaired later by the backfill below.

## `listings:import-images` — the backfill file

For listings that already exist without an image (the 18
imported under 019, and any row whose image failed at
import). The command fills images only; it never creates a
row and never changes any other field or any status.

The file is a JSON array of objects keyed by the same
dedupe triple as the importer:

| Field | Required | Rules |
|---|---|---|
| `source_url` | yes | Must match the existing listing exactly. |
| `title` | yes | Must match the existing listing exactly. |
| `start_time` | yes | ISO 8601, as in the main format; must match the existing listing. |
| `image_url` | yes | Same rules as above: `https`, the source's own photo of the activity. |

```json
[
  {
    "source_url": "https://example.com/run-club",
    "title": "Bluff Park Run Club",
    "start_time": "2026-11-17T19:00:00-08:00",
    "image_url": "https://example.com/images/tuesday-run.jpg"
  }
]
```

Per entry the command reports one of: **filled** (image
stored, `image_source_url` set), **skipped** (listing already
has an image; nothing downloaded, nothing overwritten),
**not found** (no listing matches the triple), or **image
failed** (listing matched but the image step failed; the
listing is unchanged). The summary line reports all four
counts plus image failures. Exit code is 0 when the file was
read and processed; a missing file or a file that is not a
JSON array exits non-zero, matching `listings:import`.

Re-running the same backfill file is safe: filled rows are
skipped on the second run.
