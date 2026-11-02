# READ AND DELETE — feat/events-public-seo

**Reviewer: read this, review the branch, then delete this file.**
It explains what changed, why, the decisions taken, and exactly how the
changes were verified. Nothing here is merged or deployed; this branch
is the whole deliverable.

---

## TL;DR

The SEO recon that prompted this work was run against a **stale
checkout** (`feat/000-suite-repair`, based on a main that was 109
commits behind GitHub). On current `main` (`b29a7d6`) the spec-000 K5
route restoration had **already been done** by later work:
`GET /e/{activity:slug}` named `events.public` → `PublicEventView`
exists, and the four `PublicEventViewTest` tests pass. So this branch
does **not** re-create the route. What was actually still broken, and
is fixed here:

1. `social-meta.blade.php` read the removed `$activity->price`
   attribute → paid events were emitted as `isAccessibleForFree` with
   **no offers block** in the JSON-LD. Fixed to `price_cents`.
2. The meta/OG/JSON-LD block rendered inside `<body>` (top of the
   Livewire view fragment). It now renders inside `<head>` via a
   `@stack('meta')`.
3. **Privacy:** non-public events were world-readable by URL on both
   event pages (the `ActivityDetail` policy check only ran for
   logged-in users; `PublicEventView` had no gate at all). Guests now
   get 404 on both URLs.
4. The legacy UUID URL (`/events/{uuid}`) served a duplicate,
   un-titled page to guests. Guests now get a **301** to the canonical
   slug URL; signed-in users keep the in-app page, which now has a
   per-page `<title>`, meta description, and canonical link.

Baseline vs branch test delta: **zero regressions** (see Verification).

---

## File-by-file changes

### `resources/views/components/social-meta.blade.php`
- Offers condition/pricing: `($activity->price_cents ?? 0) > 0`,
  `'price' => number_format($activity->price_cents / 100, 2, '.', '')`,
  `'priceCurrency' => $activity->currency ?: 'USD'`. (Previously:
  `$activity->price`, which no longer exists — Eloquent returned null,
  so the paid branch never fired.)
- Added `<link rel="canonical">` pointing at `events.public` (slug).
- Fallback OG/Twitter image pointed at `images/default-event-cover.jpg`,
  which does not exist in `public/images/`; now uses the existing
  `images/fl-logo-main.png`.
- Null guards: `event:end_time` meta only when an end time exists;
  geo metas and the JSON-LD `geo` block only when
  `location_coordinates` is present (JSON-LD `location` is now built
  without `geo`, which is added conditionally alongside `address`).

### `resources/views/layouts/galaxy.blade.php` (PublicEventView's layout)
- Added `@stack('meta')` in `<head>` after `<title>`.

### `resources/views/livewire/public-event-view.blade.php`
- `<x-social-meta>` is now wrapped in `@pushOnce('meta') … @endPushOnce`
  so it lands in the layout's head stack instead of the body.

### `app/Http/Middleware/RedirectGuestsToPublicEvent.php` — **new**
- Alias `events.canonical`, added to the `events.show` route after
  `capture.intent` (so referral capture on the inbound hit still runs).
- Guest + public event → `301` to `route('events.public', slug)`,
  preserving the query string (Symfony normalizes parameter order).
- Guest + non-public event → `abort(404)`.
- Signed-in users pass through untouched.
- Why middleware instead of a redirect in `ActivityDetail::mount()`:
  Livewire downgrades mount-returned redirects to **302** (verified
  empirically — a mount version of this change produced 302s and was
  replaced). The middleware produces a true 301 and runs before the
  component exists.

### `bootstrap/app.php`
- Registered the `events.canonical` middleware alias.

### `routes/web.php`
- `events.show` middleware: `['web', 'capture.intent', 'events.canonical', 'throttle:60,1']`.

### `app/Livewire/Activities/ActivityDetail.php`
- Privacy gate: non-public + guest → `abort(404)` (was: policy check
  only when `auth()->check()`, so guests sailed through). Signed-in
  path unchanged (`authorize('view', …)` → 403 for unauthorized, as
  `EventNeedsTest` expects). Comment in code notes the middleware
  answers guests first on the HTTP route; this is the same guard for
  direct mounts.
- `render()` passes a per-page layout title
  (`"{event title} - {app name}"`) — event pages in the app no longer
  all share the generic `<title>FUNLYNK</title>`.
