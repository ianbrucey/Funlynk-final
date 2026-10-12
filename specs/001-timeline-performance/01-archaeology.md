# 001 — Timeline performance — Repository archaeology

**Status:** APPROVED
**Author:** orchestrator (Augment Agent)
**Date:** 2026-10-12
**Brief:** `00-brief.md` (APPROVED 2026-10-12)

> Measured, not guessed. All numbers below came from `php artisan feed:benchmark`
> (new command, `app/Console/Commands/FeedBenchmarkCommand.php`) run against the
> local Postgres 18 + PostGIS 3.6 dev database (`funlynk_test`), seeding 100,
> 1,000, and 10,000 posts (+10% as many events) inside a single rolled-back
> transaction per scale — nothing it creates is kept. Full output:
> `07-evidence/audit-2026-10-12-local.txt`.
>
> The fixture spreads posts/events across 5 distant metro areas (20% near the
> viewer, 80% in 4 other cities thousands of km away) so the radius filter has
> realistic selectivity. An earlier single-city run clustered everything near
> the viewer, making almost every row "nearby" and hiding the real cost of an
> unselective query — that run is not used below.

---

## Measured results

| Scale (posts) | Items returned | Total eligible (near viewer) | Feed p95 (ms) | Feed queries | Card-render queries | Livewire-rehydration queries |
|---|---|---|---|---|---|---|
| 100 | 16 | 18 | 12.7 | 6 | 17 | 16 |
| 1,000 | 30 | 186 | 17.4 | 6 | 45 | 30 |
| 10,000 | 30 | 1,866 | 55.8 | 6 | 45 | 30 |

Columns:
- **Feed queries** — queries run by `FeedService::getNearbyFeed()` itself (count + fetch + eager-loaded author, × 2 for posts and events). **Constant at every scale — the service layer has no N+1.**
- **Card-render queries** — additional queries the current Blade view triggers per page, replicated exactly as `post-card-compact.blade.php` and the event card compute them today (`$post->reactions->where(...)->count()` per post; `$event->rsvps()->count()` **twice** per event). Scales with item count, not with dataset size (card count is capped at `perPage`).
- **Livewire-rehydration queries** — re-fetching every returned item by primary key, which is what Livewire does to every model held in a public property on each subsequent request. 1:1 with item count; at the current 200-item in-memory cap, a single reaction tap near that cap would cost ~200 extra queries.

### EXPLAIN comparison — current filter vs. an index-friendly alternative (scale 10,000, 10,767 active posts total)

| Predicate | Plan | Rows scanned | Execution time |
|---|---|---|---|
| `whereDistanceSphere(...) <= 10000` (current code) | `Bitmap Heap Scan on posts` using the `status` index, `ST_DistanceSphere(...)` evaluated as a `Filter` on every active row | 10,767 (all active posts, globally) | 18.1 ms |
| `ST_DWithin(location_coordinates, $point, 10000)` (candidate fix, same `idx_posts_location` GIST index that already exists) | `Index Scan using idx_posts_location`, bounding-box `Index Cond`, `ST_DWithin` only rechecks the box | 1,700 (posts near the viewer) | 7.8 ms |

