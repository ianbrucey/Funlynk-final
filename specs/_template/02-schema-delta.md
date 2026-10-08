# <NNN> — <Feature name> — Schema delta

**Status:** DRAFT (→ APPROVED)
**Date:** <YYYY-MM-DD>
**Brief:** `00-brief.md` · **Archaeology:** `01-archaeology.md`

> State every migration this feature needs. "None" is a valid answer with reasons.
> Every change states its backward-compatibility story — this is a live app.

---

## Deltas

### <Migration name, e.g. `2026_10_15_000001_add_expiry_index_to_posts`>

- **Tables/columns/indexes:** <...>
- **Foreign keys / constraints:** <...>
- **Compatibility:** <additive | backfilled | breaking — with plan>
- **Rollback:** <what `migrate:rollback` leaves behind and whether that is safe>

### (repeat per migration)

## Compatibility summary

| Change | Production data impact | Rollback safe? |
|---|---|---|
| <...> | <none / backfill required / breaking> | <yes / no — plan> |

## Fold-back

<At Freeze, accepted deltas are folded into `docs/DOMAIN_MODEL.md`. Note the
sections that will change.>
