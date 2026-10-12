# 000 — Test suite repair — Decisions

> Every decision made during this feature's life, with its date and tag.
> [D] decided · [P] proposed · [O] open. [D] is law; [P] is reviewable, not
> implementation authority; [O] stops work that depends on it.

| Date | ID | Tag | Decision | Context |
|---|---|---|---|---|
| 2026-10-08 | 000-D01 | [D] | Repair policy: the live app wins when tests drift; app code is fixed only for genuine bugs, each journaled with the repaired test as its guard; test deletions need the owner's explicit confirmation. | Brief (approved 2026-10-08) |
| 2026-10-08 | 000-D02 | [D] | Spec branch is based on `feat/tooling-phpstan-ci` (PR #5) so the suite can run; rebase onto `main` when PRs #4/#5 merge. | Archaeology baseline |
| 2026-10-08 | 000-D03 | [D] | K3 is a genuine app bug: `ActivityConversionService` writes `activities.is_paid`, dropped by migration `2026_01_30_144455`. Fix writes `payment_type` per the migration's own mapping. Pre-merge checkpoint: owner confirms the production schema has `payment_type`. | Archaeology K3 |
| 2026-10-08 | 000-D04 | [D] | K5 (`events.public`) is treated as an app bug (missing route) — three independent live references (test, `SocialShareService`, `social-meta` component) describe it. Minimal restoration per contract; repoint instead if an existing route serves the purpose. | Archaeology K5 |
| 2026-10-08 | 000-O1 | [O] | **Conversion thresholds:** restore `Post` constants to soft 5 / strong 10, or keep 2/5 and fix the tests to match? Orchestrator recommendation: **restore 5/10** — tests, the constants' own comments ("production: 5" / "production: 10"), and the product blueprint all agree 5/10 is intended; 2/5 look like dev leftovers. Restoring changes live behavior (prompts fire less often), so the owner decides. **Blocks Ticket 5.** | Archaeology K6 |