- `copyPublicLink()` now copies the canonical `events.public` slug URL
  instead of the UUID app URL. (It is labelled "Public link" in the UI;
  flagging explicitly in case you disagree — one-line revert.)

### `app/Livewire/PublicEventView.php`
- `mount()` privacy gate: non-public activity → `abort(404)` unless
  the signed-in user `can('view', $activity)` (host, per
  `ActivityPolicy`). Previously there was **no** visibility check on
  the public page at all.

### `resources/views/layouts/app.blade.php`
- Added `@stack('meta')` in `<head>` after `<title>`.

### `resources/views/livewire/activities/activity-detail.blade.php`
- `@pushOnce('meta')` block: meta description (160-char limit of the
  event description) + canonical link to the slug URL.

### `tests/Feature/EventPublicSeoTest.php` — **new, 10 tests**
- Guest GET slug page (paid event): 200; `<title>` contains the event
  title; meta description in `<head>`; canonical + `og:url` equal the
  slug URL; JSON-LD present **in `<head>`**, parses, and its
  `offers.price` is `"25.00"` for `price_cents = 2500`,
  `priceCurrency` USD, offer URL = slug URL; share buttons render.
- Free event JSON-LD: `isAccessibleForFree: true`, no `offers` key.
- Guest GET non-public event → 404 on **both** `/e/{slug}` and
  `/events/{uuid}`, without the event title in the response.
- Host still gets 200 for their non-public event on both pages.
- Guest GET `/events/{uuid}` (public) → **301** with `Location` = slug
  URL; referral/UTM parameters survive the redirect.
- Signed-in user on `/events/{uuid}` → 200 with per-page title,
  description, and canonical in `<head>`.
- Share actions: `Livewire::test(...)->call('share', …)` on both
  `PublicEventView` and `ActivityDetail` dispatch
  `share-url-generated` without throwing (this chain previously died
  on the missing route), record the `social_shares` row, and
  `SocialShareService::generateShareUrl()` returns a `/e/{slug}` URL.

---

## Decisions made (and the alternatives)

- **Canonical URL = `/e/{slug}`** (`events.public`). That is the route
  the K5 contract named and current main already uses for share URLs;
  inventing a second slug space under `/events/` was rejected.
- **UUID URLs: 301 for guests, untouched for signed-in users.** A
  blanket 301 would have broken the in-app page (RSVP management,
  host tools) that members reach via `events.show` links in feeds,
  emails, and notifications. The app page additionally carries a
  canonical link to the slug URL.
- **Non-public denial is 404, not 403**, on guest paths: a 403
  confirms the event exists. Signed-in unauthorized users keep the
  existing policy-driven 403 on the app page.
- **Meta stack lives in both layouts** (`galaxy` for the public page,
  `app` for the in-app page) with the same name, `meta`, so future
  pages push head content the same way everywhere.
- **Slug regeneration on title edit** (existing `Activity::boot()`
  behavior) is **not** addressed here — old slugs will 404 after a
  rename. Pre-existing behavior, noted for the sitemap follow-on.

## Deliberately NOT done (follow-ons from the recon plan)

- No `sitemap.xml` (recon item 3).
- No `llms.txt`, no robots.txt changes (recon item 5, apart from the
  privacy gate itself).
- No Google Search Console verification (needs Ian, ~15 min).
- Posts remain unindexed by design (auth-walled, 24–48 h expiry);
  no post→event redirect was added.
- No changes to any other spec-000 cluster (K1–K4, K6–K12 untouched).

---

## Verification — exact steps and results

> ⚠️ **Running the suite here requires the DB password.** `phpunit.xml`
> pins `DB_PASSWORD` to empty, and every test fails with
> `fe_sendauth: no password supplied` unless you supply it:
> `DB_PASSWORD=$(grep "^DB_PASSWORD=" .env | cut -d= -f2-) php vendor/bin/pest`
> (First baseline attempt without it: 371 failed / 0 assertions — an
> environment artifact, not code.)

### Baseline (main @ b29a7d6, before branching)
```
DB_PASSWORD=… php vendor/bin/pest
Tests:    2 skipped, 369 passed (1263 assertions)   Duration: 88.41s
```
The suite is fully green on current main — the spec-000-era failure
clusters are gone (later PRs fixed them), including all 4
`PublicEventViewTest` tests.

