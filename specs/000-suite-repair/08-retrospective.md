# 000 — Test suite repair — Retrospective

**Date:** 2026-10-09 · **Branch:** `feat/000-suite-repair`

## Outcome
- `php artisan test`: 61 failed → **0 failed** (201 passed, 2 skipped). Evidence: `07-evidence/full-suite.txt`.
- Pint, PHPStan level 7, and `npm run build` are green.
- Pest is a hard CI gate (`continue-on-error` removed) — claims C-05, C-06.

## What the archaeology missed
- K3 was not one bug but six stacked ones (see
  `dev-journal/bug-fixes/2026-10-09-post-to-event-conversion.md`). Each fix
  revealed the next because the conversion transaction failed at its first error.
- One fix needed a migration, which the brief ruled out. The owner approved it
  mid-execution (000-D06). Briefs for brownfield repair should allow
  "additive migrations with owner approval" rather than "no migrations".

## Skips in the suite (2)
- `MeilisearchSearchServiceTest` geo filter — needs a live Meilisearch (000-D10, open 000-O2).
- `DatabaseSearchServiceTest` `whereDistance` — pre-existing skip, not touched by this spec.

## Change before the next feature
- Decide 000-O2 (Meilisearch in CI).
- Pre-merge checkpoint (000-D03): owner confirms the production schema has
  `activities.payment_type`; the new migration runs on deploy.
- Post-merge smoke test: login → feed → post → **convert post to event** → checkout.
