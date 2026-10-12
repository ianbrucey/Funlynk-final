# 001 — Timeline performance — Implementation plan

**Status:** APPROVED
**Date:** 2026-10-12
**Brief:** `00-brief.md` (APPROVED) · **Contract:** `03-contract.md` (APPROVED) ·
**Schema delta:** `02-schema-delta.md` (none) · **Mockup:** `05-ui-mockup.html` (APPROVED)

> Atomic tickets, backend-out: service → component → views → integration →
> evidence/freeze. No migration ticket — `02-schema-delta.md` found none needed.
> Each ticket is independently testable. A ticket is done only when its verdict
> is green in isolation AND the standing gates pass (`php artisan test`,
> `vendor/bin/pint --test`).

---

## Ticket 1 — `FeedService`: `ST_DWithin` + cursor pagination + plain-array `FeedItem`

**Files:** `app/Services/FeedService.php`
**Inputs:** `03-contract.md` → Domain services, Location; `04-fixtures.json`
**Dependencies:** none
**Forbidden edits:** `app/Services/PostService.php`, `RecommendationEngine.php` (not touched by this spec), `getForYouFeed`/`getMapData` bodies (audited only, 001-O3 — may share the new `ST_DWithin` helper but their output shape is unchanged)

### Work
- [ ] Add a private `nearby(Builder $query, string $column, Point $point, int $meters)` helper issuing `whereRaw('ST_DWithin(...)')`, replacing `whereDistanceSphere` as the *filter*; keep `orderByDistanceSphere` only for the final distance sort where still needed.
- [ ] Rewrite `getNearbyFeed()` to: fixed 10 km post radius (`self::POST_RADIUS_KM`, not a parameter), `$eventRadiusKm` clamped `[10,50]`, single merged result ordered newest-first per 001-O1, opaque base64 cursor (encodes last item's `created_at`+`id`+`type`) instead of `$page`/OFFSET, no `COUNT(*)` queries.
- [ ] Map each `Post`/`Activity` to the `FeedItem` plain-array shape from `03-contract.md` (no `['data' => Model]`). Pull `reaction_count` from the existing column, `viewer_reacted` from one preloaded reaction-id set per page, `rsvp_count`/`spots_remaining` from one `withCount('rsvps')` per page (not per card).
- [ ] Malformed/unparseable cursor → treated as null (restart from newest), never throws.
- [ ] Leave `getFallbackNearbyFeed()` behavior equivalent but returning the same `FeedItem` shape and `cursor`/`hasMore` contract (still latest()-based, still used when the viewer has no coordinates).

### Verdict
- `test_feed_respects_post_and_event_radius` (C-07): no post beyond 10 km, no event beyond `eventRadiusKm`, no expired post, against `04-fixtures.json`'s radius/expiry adversarial cases.
- `test_feed_pages_have_no_duplicates_or_gaps` (C-06): walking every cursor page of a seeded dataset returns each eligible item exactly once, newest-first.
- `test_feed_snapshot_contains_no_coordinates` (C-08, service-level slice): the returned array tree contains no `lat`/`lng`/`location_coordinates` keys.
- `php artisan test` green · `vendor/bin/pint --test` clean.

## Ticket 2 — `NearbyFeed` Livewire component: lazy load, cursor, event radius

**Files:** `app/Livewire/Discovery/NearbyFeed.php`
**Inputs:** `03-contract.md` → Livewire component table; Ticket 1's `FeedService` signature
**Dependencies:** Ticket 1
**Forbidden edits:** `app/Livewire/Discovery/ForYouFeed.php` (001-O3, not touched), `app/Services/PostService.php`

### Work
- [ ] Add `#[Lazy]` to the class; add `placeholder()` returning the skeleton view from `05-ui-mockup.html`'s loading state (new partial, e.g. `resources/views/livewire/discovery/nearby-feed-skeleton.blade.php`).
- [ ] Replace `$radius`/`$page`/`$totalItems` with `$cursor` (`?string`) and `$eventRadiusKm` (`int`, default 50); rename `updatedRadius()` → `updatedEventRadiusKm()`, clamp `[10,50]`.
- [ ] `mount()` no longer calls `loadItems()` (moved to the lazy `placeholder`→render cycle per Livewire's lazy-loading lifecycle); confirm via the official docs (https://livewire.laravel.com/docs/3.x/lazy) that the first full render is the right place to call it, not `mount()`.
- [ ] `loadItems()`/`loadMore()` pass `$this->cursor` instead of page/offset; `resetFeed()` resets `$cursor` to null.
- [ ] `reactToPost()` stays within its existing ≤5-query budget (`PostService::toggleReaction` + the two `dispatch()` calls) — no new DB read added.

### Verdict
- `test_nearby_feed_query_count_is_constant` (C-02): 10 items vs 50 items run the same query count.
- `test_reacting_to_post_runs_bounded_queries` (C-03): `reactToPost` ≤ 5 queries regardless of loaded item count.
- `test_feed_page_renders_shell_with_skeleton` (C-04): initial response contains the skeleton, runs no feed query; items load on the follow-up Livewire request.
- `php artisan test` green · `vendor/bin/pint --test` clean.

## Ticket 3 — Card components consume the `FeedItem` array, not a model

**Files:** `resources/views/components/post-card-compact.blade.php`, `resources/views/components/conversion-badge.blade.php`, `resources/views/components/converted-post-overlay.blade.php`; new `resources/views/components/event-card-compact.blade.php` (extracted from the inline event-card markup currently in `nearby-feed.blade.php`)
**Inputs:** `03-contract.md` → `FeedItem` shape; `04-fixtures.json` (soft-eligible, converted, sold-out, no-image cases)
**Dependencies:** Ticket 1 (defines the exact array keys)
**Forbidden edits:** `resources/views/components/post-card.blade.php` (the non-compact detail-page card — out of scope, Spec 003)

### Work
- [ ] `post-card-compact`: accept `array $post` (`@props(['post'])` stays, but every `$post->x` becomes `$post['x']`); reaction state comes from `reaction_count`/`viewer_reacted`, not `$post->reactions->where(...)`; author block uses `author_name`/`author_image_url` directly — no `$post->author` relation access.
- [ ] `conversion-badge`/`converted-post-overlay`: accept the array; use `conversion_eligible`/`converted_activity_id`/`status` keys instead of calling `isEligibleForConversion()`/`hasReachedDismissLimit()`/`convertedActivity` on a model.
- [ ] Extract the inline event-card block (lines ~117–228 of today's `nearby-feed.blade.php`) into `x-event-card-compact` taking the event `FeedItem` array; use `spots_remaining`/`rsvp_count` directly — delete both inline `rsvps()->count()` calls.
- [ ] No-image event (`04-fixtures.json` → `no_image_event`) renders a placeholder block, not a broken `<img>`.

### Verdict
- Pest Blade/Livewire test rendering each `04-fixtures.json` feed-page item through the real components with no exceptions and no DB queries beyond what Ticket 1/2 already budget.
- `test_feed_snapshot_contains_no_coordinates` (C-08, full page): rendered HTML contains no coordinates.
- `php artisan test` green · `vendor/bin/pint --test` clean · `npm run build`.

## Ticket 4 — `nearby-feed.blade.php`: skeleton, Event radius slider, cursor load-more

**Files:** `resources/views/livewire/discovery/nearby-feed.blade.php`, new skeleton partial from Ticket 2
**Inputs:** `05-ui-mockup.html` (APPROVED), `05-ui.md`
**Dependencies:** Tickets 2, 3

### Work
- [ ] Relabel the "Distance" slider to "Event radius", range `10–50`, bound to `$eventRadiusKm`; remove the post-radius control entirely (posts are fixed, not shown as a slider per the approved mockup).
- [ ] Replace the `@forelse` branch's inline event markup with `<x-event-card-compact>`; post branch keeps `<x-post-card-compact>` (now array-driven per Ticket 3).
- [ ] Footer copy "Showing X of Y items" → "Showing X items" (no stable `total` under keyset pagination, per `03-contract.md`).
- [ ] `IntersectionObserver` "Load more" trigger and the 200-item cap message are unchanged in behavior.

### Verdict
- Manual pass against every `05-ui-mockup.html` state (default, loading, empty, load-more) at 390px and desktop.
- `php artisan test` green · `vendor/bin/pint --test` clean · `npm run build`.

## Ticket 5 — Evidence and freeze

**Files:** `07-evidence/`, `08-retrospective.md`, `decisions.md`, `docs/UI_STANDARDS.md`, `dev-journal/`
**Inputs:** all prior tickets' verdict output

### Work
- [ ] Re-run `feed:benchmark` on the 10k-post fixture, 20 iterations: confirm p95 < 200 ms (C-01); save output to `07-evidence/`.
- [ ] Browser check at 390px, Chrome "Slow 4G" throttling (C-05) — against a Laravel Cloud/staging deploy per the brief's unresolved pre-mortem risk; if no staging deploy exists yet, record that gap explicitly rather than guessing a pass.
- [ ] Add the skeleton-loading pattern (`#[Lazy]` + `placeholder()`) to `docs/UI_STANDARDS.md` §5, per `05-ui.md`'s "New patterns proposed".
- [ ] Record final build decisions in `decisions.md`; write `08-retrospective.md`.
- [ ] Dev journal: progress entry; any bug fixes found during build go in `dev-journal/bug-fixes/` with their recurrence guard.

### Verdict
- Post-merge smoke test: login → feed → post → event → checkout.
- All 8 claims (C-01…C-08) have a green, named verdict recorded in `07-evidence/`.
- All gates green on `main`.