**`ST_DWithin` is ~2.3× faster at this scale using the index that already exists — no migration needed, only a query change.** The gap is structural, not incidental: `whereDistanceSphere`'s cost is O(all active posts worldwide); `ST_DWithin`'s cost is O(posts whose bounding box overlaps the viewer's radius). The synthetic fixture only has 5 metro clusters; real data with thousands of local areas would widen this gap further as the total post count grows — `whereDistanceSphere` keeps scanning every active post everywhere, `ST_DWithin` keeps scanning only what's actually nearby.

Full EXPLAIN output for all three scales, both predicates, and the mirrored events query are in `07-evidence/audit-2026-10-12-local.txt`.

## Findings

| Thing | Location | Verdict | Constraint / note |
|---|---|---|---|
| `FeedService::queryNearbyPosts` / `queryNearbyEvents` | `app/Services/FeedService.php:212-312` | EXTEND | Query-count is flat (good). The `whereDistanceSphere` filter compiles to `whereRaw('ST_DistanceSphere(...) <= ?')` — confirmed by `vendor/matanyadaev/laravel-eloquent-spatial` (v4.5.0) source: the package has no `ST_DWithin`-based scope, so this filter can never use the `idx_posts_location` / `idx_activities_location` GIST index (it wraps the column in a function). It falls back to scanning every row matching `status`, then filters by distance in memory. **Confirmed, measured:** at 10k posts (10,767 active, realistic multi-metro spread) this costs 18.1 ms scanning all 10,767 active rows worldwide to return 30 nearby ones. A raw `ST_DWithin(...)` predicate against the *same, already-existing* GIST index returns the same 30 rows in 7.8 ms by scanning only the ~1,700 posts whose bounding box overlaps the radius — no migration required, only a query change. Grows with the count of active posts globally, not with the radius — the core fix target for the plan phase. |
| `post-card-compact.blade.php:124-125` (reaction count) | `resources/views/components/post-card-compact.blade.php` | CONFLICT | Lazy-loads **all** of a post's `reactions` to count `im_down` and check whether the viewer reacted, even though `Post.reaction_count` is already a maintained cached column (`app/Models/Post.php` casts + `scopeEligibleForConversion` already trusts it). One query per post card; the cached column is right there and unused for the count. The "did I react" check still needs a query unless the viewer's own reactions are preloaded for the page. |
| Event card RSVP display | `resources/views/livewire/discovery/nearby-feed.blade.php:222,224` | CONFLICT | Calls `$item['data']->rsvps()->count()` **twice** in the same card (once for "spots left", once for "going"). Not even cached within the request. |
| `NearbyFeed::$items` | `app/Livewire/Discovery/NearbyFeed.php:28` | CONFLICT | Public array holding up to 200 `['type' => ..., 'data' => Eloquent model]` entries. Livewire dehydrates/rehydrates every model in a public property on every request (confirmed: re-`find()`-ing each returned item costs one query per item, measured above). A single reaction tap or `loadMore` rebuilds every already-loaded model from the database. |
| `NearbyFeed::mount()` | `app/Livewire/Discovery/NearbyFeed.php:32-39` | CONFLICT | Calls `loadItems()` synchronously inside `mount()`. The whole page waits for the feed query (plus N+1 above) before any HTML is sent — no skeleton, no deferred load. Livewire v3 has lazy-loading support (`#[Lazy]` / `wire:init`); needs the version-matched doc check in the plan phase before use. |
| `FeedService::getNearbyFeed` merge/sort | `app/Services/FeedService.php:37-83` | CONFLICT | Posts and events are queried as **two independently paginated** result sets (own `LIMIT`/`OFFSET`, own `COUNT(*)`), then merged and re-sorted by recency in PHP *per page*. Page 2 can legally contain items newer than page 1. Two `COUNT(*)` queries run per page per content type. This is what 001-O1 (resolved: one merged, newest-first list) replaces. |
| `NearbyFeed` default radius | `app/Livewire/Discovery/NearbyFeed.php:15` | CONFLICT | Defaults to 30 km for both posts and events. `FeedService::getNearbyFeed()`'s own signature already defaults to 10 km — the Livewire component overrides it. 001-O2 (resolved: posts 10 km / events 50 km) requires the component and view to stop sharing one radius control for two different contracts. |
| Images (`Storage::url($item['data']->images[0])`) | `resources/views/livewire/discovery/nearby-feed.blade.php:165-171`, `post-card-compact.blade.php` | NEW (out of scope) | Full-size originals, no `loading="lazy"`, no responsive sizes. Confirmed by inspection; not re-measured here — fix belongs to spec 002 per the brief's non-goals. |
| `Post.reaction_count`, `Activity` RSVP count | `app/Models/Post.php`, `app/Models/Activity.php` | REUSE | Already-maintained counters/relations; the fix path is to use what exists (`reaction_count`, and a single `withCount('rsvps')` at the service layer) rather than add new columns. |
| `feed:benchmark` command | `app/Console/Commands/FeedBenchmarkCommand.php` | NEW | Built for this audit (C-01's verdict mechanism). Seeds inside `DB::transaction()` + `rollBack()` — never commits. Re-run any time with `php artisan feed:benchmark --scales=100,1000,10000`. |

## Architectural doors this feature must use

- Location queries go through `matanyadaev/laravel-eloquent-spatial` scopes only — no hand-rolled distance math. (The index-usage problem must be fixed by changing *which* spatial scope/predicate is used, not by bypassing the package.)
- Reactions still go through `PostService::toggleReaction` (unchanged) — the fix here is read-path only (what the feed and cards query), not the write path.
- The feed route stays behind `auth` + `onboarding.complete` middleware — unchanged.

## Files to touch (anticipated — confirmed at ticket time)

| File | Action | Reason |
|---|---|---|
| `app/Services/FeedService.php` | edit | Replace the per-type paginated-then-PHP-merged query with one cursor-paginated, index-friendly query; fix radius defaults (10 km posts / 50 km events, 001-O2). |
| `app/Livewire/Discovery/NearbyFeed.php` | edit | Stop holding full item payloads across requests; lazy-load first render; bound per-interaction query cost. |
| `resources/views/livewire/discovery/nearby-feed.blade.php`, `resources/views/components/post-card-compact.blade.php` | edit | Use `reaction_count` instead of lazy-loading reactions; compute RSVP counts once per event, not twice. |

**No migration is anticipated.** `idx_posts_location` and `idx_activities_location` (both `GIST`, created with the tables) already support `ST_DWithin` — confirmed by `EXPLAIN` above. `02-schema-delta.md` should record this as N/A unless the plan phase finds otherwise.

## Protected files

- `app/Services/PostService.php` (reaction write path) — out of scope, not touched.
- `app/Policies/*` — authorization unchanged.
- Migrations belonging to other specs.

## What production does here today

- Route: `GET /feed/nearby` (`feed.nearby`) → `NearbyFeed` Livewire full-page component; `/dashboard` redirects here. Confirm `auth` + onboarding middleware group in `routes/web.php` at ticket time.
- No queue jobs or scheduled tasks touch this read path today.
- No feature flag currently gates the feed; this is a direct, atomic cutover per the pre-pilot relaxation.

## Baseline

- Tests: `php artisan test` — 201 passed, 2 skipped (0 failed), 56.25s, run 2026-10-12.
- Pint: `vendor/bin/pint` on the new file — clean.
- PHPStan: not yet configured project-wide (per `BUILD_PROTOCOL.md` — first tooling ticket, [P]).
- Package versions (from `composer show`): `laravel/framework` 12.40.2, `livewire/livewire` 3.6.4, `matanyadaev/laravel-eloquent-spatial` 4.5.0, Postgres 18 / PostGIS 3.6 (local dev; confirm production versions before the plan is finalized).

## Open items carried into the plan phase

- **C-05 (first cards visible < 1 s on Laravel Cloud, Slow 4G)** — not measurable from this local session; needs a staging/Cloud deploy and a throttled browser check before Freeze.
- **Index-usage fix — direction confirmed, not yet implemented.** Use `ST_DWithin` (via `whereRaw`, since the installed `matanyadaev/laravel-eloquent-spatial` v4.5.0 has no first-class scope for it) as the radius filter, keeping `orderByDistanceSphere` for the final top-N sort. Measured 2.3× faster at 10k posts against the existing GIST index; the plan phase still needs to re-check the package's own docs/changelog for a newer version with native `ST_DWithin` support before deciding between a raw `whereRaw` and a package upgrade (version change requires owner approval per the package-management rule).
- **Octane / production topology** — still unconfirmed whether production runs Octane and where the DB/S3 sit relative to the app (brief's pre-mortem risk, unresolved).
