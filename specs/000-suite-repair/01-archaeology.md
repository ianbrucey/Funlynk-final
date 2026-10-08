# 000 — Test suite repair — Repository archaeology

**Status:** APPROVED (findings complete 2026-10-08)
**Author:** Lotus (orchestrator)
**Date:** 2026-10-08
**Brief:** `00-brief.md` (APPROVED 2026-10-08)

> Method: full suite run captured on this branch (baseline re-verified), every
> failure's error signature extracted, then the implicated app code, migrations,
> factories, and git history read directly. Classifications follow the brief's
> repair policy: TEST-STALE (fix the test), APP BUG (fix the app, journal it),
> TEST-CONFIG / TEST-INFRA (fix test environment/support), CONFLICT (needs a
> decision — recorded in `decisions.md`).

---

## Baseline (before any repair work)

- Tests: `php artisan test` — **61 failed, 1 skipped, 141 passed** (423 assertions), PostgreSQL + PostGIS (`funlynk_test`)
- Pint: clean (381 files, from the tooling branch this spec is based on)
- PHPStan: level 7 clean (1,188 pre-existing findings baselined)
- Asset build: `public/build/` absent on the dev server — this is itself finding K1; CI builds assets before tests
- Branch base: `feat/000-suite-repair` sits on `feat/tooling-phpstan-ci` (PR #5) so the suite can run; rebase onto `main` when PRs #4/#5 merge

## Failure clusters (61 total, classified)

### K1 — Missing Vite manifest in the test environment (10) — TEST-INFRA

Every page-render test fails with `ViteManifestNotFoundException`
(`public/build/manifest.json` absent): `ExampleTest`, `CreatePostTest`
(render), `EditProfileTest` (access), `GroupsIntegrationTest` ×5,
`PublicGroupLandingTest` ×2 (the 500s).

- **Verdict:** the app is fine; tests must not depend on built assets.
- **Fix shape:** `$this->withoutVite()` in `tests/TestCase.php` (or per page
  test). Any page that still 500s after this is a separate finding.

### K2 — Missing service config in the test environment (16) — TEST-CONFIG

- `EditProtection/ActivityEditServiceTest` ×8 + `RefundWindowServiceTest` ×6:
  "Stripe secret key is not configured" — the refund-window path constructs a
  Stripe client; the test env defines no `STRIPE_SECRET_KEY`.
- `EditProfileTest` ×2: AWS SDK `InvalidArgumentException` (missing client
  configuration) — avatar handling resolves an S3 client in the test env.

- **Verdict:** test environment, not app logic.
- **Fix shape:** dummy `STRIPE_SECRET_KEY` / AWS values in `phpunit.xml`
  (client construction only); `Storage::fake()` / client mock in any test that
  would otherwise reach the network.

### K3 — Conversion service writes a dropped column (11) — **APP BUG (live path)**

`ActivityConversionService::createFromPost()` builds
`Activity::create([... 'is_paid' => $isPaid ...])`. The migration
`2026_01_30_144455_add_payment_type_to_activities_table` **dropped**
`activities.is_paid` in favor of `payment_type` ('free'/'online'/'at_door');
the test DB (built from the repo's migrations) confirms `is_paid` is gone.
The create therefore throws `QueryException` for **every** conversion, paid
or free: `ActivityConversionServiceTest` ×6, `PostServiceConversionExecutionTest`
×3, `PostConversionEventsTest` ×1 (a sibling `price` column reference), plus
`ActivityConversionServiceTest` ×1 `TypeError: string * int` in the same
service's price handling.

`PostService::convertToEvent()` (app/Services/PostService.php:375) delegates
to this service — **it is the live post→event conversion path, not dead code.**
The `Activity` model already carries a backward-compatible `is_paid` accessor
(reads work; writes do not), and `ActivityFactory` already uses `payment_type`.

- **Verdict:** genuine app bug (repair policy §2). Fix: write
  `'payment_type' => $isPaid ? 'online' : 'free'` — the migration's own data
  mapping — and fix the `price`/`string * int` handling in the same service.
- **Pre-merge checkpoint (plan T3):** confirm the production schema has
  `payment_type` (i.e., the migration ran in production) before this ships.
  All repo evidence says it did; the owner confirms at merge time.

### K4 — `PostConversionFactory` does not exist (6) — TEST-INFRA

`PostConversionMigrationsTest` ×1 + `PostConversionEventsTest` ×5 error with
`Class "Database\Factories\PostConversionFactory" not found`.

- **Verdict:** create `database/factories/PostConversionFactory.php` (NEW
  test-support code) modeled on the `PostConversion` model's fillable/casts.

### K5 — Route `events.public` referenced by live code, never defined (4) — CONFLICT → APP BUG

`PublicEventViewTest` ×4 fail with `Route [events.public] not defined`. The
route is also referenced by **app code**: `SocialShareService.php:34` and
`resources/views/components/social-meta.blade.php` (og:url + share payload).
`git log -S "events.public" -- routes/` shows the route never existed in the
recorded history of `routes/`.

- **Verdict:** three independent references (test, service, view component)
  describe a public event page that the router doesn't provide. Treated as an
  app bug (missing route) under repair policy §2. The plan's ticket proposes
  the minimal restoration consistent with the references (named route
  `events.public`, slug parameter, guest-accessible event view) — if
  execution finds an existing route already serving this purpose, the fix is
  to point the references at it instead.

### K6 — Conversion thresholds: tests say 5/10, code says 2/5 (6) — **CONFLICT — [O] owner decision**

`ConversionEligibilityServiceTest` ×3, `PostServiceConversionTest` ×2,
`PostConversionMigrationsTest` ×1 fail on eligibility outcomes and reason
strings. The tests encode soft = **5** reactions, strong = **10**. The code
(`Post::CONVERSION_SOFT_THRESHOLD = 2`, `CONVERSION_STRONG_THRESHOLD = 5`)
carries comments admitting these are not the production values:
"Soft prompt at 2 reactions (production: 5)", "Strong prompt/auto-convert at
5 (production: 10)". `docs/PRODUCT_BLUEPRINT.md` also records ~5 suggest /
~10 auto-convert.

- **Verdict:** three sources (tests, code comments, blueprint) agree the
  intended thresholds are 5/10; the constants look like dev leftovers. **But**
  restoring them changes live product behavior, so this is the owner's call —
  recorded as **[O] in `decisions.md`** with the orchestrator's recommendation
  (restore 5/10). The eligibility ticket is gated on this decision.

### K7 — Conversion prompt notification payload drift (2) — LOGIC, tied to K6

`PostConversionEventsTest` ×2: `Undefined array key "message"` — the tests
expect the prompt notification's data to carry a `message` string embedding
the thresholds ("5 people are interested", "10+ people want to join 🔥").
The notification construction (via `SendConversionPromptNotification`
listener) no longer produces that key/shape.

- **Verdict:** determine in execution which side carries the current truth
  (notification payload shape is app-visible behavior — users see these
  strings). Resolved together with the K6 threshold decision.

### K8 — FeedService return-shape drift (2) — LOGIC, per-case

`FeedServiceTest` ×2: `TypeError: Cannot access offset of type string on
string` inside feed assembly (items are arrays with a `data` key in the
current service). Per-case in execution: align the test with the service's
current return contract, or fix the service if the shape regressed.

### K9 — Meilisearch geo filter expectation (1) — per-case

`MeilisearchSearchServiceTest`: result size 2 vs expected 1 on a geo-proximity
filter. Could be index/settings drift or a stale expectation; per-case in
execution (note: no Meilisearch server runs in the local test env, so first
establish which engine actually served the query).

### K10 — Group post component property drift (1) — TEST-STALE

`GroupsLivewireTest`: test sets public property `title` on
`groups.create-group-post`; the component (`CreateGroupPost`) has no `title`
property — group posts are content-only (`content`, `locationName`,
`expiresIn`). Fix the test.

### K11 — PostConversion model shape assertion (1) — per-case

`PostConversionMigrationsTest`: "actual size 3 matches expected size 2" — a
count assertion (fillable/casts/relations) drifted. Per-case in execution.

### K12 — Public group landing redirect expectation (1) — per-case

`PublicGroupLandingTest`: expected 200, received 302 — likely an auth/onboarding
redirect the test doesn't account for. Per-case in execution; if the redirect
is intended behavior, fix the test.

## Architectural doors this feature must use

- Tests run on PostgreSQL + PostGIS only (`phpunit.xml`) — never SQLite.
- No migration is created, edited, or re-run by this spec (see `02-schema-delta.md`).
- App-code changes are limited to genuine bugs (K3, K5 if restored as a route,
  K6 if the owner restores thresholds) — each gets a `dev-journal/bug-fixes/`
  entry and the repaired test as its guard.
- Authorization assertions are never weakened to reach green (brief, security section).

## Files to touch

| File | Action | Owner | Reason |
|---|---|---|---|
| `phpunit.xml` | edit | orchestrator | K2 dummy Stripe/AWS test config |
| `tests/TestCase.php` | edit | orchestrator | K1 `withoutVite()` for page tests |
| `database/factories/PostConversionFactory.php` | create | builder | K4 missing factory |
| `app/Services/ActivityConversionService.php` | edit | builder | K3 write `payment_type`; price handling |
| `routes/web.php` + event view/controller | edit/create | builder | K5 restore `events.public` (if no existing route serves it) |
| `app/Models/Post.php` | edit (constants only) | builder | K6 — ONLY if the owner decides 5/10 |
| `app/Listeners/SendConversionPromptNotification.php` (+ notification) | edit | builder | K7 payload shape, after K6 |
| `tests/**` (failing files only) | edit | builder | TEST-STALE fixes per cluster |
| `.github/workflows/tests.yml` | edit | orchestrator | Final ticket: remove Pest `continue-on-error` |

## Protected files

- `database/migrations/**` — never edited by this spec.
- All passing tests — a repair that breaks a green test is stopped and re-planned.
- Production behavior beyond the K3/K5/K6 bug fixes above.

## What production does here today

- Post→event conversion runs through `PostService::convertToEvent()` →
  `ActivityConversionService` (K3 affects this path).
- Social sharing of events builds URLs via `route('events.public')` (K5
  affects share links and og meta on event pages).
- Conversion prompts fire from `Post` thresholds (K6) via the
  `CheckPostConversion` listener/job chain.

## Baseline

Recorded above (61F / 1S / 141P). Versions: laravel/framework 12.x,
livewire 3.6.4, pest 4.x — from `composer.lock` on this branch.
