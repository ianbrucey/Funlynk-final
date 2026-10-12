# 001 — Timeline performance — Decisions

> Every decision made during this feature's life, with its date and tag.
> [D] decided · [P] proposed · [O] open. [D] is law; [P] is reviewable, not
> implementation authority; [O] stops work that depends on it.

| Date | ID | Tag | Decision | Context |
|---|---|---|---|---|
| 2026-10-11 | 001-D01 | [D] | Budget: server feed data < 200 ms at 10k posts; first cards visible < 1 s on a phone over 4G. | Owner, roadmap approval |
| 2026-10-11 | 001-D02 | [D] | Pilot scale < 100 users; atomic cutover allowed (pre-pilot relaxation). | Owner |
| 2026-10-12 | 001-O1 | [D] | **Feed order until spec 005.** One merged list, newest first (posts by creation time, events by when they were posted), with cursor-based paging. Distance only filters. | Owner approved; unblocks final form of C-06 |
| 2026-10-12 | 001-O2 | [D] | **Radius defaults.** Posts 10 km, events 50 km (blueprint ranges); slider kept. | Owner approved; unblocks final form of C-07 |
| 2026-10-12 | 001-O3 | [D] | **For-You feed scope.** Audited but not changed in spec 001; belongs to spec 005. | Owner approved |
| 2026-10-12 | 001-D03 | [D] | Brief approved. `scratch.md` added to `.gitignore`. | Owner approved |
| 2026-10-12 | 001-D04 | [D] | Archaeology complete — measured, not guessed, with a realistic 5-metro fixture (20% near viewer / 80% distant). Pre-mortem hypotheses confirmed: no N+1 in `FeedService` itself, but the Blade view adds 1 query/post + 2 queries/event; Livewire would rehydrate 1 query per loaded item on every interaction; PHP re-sorts each page independently. `whereDistanceSphere` cannot use the GIST spatial index and scans every active post globally (18.1 ms at 10k); a raw `ST_DWithin` predicate against the *same, already-existing* index returns the same rows in 7.8 ms (2.3× faster, no migration needed). Feed query p95 at 10k posts: 55.8 ms (within the 200 ms budget; card-render + rehydration cost on top of it is not). | `01-archaeology.md`, `07-evidence/audit-2026-10-12-local.txt` |
