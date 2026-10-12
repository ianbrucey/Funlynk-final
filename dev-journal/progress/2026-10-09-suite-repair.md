# Progress — 2026-10-09 (spec 000 suite repair)

## Done
- Spec 000 Tickets 1–8 executed on `feat/000-suite-repair` (rebased on `main`).
- Suite: 61 failed → 0 failed (201 passed, 2 skipped). Pest is now a hard CI gate.
- App bugs fixed: post → event conversion (six stacked causes —
  `bug-fixes/2026-10-09-post-to-event-conversion.md`); `events.public` route restored.
- Thresholds restored to 5/10 (000-D05). One additive migration (000-D06).
- Local test DB: role `funlynk`, database `funlynk_test` with PostGIS.

## Next
- Owner: pre-merge checkpoint (prod has `payment_type`), review + merge, smoke test.
- Decide 000-O2 (Meilisearch service in CI).
- Component library discussion (owner request).
