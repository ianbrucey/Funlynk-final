# <NNN> — <Feature name> — Implementation plan

**Status:** DRAFT (→ APPROVED)
**Date:** <YYYY-MM-DD>
**Brief:** `00-brief.md` (APPROVED) · **Contract:** `03-contract.md` ·
**Mockup:** `05-ui-mockup.html` (APPROVED, if UI)

> Atomic tickets, backend-out: baseline → migration → model/policy/service →
> routes/components/events → views → integration → evidence/freeze.
> Each ticket is independently testable. A ticket is done only when its verdict
> is green in isolation AND the standing gates pass.

---

## Ticket 1 — <Outcome, e.g. baseline + migration>

**Files:** <owned files>
**Inputs:** contract sections, fixture cases
**Dependencies:** none
**Forbidden edits:** <protected files>

### Work
- [ ] <implementation step>

### Verdict
- <named test from the brief's claims table>
- `php artisan test` green · `vendor/bin/pint --test` clean · `npm run build` (if assets touched)

## Ticket 2 — <Outcome>

<repeat the shape above>

## Ticket N — Evidence and freeze

**Files:** `07-evidence/`, `08-retrospective.md`, `decisions.md`, `dev-journal/`
**Inputs:** all prior tickets' verdict output

### Work
- [ ] Fold accepted schema deltas into `docs/DOMAIN_MODEL.md`
- [ ] Add new UI primitives to `docs/UI_STANDARDS.md` + pattern library
- [ ] Add new security rules as matrix/architecture tests
- [ ] Record build decisions in `decisions.md`
- [ ] Write `07-evidence/` (verdict output, approved mockup) and `08-retrospective.md`
- [ ] Dev journal entries (bug fixes with guards, domain knowledge, progress)

### Verdict
- Post-merge smoke test passed: login → feed → post → event → checkout
- All gates green on `main`
