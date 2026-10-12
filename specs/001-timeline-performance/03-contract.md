# 001 — Timeline performance — Contract

**Status:** IN REVIEW
**Date:** 2026-10-12
**Archaeology:** `01-archaeology.md` · **Schema delta:** `02-schema-delta.md` (none)

> The contract is the buildable truth. Views and components may request only
> what this contract exposes. If implementation reveals a contract error, stop:
> update and reapprove this artifact before continuing.

---

## Contract decision flagged for review

**Radius splits in two.** Today one slider value is passed as the *same*
radius to both posts and events (`NearbyFeed::$radius`, default 30 km —
confirmed in archaeology as a bug against the service's own 10 km default).
001-O2 decided posts = 10 km, events = 50 km, "keep the slider." This contract
resolves that by making **posts a fixed 10 km, not user-adjustable** (matching
the blueprint's "~5–10 km" framing of posts as spontaneous/local), and the
slider becomes an **events-only** radius control (range 10–50 km, default
50 km). This is a visible UI behavior change (label changes from "Radius" to
"Event radius") — flagging for explicit sign-off before `05-ui-mockup.html`.

## Domain services

| Method | Inputs | Outputs | Errors |
|---|---|---|---|
| `FeedService::getNearbyFeed(User $user, ?string $cursor = null, string $contentType = 'all', string $timeFilter = 'all', string $searchQuery = '', int $eventRadiusKm = 50, int $perPage = 30): array` | Viewer (must have `location_coordinates`, else `getFallbackNearbyFeed` as today), opaque `$cursor` (null = first page), `$eventRadiusKm` clamped `[10,50]` server-side regardless of client input | `['items' => array<FeedItem>, 'nextCursor' => ?string, 'hasMore' => bool]` — **no `total`/`page`** (keyset pagination has no stable total; dropping it also removes the duplicated `COUNT(*)` queries found in archaeology) | Invalid cursor → treated as null (start over), never a 500 |
| `FeedService::getMapData(...)` | unchanged | unchanged | unchanged — out of scope, not touched |
| `FeedService::getForYouFeed(...)` | unchanged | unchanged | audited, not changed (001-O3) |

**Posts radius is a constant** (`FeedService::POST_RADIUS_KM = 10`), not a parameter — callers cannot widen it.

### `FeedItem` shape (plain array, never an Eloquent model)

Returned items are plain arrays, not `['data' => Model]`. This is the fix for
the measured Livewire rehydration cost (archaeology: 1 query per loaded item
per interaction) — Livewire dehydrates plain arrays as JSON with no DB access.

Common keys: `type` (`'post'|'event'`), `id`, `title`, `author_name`,
`author_image_url` (nullable), `location_name`, `distance_km` (float, rounded
to 1 decimal — **never raw coordinates**, C-08), `created_at` (ISO string).

Post-only keys: `description`, `tags`, `time_hint`, `expires_at`, `status`,
`reaction_count` (from the existing cached column — no lazy-loaded
`reactions` relation), `viewer_reacted` (bool, from one preloaded set per
page, not a per-card query), `conversion_eligible` (bool, computed server-side
from `reaction_count`/`conversion_dismiss_count` — replaces the view calling
`isEligibleForConversion()`/`hasReachedDismissLimit()` on a hydrated model),
`converted_activity_id` (nullable).

Event-only keys: `images` (array), `start_time`, `rsvp_count` (from a single
`withCount('rsvps')` at fetch time — **not** called per card, and not called
twice), `max_attendees` (nullable), `spots_remaining` (nullable, computed
server-side).

> Exact field list is the floor for the ticket, not exhaustive — confirmed
> against the real Blade templates (`post-card-compact.blade.php`,
> `nearby-feed.blade.php`) when the ticket is written; no new field may expose
> coordinates, email, or other users' private data.

## Routes

| Method | Path | Component | Auth | Change |
|---|---|---|---|---|
| GET | `/feed/nearby` | `App\Livewire\Discovery\NearbyFeed` | `auth` + `onboarding.complete` | Component internals change; route/middleware unchanged |
| GET | `/feed/for-you` | `App\Livewire\Discovery\ForYouFeed` | `auth` + `onboarding.complete` | Unchanged (audited only, 001-O3) |

## Livewire component — `NearbyFeed`

| Property | Type | Notes |
|---|---|---|
| `$items` | `array<FeedItem>` (plain arrays) | Replaces today's `['type'=>, 'data'=>Model]` mix |
| `$cursor` | `?string` | Replaces `$page`; opaque, server-generated, never decoded/trusted beyond `FeedService` |
| `$eventRadiusKm` | `int`, `10..50`, default `50` | Replaces `$radius`; posts radius is server-fixed |
| `$searchQuery`, `$contentType`, `$timeFilter` | unchanged | unchanged behavior |
| `$hasMore` | `bool` | unchanged meaning |

| Action | Query budget | Notes |
|---|---|---|
| `mount()` | 0 queries on the initial response | Component is `#[Lazy]` (Livewire 3.x, https://livewire.laravel.com/docs/3.x/lazy); `placeholder()` renders the skeleton grid. The feed query runs on Livewire's automatic follow-up request, not before first paint (C-04). |
| `loadItems()` / initial lazy load | ≤ 8 queries, flat regardless of scale or page | 1 cursor/union query, ≤ 2 bulk model hydration queries (posts, events) with eager-loaded author/host, 1 preloaded viewer-reactions set, 1 `withCount('rsvps')` (folded into the event hydration query, not separate) |
| `loadMore()` | same ≤ 8-query budget | Must not re-fetch or re-touch already-loaded items |
| `reactToPost()` | ≤ 3 queries | `PostService::toggleReaction` (unchanged) + updates only the single affected item in `$items` (array mutation, no model reload) |
| `search()`, `updatedEventRadiusKm()`, `updatedContentType()`, `updatedTimeFilter()`, `clearSearch()` | same reset-and-reload budget as today | Reset `$cursor` to null |

Events emitted/listened: unchanged (`react-to-post`, `post-reacted`,
`open-conversion-modal`, `banner-dismissed`).

## Validation

| Input | Rules | Invalid → error |
|---|---|---|
| `eventRadiusKm` | integer, clamp to `[10,50]` server-side | Out-of-range silently clamps, never a validation error (it's a slider, not a form) |
| `searchQuery` | string, max 200 chars (existing behavior, confirm at ticket time) | Over-length truncated, not rejected |
| `cursor` | opaque string or null; parsed server-side with a `try/catch` | Unparseable cursor → treated as null (restart from the newest item), never a 500 |

## Authorization

| Operation | Actor | Rule | Denied behavior |
|---|---|---|---|
| View nearby feed | authenticated, onboarded user | route middleware `auth` + `onboarding.complete` (unchanged) | redirect to login / onboarding |
| React to a post | authenticated user | `PostService::toggleReaction` (unchanged, not touched by this spec) | unchanged |

## Location

- **Coordinates in:** viewer's `location_coordinates` (`Point`), read server-side only, never from client input.
- **Radius applied:** `ST_DWithin(location_coordinates, $point, $meters)` via `whereRaw` (the installed `matanyadaev/laravel-eloquent-spatial` v4.5.0 has no native `ST_DWithin` scope — confirmed in archaeology). Posts: fixed 10 km. Events: 10–50 km, slider-controlled.
- **Coordinates out:** never. Cards receive `distance_km` (rounded) and `location_name` only — enforced by the `FeedItem` shape above (C-08).

## External integrations

| Integration | Failure behavior |
|---|---|
| Meilisearch / Scout | Not touched by this spec — `searchQuery` keeps using the existing `ILIKE` path in `FeedService`, unchanged. |
| Stripe | Not touched. |

## Approval gate

- [ ] Product owner approved the radius-split decision above — **approver:** \<name\> · **date:** \<YYYY-MM-DD\>
