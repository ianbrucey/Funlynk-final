# 000 — Test suite repair — Schema delta

**Status:** APPROVED
**Date:** 2026-10-08
**Brief:** `00-brief.md` · **Archaeology:** `01-archaeology.md`

## Deltas

**One additive delta (amended 2026-10-09, owner-approved — `decisions.md` 000-D06):**

| Migration | Change |
|---|---|
| `2026_10_09_200347_add_migrated_status_to_post_invitations_table` | Widen `post_invitations_status_check` to `pending, viewed, reacted, ignored, migrated` |

Execution found that `MigratePostInvitations` writes `status = 'migrated'`,
which the original CHECK constraint rejects, rolling back every conversion of
a post with pending invitations. No existing migration is edited.

The original schema-adjacent finding (archaeology K3) is an app-code bug, not a
schema gap: migration `2026_01_30_144455_add_payment_type_to_activities_table`
intentionally replaced `activities.is_paid` with `payment_type`. The schema is
correct as migrated; `ActivityConversionService` still writes the dropped
column and is fixed in code (write `payment_type` using the migration's own
mapping: paid → `'online'`, free → `'free'`).

## Compatibility summary

| Change | Production data impact | Rollback safe? |
|---|---|---|
| Widen `post_invitations_status_check` | none — existing rows already satisfy the wider constraint | Yes — `down()` maps `migrated` → `pending`, then restores the original constraint |

## Fold-back

Nothing to fold into `docs/DOMAIN_MODEL.md` from this spec. (DOMAIN_MODEL.md
itself is still [P] future work.)
