# 001 — Timeline performance — Schema delta

**Status:** APPROVED
**Date:** 2026-10-12
**Brief:** `00-brief.md` · **Archaeology:** `01-archaeology.md`

---

## Deltas

**None.** The archaeology measured the fix directly: `idx_posts_location` and
`idx_activities_location` (both `GIST`, created with their tables in
`2025_11_10_003359_create_posts_table.php` and
`2025_11_10_003450_create_activities_table.php`) already support an
`ST_DWithin` radius filter — confirmed by `EXPLAIN (ANALYZE, BUFFERS)` showing
`Index Scan using idx_posts_location` with a bounding-box `Index Cond`, 2.3×
faster than the current `whereDistanceSphere` filter at the 10k-post scale
(`01-archaeology.md` → "EXPLAIN comparison").

The plan's fix is a query change in `FeedService` (filter with `ST_DWithin`,
keep `orderByDistanceSphere` for the final top-N sort) and a Blade/Livewire
change to stop re-querying reaction/RSVP counts per card. Neither needs a
migration.

If the plan phase's implementation reveals a need for an index this feature
doesn't already have (e.g. a composite index to help the merged-feed cursor
pagination from 001-O1), it will be proposed here before any ticket uses it,
as an additive migration per the live-app rules.

## Compatibility summary

| Change | Production data impact | Rollback safe? |
|---|---|---|
| None | N/A | N/A |

## Fold-back

N/A — no schema change to fold into `docs/DOMAIN_MODEL.md` for this spec.
