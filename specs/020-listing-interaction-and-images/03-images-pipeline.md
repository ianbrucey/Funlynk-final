# 020 — Workstream B: images for listings

Detail design for the brief's workstream B. Archaeology is in
`01-archaeology-and-plan.md` section (d) and (e). The import
format itself is specified in `04-import-format.md`; this file
is the decision record and the importer/rendering design.

## The decision (locked): download at import, store our own copy

The owner asked: download the images and upload them into
FunLynk formally, or reference the source URL directly — and
directed us to choose the most maintainable and stable
solution even if it is more cumbersome. The decision:

**The importer downloads the image once, at import time,
processes it through `App\Services\ImageProcessor` with the
exact hosted-activity arguments — directory `activities`,
preset `cover`, disk `s3` — and stores the returned path in
the activity's `images` array as `images[0]`.**

Reasoning, stated once so it does not get relitigated:

- **Hotlinking rots.** Source sites redesign, move files, and
  expire CDN URLs on their own schedule. Every hotlinked image
  is a future broken card we cannot detect or repair. A stored
  copy either works or does not; its health is ours to see.
- **Hotlinking is often blocked.** Many hosts serve 403s to
  cross-site referrers, or watermark/ degrade embeds. The
  failure is silent and per-source, the least maintainable
  failure mode there is.
- **Hotlinking leaks and mixes.** http images on an https page
  are blocked as mixed content, and hotlinking sends our
  users' browsers (and IPs) to third-party servers on every
  feed scroll.
- **We already own the whole pipeline.** `ImageProcessor`
  decodes, rotates, resizes to the `cover` preset and
  re-encodes to WebP at a fixed quality; hosted activities
  already store `activities/<uuid>.webp` paths on the s3 disk
  and render them through `ImageProcessor::url()`. Downloaded
  listing images get uniform size, format and weight for free,
  and the render code cannot tell a listing photo from a host
  photo. One pipeline, one place to fix anything, forever.
- **Control is the takedown path.** If a source objects, an
  admin replaces or clears the image in Filament (the form's
  `images` upload already exists) or unpublishes the listing.
  With a hotlink there is nothing to take down but the whole
  listing.
- The cumbersome part is real and accepted: the importer now
  does network I/O, downloads can fail or be slow, and storage
  grows by one WebP (~1600px, quality 82) per listing. The
  failure design below keeps all of that from ever touching
  the listing itself.

