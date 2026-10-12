# 001 — Timeline performance — Strategic brief

**Status:** APPROVED
**Author:** orchestrator (Augment Agent)
**Date:** 2026-10-11

---

## Goal

A user who opens the app lands on a nearby feed whose first cards are visible in
under 1 second on a phone over 4G, with feed data returned by the server in under
200 ms — whether the area has 100, 1,000, or 10,000 posts.

## Claims

| ID | Capability | Verdict (named binary test) | Blueprint anchor |
|---|---|---|---|
| C-01 | Feed data is fast at scale | `feed:benchmark` command on the 10k-post fixture: p95 of 20 runs of the first feed page < 200 ms on the dev server; output saved in `07-evidence/` | Non-negotiable: the timeline must feel instant |
| C-02 | No per-card database queries | `test_nearby_feed_query_count_is_constant` — rendering 10 items and 50 items runs the same number of queries | same |
| C-03 | Reacting does not reload the feed | `test_reacting_to_post_runs_bounded_queries` — `reactToPost` runs ≤ 5 queries regardless of how many items are loaded | same |
| C-04 | Page shell appears before the feed query finishes | `test_feed_page_renders_shell_with_skeleton` — the initial response contains the skeleton and runs no feed query; items load in a follow-up request | same |
| C-05 | First cards visible < 1 s on a phone | Browser check at 390px with Chrome "Slow 4G" throttling against Laravel Cloud: first card visible < 1 s; recording/trace in `07-evidence/` | same |
| C-06 | Infinite scroll is coherent | `test_feed_pages_have_no_duplicates_or_gaps` — walking every page of the fixture returns each eligible item exactly once, in the decided order | Discovery |
| C-07 | Radius contract holds | `test_feed_respects_post_and_event_radius` — no post outside the post radius, no event outside the event radius, no expired post | Posts / Events radius ([P] → decided in 001-O2) |
| C-08 | No location leakage | `test_feed_snapshot_contains_no_coordinates` — the Livewire snapshot and HTML contain no coordinates of other users, posts, or events | Non-negotiable: location data never leaves its radius contract |

## Blueprint anchors

- [D] The timeline must feel instant; budget [D] 2026-10-11 (`BUILD_PROTOCOL.md` §7).
- [D] Location queries go through the spatial layer (`matanyadaev/laravel-eloquent-spatial`) — no hand-rolled distance math.
- [D] Authorization server-side: the feed is an authenticated, onboarded-user route.
- [D] Pre-pilot relaxation 2026-10-11: atomic cutover allowed, no flag required.
- [D] 001-O1 resolved 2026-10-12: feed order is one merged list, newest first (posts by creation time, events by when posted); distance only filters. C-06 is written to this order.
- [D] 001-O2 resolved 2026-10-12: default radius is 10 km for posts, 50 km for events; slider kept. C-07 tests these defaults.
- [D] 001-O3 resolved 2026-10-12: For-You is audited but not changed in spec 001; it is owned by spec 005.

## Production impact

No users before the pilot. The new feed replaces the old one in a single cutover
(no flag). No schema changes expected; if the audit shows a missing index, it
ships as an additive migration. Rollback: revert the merge.

## Security and privacy classification

- **Actors:** authenticated, onboarded user. Guests never reach the feed.
- **Data touched:** posts, events, reactions, RSVP counts, author/host profiles, the viewer's location.
- **Authorization rules:** route middleware `auth` + `onboarding.complete`; reactions via `PostService::toggleReaction` (existing rules unchanged).
- **Location exposure:** the viewer's point is used only in server-side queries. Cards show place names and distances, never coordinates (C-08).
- **Payment touchpoints:** none.

## Evidence and fixtures

`04-fixtures.json` + a seeder producing three datasets around one city centre:
100, 1,000, and 10,000 posts (plus ~10% as many events). Includes posts inside
and outside each radius, expired posts, converted posts, posts with 0–200
reactions, events with RSVPs and sold-out events, posts with and without images,
and a viewer with no saved location (fallback feed).

## Pre-mortem

Hypotheses from reading the code (unmeasured — the audit confirms or rejects each):

- **Per-card queries (N+1):** post cards lazy-load *all* reactions to count "I'm down"; event cards run `rsvps()->count()` twice; converted posts load their activity.
- **Livewire state:** `NearbyFeed::$items` holds up to 200 models; every interaction (a reaction tap, load more) rebuilds all of them from the database.
- **Blocking first paint:** the feed query runs inside `mount()`, so the page waits for it.
- **Spatial index unused:** `whereDistanceSphere` filters on `ST_DistanceSphere(...)`, which cannot use the GIST index → full table scan as posts grow.
- **Incoherent paging:** SQL orders by distance, then PHP re-sorts each page by recency; plus a `COUNT(*)` per type per page and OFFSET paging.
- **Images:** originals served full-size from S3 with no lazy loading (fix belongs to spec 002; the audit measures the weight).

Risks:
- **Likely failure:** timing verdicts are flaky in CI → only C-01/C-05 measure time, on fixed hardware; CI enforces query counts (C-02, C-03).
- **Assumption:** the dev server's database performs like Laravel Cloud's. Mitigation: C-05 runs against Laravel Cloud.
- **Unknown:** whether production runs Octane and where S3/DB sit relative to the app. The audit records it.

## Non-goals

Card visual redesign and the image pipeline (spec 002) · detail pages (003) ·
group chat (004) · ranking, friends, and following (005) · map view · Meilisearch
search. New features of any kind.

## Approval gate

- [x] Orchestrator verdicts written (claims table complete)
- [x] Builder has reviewed and added risks to the pre-mortem
- [x] Governing [D]/[P]/[O] items linked; [O] stop conditions stated
- [x] Production impact + rollback plan stated
- [x] Security and privacy classification complete
- [x] Product owner approved — **approver:** product owner · **date:** 2026-10-12