### Branch (feat/events-public-seo)
```
DB_PASSWORD=… php vendor/bin/pest
Tests:    2 skipped, 379 passed (1302 assertions)   Duration: 90.45s
```
Delta: **+10 passed (the new test file), 0 new failures, 0 regressions.**

### Targeted runs
```
pest tests/Feature/EventPublicSeoTest.php tests/Feature/PublicEventViewTest.php
→ 14 passed (46 assertions)

vendor/bin/pint --test <changed PHP files>     → PASS (6 files, then 1 more after the middleware fix)
vendor/bin/phpstan analyse                     → [OK] No errors   (level 7, phpstan.neon + baseline, full configured paths)
```
PHPStan initially flagged the new middleware
(`Activity::find()` union type); fixed by narrowing with
`is_string()` before `find()` — re-run is clean.

### Live HTTP smoke test (dev copy, guest, no cookies)
`npm ci && npm run build` (assets were absent on this checkout;
`public/build` is gitignored), then `php artisan serve --port=8899`
against the dev database. Server stopped afterwards.

```
GET /e/salsa-night-basics-2026-11-11-ddvu            → HTTP 200
<title>Salsa Night Basics - Laravel</title>          ("- Laravel" is this dev copy's APP_NAME; production config governs the suffix)
<link rel="canonical" href="https://127.0.0.1:8899/e/salsa-night-basics-2026-11-11-ddvu">
<meta name="description" content="Ullam alias magni minus a et et ullam voluptatibus. Bring a friend — all levels welcome.">
JSON-LD offset 1963 < </head> offset 11028            → structured data is inside <head>
"offers": { "price": "28.00", "priceCurrency": "USD", "url": "…/e/salsa-night-basics-2026-11-11-ddvu" }   (price_cents = 2800 ✓)

GET /events/160af3cb-1a77-44a6-86b6-e1e8c9796b68     → HTTP/1.1 301 Moved Permanently
Location: https://127.0.0.1:8899/e/salsa-night-basics-2026-11-11-ddvu

GET /e/tuesday-skate-session-2026-11-03  (is_public = false)  → HTTP 404
GET /events/<that event's UUID>                             → HTTP 404
```

---

## Housekeeping notes for the reviewer

- The dev server's local `main` was 109 commits behind GitHub; it was
  fast-forwarded to `origin/main` (`b29a7d6`) before this branch was
  cut, and a `composer install` was run to sync `vendor/`. The
  checkout previously sat on `feat/000-suite-repair` — that branch is
  untouched and still exists locally and remotely.
- `node_modules/` and `public/build/` now exist on this dev checkout
  (both gitignored) from the smoke test.
- Events whose host renames the title will change slug (existing
  boot behavior) — old public URLs 404. Worth a redirect table when
  the sitemap lands.

## Suggested PR (if not already opened)

**Title:** `Public event SEO: head meta + JSON-LD price fix, canonical slug redirects, private-event guest gate`

**Body:**

> Follow-through on the spec-000 K5 contract (`events.public`) and the
> 2026-11-01 SEO recon. The slug route itself already existed on main;
> this branch fixes what was still broken around it:
>
> - `social-meta` read the removed `price` attribute — paid events
>   were emitted as free with no `offers`. Now uses `price_cents` +
>   `currency`, adds the canonical link, fixes the missing fallback
>   OG image, and guards null geo/end-time.
> - Meta/OG/JSON-LD now renders in `<head>` via a new `@stack('meta')`
>   on the `galaxy` and `app` layouts.
> - Privacy: non-public events were guest-readable by URL on both
>   event pages. Guests now get 404 (new `events.canonical`
>   middleware on `events.show`; matching gate in
>   `PublicEventView::mount` and `ActivityDetail`).
> - Canonicalization: guests hitting the UUID app URL get a **301**
>   to the slug URL (query string preserved); signed-in users keep
>   the app page, which gains a per-page title, meta description,
>   and canonical link. "Copy public link" now copies the slug URL.
> - Tests: 10 new feature tests (`EventPublicSeoTest`) covering the
>   SEO head, JSON-LD price, both privacy gates, the 301, and the
>   share actions. Suite: 369 → 379 passed, 2 skipped, 0 failed.
>   Pint PASS; PHPStan level 7 clean. Verified additionally with
>   guest curl requests against the dev copy (200 + in-head JSON-LD,
>   301, 404s) — transcripts in `READ_AND_DELETE.md` on the branch.
>
> Not included (follow-ons): sitemap.xml, llms.txt/robots, Search
> Console verification.
