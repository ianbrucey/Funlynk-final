# 001 — Timeline performance — Retrospective

**Date:** 2026-10-12
**Branch:** `feat/001-timeline-performance`

## Claims scorecard (C-01…C-08)

| ID | Claim | Verdict | Status |
|---|---|---|---|
| C-01 | Feed data fast at scale | `feed:benchmark`, 20 iterations, 10k posts: p95 41.93 ms | ✅ green |
| C-02 | No per-card queries | `test_nearby_feed_query_count_is_constant` | ✅ green |
| C-03 | Reacting doesn't reload feed | `test_reacting_to_post_runs_bounded_queries` | ✅ green |
| C-04 | Shell before feed query | `test_feed_page_renders_shell_with_skeleton` | ✅ green |
| C-05 | First cards < 1s on Slow 4G | Browser check against Laravel Cloud | ⚠️ **gap** — no reachable deploy URL; see `07-evidence/c05-browser-check-GAP.md` |
| C-06 | Infinite scroll coherent | `test_feed_pages_have_no_duplicates_or_gaps` | ✅ green |
| C-07 | Radius contract holds | `test_feed_respects_post_and_event_radius` | ✅ green |
| C-08 | No location leakage | `test_feed_snapshot_contains_no_coordinates` (service-level + full page) | ✅ green |

7 of 8 claims closed. C-05 is explicitly recorded as open rather than guessed,
per the plan's own instruction for this case.

## What went well

- The archaeology-first approach (measure before guessing) paid off directly:
  the pre-mortem's five hypotheses were all confirmed by the audit, so Ticket
  1–4 work was targeted at real, measured costs instead of suspected ones.
- `ST_DWithin` on the existing GIST index needed no migration — the fix was
  query-only, which matched the "no schema changes expected" production-impact
  call in the brief.
- Moving `FeedItem` to a plain array (not an Eloquent model) eliminated two
  classes of cost in one change: per-card N+1 relation access in the Blade
  components (Ticket 3) and Livewire's per-interaction model rehydration
  (Ticket 2) — confirmed by `test_nearby_feed_query_count_is_constant` and
  `test_reacting_to_post_runs_bounded_queries` both passing on the first real
  implementation, not after iteration.
- Fixture-driven card tests (`NearbyFeedCardsTest`) caught real rendering
  issues (converted-post overlay, no-image event placeholder) before they
  could reach a mockup-comparison pass.

## What was harder than expected

- `03-contract.md`'s query-budget estimate for `reactToPost` (≤ 3) was wrong
  by an order of magnitude once measured (14, from pre-existing broadcast/
  notification machinery this spec doesn't touch). Recorded as 001-D10 rather
  than silently loosening the test — the right call, but it's a reminder that
  contract-stage query budgets are estimates until Ticket 1/2 measure them.
- The benchmark tool itself (`FeedBenchmarkCommand`, written during
  archaeology) silently drifted out of sync with `FeedService`'s finished
  signature by the time Ticket 5 ran it — nothing caught this until the
  command errored at runtime, because it isn't exercised by `php artisan
  test`. See `dev-journal/bug-fixes/2026-10-12-stale-benchmark-command.md`.
- C-05 could not be closed in this environment at all — there's no reachable
  staging/Laravel Cloud URL in the agent's tool access, only infra
  credentials for backing services. This should be flagged to the product
  owner as a standing gap for every future spec with a real-network claim.

## Process notes

- All 5 tickets shipped backend-out as planned, each independently green
  before the next started.
- No migration ticket was needed, confirmed by `02-schema-delta.md` ahead of
  time — saved a ticket's worth of review.
- Full suite: 209 passed, 2 skipped, Pint clean, at every ticket boundary.

## Follow-ups (not blocking this spec's freeze)

1. Run the C-05 Slow-4G browser check once a reachable deploy exists; do not
   retroactively mark C-05 green without it.
2. Consider adding `feed:benchmark` (or a lighter contract check) to CI so a
   `FeedService` signature change can't silently break it again.
3. Post-merge: run the smoke test (login → feed → post → event → checkout) on
   `main` per `AGENTS.md`'s working rules — this is a deploy-time step, not
   performed as part of this build.