**Rejected alternatives, for the record:** hotlinking the
source URL (above); storing the source URL in `images` (worse:
`ImageProcessor::url()` passes full URLs through untouched, so
it would hotlink silently, with no code anywhere admitting
that is what it does); skipping images (the owner's live
feedback on 2026-11-09 was that an imageless listing "is a
problem").

## Importer changes (`ImportListingsCommand`)

Operation order per row is part of the spec, because two
claims depend on it:

1. **Validate** the row (019 rules, plus `image_url` shape if
   present: string, parseable, `https` scheme — an invalid
   `image_url` is an *image* failure, not a row rejection; see
   step 3).
2. **Dedupe check** on (source_url, title, start_time), exactly
   as today. A duplicate is skipped at this point. **No image
   download ever happens for a duplicate** (C-20-12), and a
   duplicate whose existing row has no image is still skipped:
   filling images on existing rows is the backfill command's
   job, not a side effect of re-import.
3. **Image step** (only when `image_url` is present): download
   and store. Any failure here is recorded against the row and
   processing continues to step 4 with no image.
4. **Create** the draft row, with `images` = `[path]` or `[]`,
   and `image_source_url` = the `image_url` or null.

### The image step, precisely

- HTTP GET with a short timeout (10s), redirects followed,
  response body capped at `listings.image_max_bytes` (default
  10 MB, [O-20-5]); an over-cap response is aborted, not
  truncated-and-kept.
- The response's content type must be one of image/jpeg,
  image/png, image/webp, image/gif. Anything else (including
  SVG, and HTML error pages served with a 200) is a failure.
- The bytes then go to `ImageProcessor::store($bytes,
  'activities', 'cover', 's3')`. `ImageProcessor::contents()`
  already accepts a binary string, and Intervention's decode
  throws on bytes that are not really an image — that throw is
  the final validation and is caught as an image failure.
  There is no code path that stores the raw download.
- One attempt, no retries inside the import. A failed image
  can be repaired later by the backfill command (it matches
  rows with empty `images`, failed imports included).

### Reporting

In 019's style: one line per row that needs one, then the
summary. Image outcomes get their own counters so a research
run's image quality is visible at a glance:

```
Row 7 (Bluff Park Run Club): imported, image failed (content type text/html).
Listings import complete: 16 imported, 2 duplicates skipped, 0 rejected. Images: 13 stored, 3 failed.
```

## Attribution

- New nullable column `activities.image_source_url` (string).
  Set only when an image was actually stored from that URL;
  an imageless row has null. It mirrors the existing
  `source_name` / `source_url` provenance pair and keeps the
  `images` array a pure list of storage paths, identical in
  shape to hosted rows.
- Credit rendering (C-20-14): on the detail page, in the
  demoted source block (workstream A3), small print beside the
  source link: "Photo: {source_name}" linked to `source_url`,
  shown only when `image_source_url` is set. The full image
  URL itself stays in the column for provenance and takedown;
  it is not rendered as a link (a bare image file is not a
  useful destination, and rendering it invites the hotlink
  confusion this spec removed).
- Card: no credit on the card (space), the detail page is the
  attribution surface, same as the source link's demoted home.

## Rendering changes

- `FeedService::mapEventToFeedItem`: remove the listing
  carve-out at L745 so `images` ships for listings as for
  hosted events. The event card already renders `images[0]`
  when present and the date-strip placeholder when the array
  is empty; no card markup change is needed beyond the
  workstream A link demotion.
- Listing detail: the placeholder block in
  `listing-detail.blade.php` becomes conditional — when
  `images` is non-empty, render `images[0]` (resolved through
  `ImageProcessor::url()`) in the same aspect-video frame with
  the same date strip over it; when empty, render today's
  placeholder unchanged. One image only on listings in 020;
  the hosted carousel is not imported.

## Backfill: `listings:import-images`

The 18 listings already in the database (17 draft,
1 published, imported 2026-11-08/09) have `images = []` and
would stay imageless under a format-only change. 020 ships a
one-time companion command ([O-20-6] recommends running it):

```
php artisan listings:import-images {path}
```

- Input: a JSON array of `{source_url, title, start_time,
  image_url}` objects — the same dedupe triple the importer
  uses, plus the image. (`04-import-format.md` specifies the
  file.) Researchers produce it from the same source pages;
  the original batch file's rows are the key list.
- Behavior per entry: find the listing by the triple. Not
  found → report `not found`, continue. Found with a non-empty
  `images` → report `skipped, already has image`, and **never
  download** (the existing image wins, whatever it is; a
  human replaces images in Filament, not a batch file). Found
  with empty `images` → run the same image step as the
  importer (same caps, types, processing, failure rules) and
  on success set `images` and `image_source_url`.
- It creates nothing, changes no other field, and cannot
  publish or unpublish anything. Status (draft/published) is
  untouched, so backfilling a draft stays invisible until
  approval, exactly like the original import.
- Report in importer style: matched / filled / skipped /
  not-found / image-failed counts, one line per entry that
  needs one.
- Re-running the same backfill file is a no-op (every matched
  row now has an image and is skipped), which is the
  idempotency claim C-20-15 proves alongside the fill rules.

## Research workflow note

The batch producers (Muse AI research) now capture, per
activity, the **single best image URL**: the source's own
photo of the actual activity or venue, on the organizer's or
venue's own page where possible — not a stock photo, not a
logo, not a social avatar. `04-import-format.md` carries the
instruction and the field rules. If a source has no usable
photo, the field is omitted; an imageless listing is a normal
outcome, not a research failure.

## Rights posture (owner-visible note, not a blocker)

Stated plainly: these photos come from other people's pages.
FunLynk's posture in 020 is the same one 019 took toward the
listings themselves — list with attribution, honor takedown:

- The photo is credited on the detail page next to the source
  link, and the exact source URL is stored on the row.
- The image is used to illustrate a factual listing of the
  organizer's own public event, at reduced size, re-encoded —
  never sold, never presented as FunLynk's own event or photo
  (the "FunLynk does not run this event" notice sits on the
  same page).
- Takedown is fast and total: an admin can clear or replace
  the image in Filament, or unpublish the listing, without a
  deploy. A source that objects to being listed at all is
  handled by 019's unpublish path, unchanged.

This is recorded so the owner sees it at review. It does not
block the build; the locked download-and-store decision above
is what makes the takedown path this simple.
