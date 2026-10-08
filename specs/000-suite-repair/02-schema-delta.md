# 000 — Test suite repair — Schema delta

**Status:** APPROVED
**Date:** 2026-10-08
**Brief:** `00-brief.md` · **Archaeology:** `01-archaeology.md`

## Deltas

**None.** This spec creates, edits, and re-runs no migrations.

The one schema-adjacent finding (archaeology K3) is an app-code bug, not a
schema gap: migration `2026_01_30_144455_add_payment_type_to_activities_table`
intentionally replaced `activities.is_paid` with `payment_type`. The schema is
correct as migrated; `ActivityConversionService` still writes the dropped
column and is fixed in code (write `payment_type` using the migration's own
mapping: paid → `'online'`, free → `'free'`).

## Compatibility summary

| Change | Production data impact | Rollback safe? |
|---|---|---|
| (none) | none | N/A |

## Fold-back

Nothing to fold into `docs/DOMAIN_MODEL.md` from this spec. (DOMAIN_MODEL.md
itself is still [P] future work.)
