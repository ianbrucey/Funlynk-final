# 001 — Timeline performance — Decisions

> Every decision made during this feature's life, with its date and tag.
> [D] decided · [P] proposed · [O] open. [D] is law; [P] is reviewable, not
> implementation authority; [O] stops work that depends on it.

| Date | ID | Tag | Decision | Context |
|---|---|---|---|---|
| 2026-10-11 | 001-D01 | [D] | Budget: server feed data < 200 ms at 10k posts; first cards visible < 1 s on a phone over 4G. | Owner, roadmap approval |
| 2026-10-11 | 001-D02 | [D] | Pilot scale < 100 users; atomic cutover allowed (pre-pilot relaxation). | Owner |
| 2026-10-11 | 001-O1 | [O] | **Feed order until spec 005.** Recommendation: one merged list, newest first (posts by creation time, events by when they were posted), with cursor-based paging. Distance only filters. | Blocks the final form of C-06 |
| 2026-10-11 | 001-O2 | [O] | **Radius defaults.** Today both default to 30 km with a slider. Recommendation: posts 10 km, events 50 km (blueprint ranges), keep the slider. | Blocks the final form of C-07 |
| 2026-10-11 | 001-O3 | [O] | **For-You feed in scope?** Recommendation: audit it, but change only the nearby feed (the landing page) — For-You belongs to spec 005. | Scope |
