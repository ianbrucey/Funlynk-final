# 000 — Test suite repair — Implementation plan

**Status:** DRAFT (→ APPROVED)
**Date:** 2026-10-08
**Brief:** `00-brief.md` (APPROVED) · **Archaeology:** `01-archaeology.md` ·
**Contract:** `03-contract.md`

> Atomic tickets, backend-out. Each ticket is independently testable; a ticket
> is done only when its verdict is green in isolation AND the standing gates
> pass (`php artisan test` for the touched files, Pint, PHPStan checkpoint on
> app-code tickets). Ticket 5 is **gated on the K6 owner decision** in
> `decisions.md` — it does not start until that decision exists.

---

## Ticket 1 — Test-environment config (K1 + K2)

**Files:** `phpunit.xml`, `tests/TestCase.php`
**Inputs:** archaeology K1, K2
**Dependencies:** none
**Forbidden edits:** any test's assertions

### Work
- [ ] Add dummy `STRIPE_SECRET_KEY` and AWS/S3 values to `phpunit.xml` (construction-only)
- [ ] Add `withoutVite()` to the base test case so page tests render without built assets
- [ ] Where a test would still reach the network (Stripe/S3), add `Storage::fake()` / client mock in that test's setup

### Verdict
- `ExampleTest`, `EditProfileTest`, `EditProtection/*` suites green
- Claims C-03 (partial), C-04 (partial)

## Ticket 2 — Missing factory (K4)

**Files:** `database/factories/PostConversionFactory.php` (create)
**Inputs:** `PostConversion` model fillable/casts; archaeology K4
**Dependencies:** none
**Forbidden edits:** the model

### Work
- [ ] Create the factory matching the model's current shape

### Verdict
- No `PostConversionFactory not found` errors remain anywhere in the suite

## Ticket 3 — Conversion service writes dropped column (K3) — APP BUG

**Files:** `app/Services/ActivityConversionService.php`; `dev-journal/bug-fixes/`
**Inputs:** archaeology K3; migration `2026_01_30_144455`
**Dependencies:** Ticket 2 (factory errors mask this cluster's tests)
**Forbidden edits:** migrations; `PostService` public signature

### Work
- [ ] Write `payment_type` (`'online'`/`'free'`) instead of `is_paid` in `createFromPost()`
- [ ] Fix the `price` column reference and the `string * int` price handling in the same service
- [ ] Journal entry: symptom → root cause → fix → guard (the repaired tests)

### Verdict
- `ActivityConversionServiceTest`, `PostServiceConversionExecutionTest` green
- Claim C-01 (partial)
- **Pre-merge checkpoint:** owner confirms production schema has `payment_type`

## Ticket 4 — Public event route (K5)

**Files:** `routes/web.php`; existing event view/controller (or minimal new one)
**Inputs:** archaeology K5; contract §Exception
**Dependencies:** Ticket 1 (page tests must render)
**Forbidden edits:** `SocialShareService` / `social-meta.blade.php` unless execution proves an existing route serves the purpose (then repoint instead, and record it)

### Work
- [ ] Establish whether an existing route already serves the public event page
- [ ] Restore `events.public` (slug, guest-accessible) per the contract, or repoint the three references

### Verdict
- `PublicEventViewTest` green
- Claim C-03 (partial)

## Ticket 5 — Thresholds + prompt notification (K6 + K7) — GATED

**Files:** `app/Models/Post.php` (constants, only if decided), `app/Listeners/SendConversionPromptNotification.php` (+ notification), failing eligibility/prompt tests
**Inputs:** archaeology K6, K7; **`decisions.md` K6 decision (owner)**
**Dependencies:** the K6 decision; Ticket 3 (conversion path must work)
**Forbidden edits:** eligibility result shape (contract §Standing contracts)

### Work
- [ ] Apply the decided thresholds (recommendation: restore 5/10)
- [ ] Restore/align the prompt notification `message` payload with the decided thresholds
- [ ] Align the eligibility tests with the decided behavior (no assertion weakening — the decision doc is the authority)

### Verdict
- `ConversionEligibilityServiceTest`, `PostServiceConversionTest`, `PostConversionEventsTest`, `PostConversionMigrationsTest` green
- Claims C-01 (complete)

## Ticket 6 — Groups residuals (K10 + K12 + any non-Vite 500s)

**Files:** `tests/Feature/GroupsLivewireTest.php`, `tests/Feature/PublicGroupLandingTest.php`, `tests/Feature/GroupsIntegrationTest.php`
**Inputs:** archaeology K10, K12
**Dependencies:** Ticket 1
**Forbidden edits:** `CreateGroupPost` component behavior

### Work
- [ ] Fix the `title` property usage in the Livewire test (component is content-only)
- [ ] Resolve the 302-vs-200 landing expectation per actual intended behavior

### Verdict
- Groups test files green
- Claim C-02 (complete)

## Ticket 7 — Discovery/misc residuals (K8 + K9 + K11)

**Files:** `tests/Feature/FeedServiceTest.php`, `tests/Feature/MeilisearchSearchServiceTest.php`, `tests/Feature/Database/PostConversionMigrationsTest.php` (residual)
**Inputs:** archaeology K8, K9, K11
**Dependencies:** Ticket 1
**Forbidden edits:** none beyond the per-case verdict

### Work
- [ ] Per-case: align each test with the current service contract, or fix the service if the shape regressed (journal any app fix)

### Verdict
- The three test files green
- Claim C-04 (complete)

## Ticket 8 — Full suite + CI gate flip + freeze

**Files:** `.github/workflows/tests.yml`, `07-evidence/`, `08-retrospective.md`, `decisions.md`, `dev-journal/`
**Inputs:** Tickets 1–7 green
**Dependencies:** all prior tickets
**Forbidden edits:** —

### Work
- [ ] Full `php artisan test` green (C-05)
- [ ] Remove `continue-on-error` from the CI Pest step (C-06)
- [ ] Evidence: full-suite output in `07-evidence/`; retrospective written
- [ ] Post-merge smoke test: login → feed → post → event → checkout

### Verdict
- `php artisan test` — 0 failed
- CI green on the branch with Pest as a hard gate
- Claims C-05, C-06 (complete)
