# 000 — Test suite repair — Strategic brief

**Status:** APPROVED
**Author:** Lotus (orchestrator)
**Date:** 2026-10-08

> HOW TO USE THIS TEMPLATE: fill in every `<...>` placeholder. Do not delete
> sections — if a section does not apply, write "N/A — <reason>" so the omission
> is explicit. No implementation work begins until this brief is APPROVED by the
> product owner.

---

## Goal

The FunLink test suite is trustworthy again: `php artisan test` passes 100% on
PostgreSQL + PostGIS, so every future spec's verdicts mean something and the CI
Pest gate can be switched from advisory to blocking.

## Claims

Every capability gets a named, binary verdict. Verdicts are written by the
orchestrator — a builder never writes the verdict that will judge its own work.

| ID | Capability | Verdict (named binary test) | Blueprint anchor |
|---|---|---|---|
| C-01 | Conversion-domain tests pass (post → event conversion services, eligibility, execution, events, migration tests) | `php artisan test --filter="Conversion"` — 0 failures | Testing strategy (BUILD_PROTOCOL §5); Product blueprint: Posts/Events |
| C-02 | Groups-domain tests pass (integration, Livewire, public landing) | `php artisan test --filter="Group"` — 0 failures | Blueprint: Groups |
| C-03 | Events / edit-protection / check-in tests pass | `php artisan test --filter="Event,EditProtection,CheckIn"` — 0 failures | Blueprint: Events, Trust & moderation |
| C-04 | Discovery / feed / search / profile / misc tests pass | `php artisan test --filter="FeedService,SearchService,EditProfile,CreatePost,Example"` — 0 failures | Blueprint: Discovery, Social graph |
| C-05 | Full suite green | `php artisan test` — 0 failed (baseline 2026-10-08: 61 failed, 1 skipped, 141 passed) | BUILD_PROTOCOL §4 quality gates |
| C-06 | CI enforces the suite | `.github/workflows/tests.yml` Pest step no longer `continue-on-error`; CI run green on this branch | [D] CI as referee (BUILD_PROTOCOL §6) |

## Blueprint anchors

- [D] Quality gates (AGENTS.md, 2026-10-08): `php artisan test` green before a task is done; never weaken an assertion to get green.
- [D] No opportunistic cleanup (BUILD_PROTOCOL §6): baseline debt is scoped into the brief or left alone — this brief IS the scope for the 61 failures.
- [D] Brownfield rules (BUILD_PROTOCOL §1): the app is live; migrations already ran in production. Test-only changes are preferred; app-code changes require a genuine bug.
- [P] Foundation protocol PR #4 and tooling PR #5 are in review, not yet merged. This spec is drafted against them; if review changes the protocol, this brief is re-approved.

## Repair policy

When a test and the app disagree, the app wins by default — it is live and
serving users; the tests drifted. Concretely:

1. **Fix the test** when the app behavior is correct (renamed route, changed
   copy, evolved component API, schema the migration already changed).
2. **Fix the app** only when the test exposes a genuine bug. The fix gets a
   `dev-journal/bug-fixes/` entry (symptom → root cause → fix → guard), and the
   repaired test is the regression guard. No behavior change beyond the bug fix.
3. **Delete or rewrite the test** only when it tests behavior that no longer
   exists AND the product owner confirms the behavior is intentionally gone.
   Deletions are listed in `decisions.md` with the reason — never silent.
4. **QueryExceptions (schema drift)** get per-case archaeology: is the column
   missing because the test is stale, or because a migration never ran? The
   answer is recorded before any fix.

## Production impact

None. This spec changes tests and, only where genuine bugs are found, minimal
app code with no behavior change beyond the fix. No migrations. No deploy-risk
change. Rollback: N/A (revert the branch).

## Security and privacy classification

- **Actors:** N/A (test-only).
- **Data touched:** synthetic fixtures only. If a failure needs
  production-shaped data to reproduce, the shape is synthesized — production
  data is never a fixture.
- **Authorization rules:** unchanged. Any test that weakens an authorization
  assertion to get green fails this spec's review.

## Evidence and fixtures

Baseline run 2026-10-08 (`php artisan test` on PostgreSQL + PostGIS,
`funlynk_test`): **61 failed, 1 skipped, 141 passed** (423 assertions).
Failure inventory by area (full output in `07-evidence/` at Freeze):

- **Conversion** (~25): `Services/PostServiceConversionTest`,
  `Services/ConversionEligibilityServiceTest`,
  `Services/ActivityConversionServiceTest` (QueryException ×6, TypeError),
  `Services/PostServiceConversionExecutionTest` (QueryException ×3),
  `Events/PostConversionEventsTest` (ErrorException/Error/QueryException),
  `Database/PostConversionMigrationsTest`
- **Groups** (~9): `GroupsIntegrationTest` (5 page-access),
  `GroupsLivewireTest` (PublicPropertyNotFoundException),
  `PublicGroupLandingTest` (3 render)
- **Events/edit-protection** (~18): `PublicEventViewTest`
  (RouteNotFoundException, ViewException ×3),
  `EditProtection/ActivityEditServiceTest` (Exception ×8),
  `EditProtection/RefundWindowServiceTest` (Exception ×6)
- **Discovery/misc** (~9): `FeedServiceTest` (TypeError ×2),
  `MeilisearchSearchServiceTest` (1), `EditProfileTest` (3),
  `CreatePostTest` (1), `ExampleTest` (1)

Note: even `ExampleTest` (the default "app returns successful response") fails —
the rot is broad, not deep. No single environmental cause was found
(Meilisearch-dependent tests are a minority; most failures are logic/schema drift).

## Pre-mortem

- **Likely failure:** a "test bug" turns out to be an app bug in a hot path
  (e.g., conversion eligibility). Mitigation: repair policy §2 — fix at the
  cause, journal it, the test becomes the guard. If the bug is load-bearing for
  the timeline refactor, flag it in `decisions.md` for spec 001.
- **Assumption:** the app is correct wherever tests drifted. Might be false in
  spots — archaeology verifies per area before fixing.
- **Unknown:** whether any failing test covers behavior the product owner
  intentionally removed. Mitigation: deletions require his confirmation (§3).
- **Mitigation for scope creep:** tickets are per-area; a ticket that starts
  "fixing" app architecture gets stopped and re-briefed.

## Non-goals

- No app refactoring beyond genuine bug fixes. The timeline performance work,
  UI consistency pass, and payments hardening are separate specs.
- No new tests for untested behavior — this spec repairs the existing suite to
  truthful green; coverage expansion is future work.
- No changes to the protocol docs, CI workflow (beyond the Pest gate flip),
  or PHPStan baseline.

## Approval gate

- [x] Orchestrator verdicts written (claims table complete)
- [x] Builder has reviewed and added risks to the pre-mortem
- [x] Governing [D]/[P]/[O] items linked; [O] stop conditions stated
- [x] Production impact + rollback plan stated
- [x] Security and privacy classification complete
- [x] Product owner approved — **approver:** Ian Bruce (DadGod) · **date:** 2026-10-08

<No code, no schema changes, no tickets until every box is checked.>
