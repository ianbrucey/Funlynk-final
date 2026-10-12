# `feed:benchmark` broke silently after `FeedService` contract changed (2026-10-12)

Spec: `specs/001-timeline-performance/` (Ticket 5, decision 001-D11).

## Symptom

Re-running `feed:benchmark` for Ticket 5's final evidence collection threw
`Unknown named parameter $radius` immediately. The command was written during
archaeology (before Ticket 1) and called
`$feedService->getNearbyFeed(user: $viewer, radius: 50, page: 1, perPage: 30)`.

## Root cause

Ticket 1 changed `FeedService::getNearbyFeed()`'s signature and return shape
(cursor-based paging instead of `page`/`radius`, plain `FeedItem` arrays
instead of `['type' => ..., 'data' => Model]`) as approved in `03-contract.md`
and `001-D06`. `FeedBenchmarkCommand` is not exercised by `php artisan test`
(it's a manual evidence tool, seeds and rolls back its own data), so nothing
caught the drift until it was run again by hand for Ticket 5.

The command's N+1/rehydration simulation blocks were also now meaningless:
they called `$item['data']->reactions` / `Post::query()->find($item['data']->id)`,
but `$item['data']` no longer exists — `FeedItem` carries `reaction_count`
and `rsvp_count` inline, and Livewire never rehydrates a model for these items
at all (that's the point of Ticket 1/2's fix), so there was nothing left to
simulate.

## Fix

- Updated the call to `getNearbyFeed(user: $viewer, eventRadiusKm: 50, perPage: 30)`.
- Removed the obsolete per-card and rehydration query simulations; the
  invariant they checked is now covered directly by
  `test_nearby_feed_query_count_is_constant` and
  `test_reacting_to_post_runs_bounded_queries` (Ticket 2), which exercise the
  real Livewire component instead of simulating it.
- Summary table/`printSummary()` columns updated to match (`has more` replaces
  `total`, no `total` under keyset pagination).

## Recurrence guard

None added — `feed:benchmark` is an evidence/archaeology tool, deliberately
outside `php artisan test`'s scope (it seeds and rolls back scratch data at
scale, which is too slow for the standard suite). The retrospective
(`08-retrospective.md`) flags adding a lightweight CI contract check for it as
a follow-up, not done in this spec.

## Lesson

A tool that calls a service but isn't covered by the test suite will silently
drift whenever that service's contract changes. Either keep such tools inside
the test suite (even if skipped by default) or budget time to re-verify them
by hand at every ticket boundary that touches the service they call — not
just at the end, when the drift has had time to accumulate.
