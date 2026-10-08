# FunLink — Planning Protocol (per-feature)

**Status:** [D] Decided 2026-10-08 — the per-feature planning protocol for FunLink,
complementary to `docs/BUILD_PROTOCOL.md`.
**Applies to:** every feature that adds **a route, a migration, or a screen**.
Small fixes (copy tweak, one-line bug, styling nudge) run in lightweight mode:
brief + tickets + verdicts, nothing else.

---

## The two planning layers

- **System level** (`docs/`): decision-tagged standing law — blueprint, domain
  model, security, UI and build standards. Changed only with a recorded decision.
- **Feature level** (`specs/<NNN>-<slug>/`): this protocol. One folder per feature,
  artifacts generated in order:

```text
00-brief.md → 01-archaeology.md → 02-schema-delta.md → 03-contract.md
→ 04-fixtures.json → 05-ui-mockup.html → 05-ui.md → 06-plan.md → decisions.md
```

**Testing is not a state — it is a thread through every state:** verdicts are
defined in the Brief, fixtures fix the test data in the Council artifacts, tickets
carry named tests as acceptance criteria, green runs gate Execution, and Freeze
finalizes matrix cells and architecture tests.

**Production is a thread too:** the Brief states the production impact and
rollback plan; Archaeology records what production does today; the Contract
states migration compatibility; Freeze confirms the smoke test passed.

---

## The states (in order; no skipping)

### State 1 — Brief (`00-brief.md`)

- **Goal** — one sentence.
- **Claims** — capabilities as a table: `ID · description · Verdict · blueprint anchor`,
  where the Verdict is the **binary test** that proves the claim. A claim isn't
  done until its verdict is green.
- **Governing decisions** — which system docs and [D]/[P]/[O] items apply.
  If a claim needs an [O] answer, stop: get the answer first. Open items are
  questions, not assumptions.
- **Production impact** — what changes for live users and their data; rollback plan.
- **Security and privacy** — actors, data classes, authorization rules, location
  exposure, payment touchpoints.
- **Evidence** — fixtures, real examples, source requirements.
- **Pre-mortem** — what could break · what we're assuming · what we don't know yet.
- **Non-goals** — explicit exclusions.
- **Authorship** — the orchestrator drafts the Brief; the assigned agent reviews it
  and appends concerns (especially the pre-mortem); the product owner approves.
  The executing agent never writes its own Verdicts.
- **Approval gate** — DRAFT → APPROVED. No brief, no work.

### State 2 — Archaeology (`01-archaeology.md`)

Scan the codebase and docs for what already exists. Output a
**REUSE / EXTEND / NEW / CONFLICT** table for models, policies, services, routes,
components, and tests — plus conflicts and constraints. Skipping this is how
agents duplicate things that exist.

End the doc with a **files-to-touch table** (file · action · owner · reason) and a
**protected files** list the feature must not edit. Record the baseline:
test count, `pint --test`, asset build. Decide up front whether
cleaning baseline debt is in scope; if not, do not fix it mid-feature.

Also record: what does production do here today (routes, jobs, queues touched).

### State 3 — Council artifacts (relay race; each inherits the previous)

| Artifact | File | Content |
|---|---|---|
| Schema delta | `02-schema-delta.md` | Migrations/fields against the domain model, with backward-compatibility story. Accepted deltas fold back into the domain model at Freeze. |
| Contract | `03-contract.md` | Routes + requests + Livewire properties/actions/events. **Every operation names its authorization rule.** Payments name idempotency and failure behavior. Broadcasts name channels and payloads. Views may request only what the contract exposes. |
| Fixtures | `04-fixtures.json` | Real-data fixtures **before any logic**: normal case plus adversarial cases — unauthorized users, expired posts, sold-out events, failed payments, out-of-radius locations, malformed inputs. **One definition only:** either the JSON is loaded by test helpers/seeders, or the helper is canonical and the spec points to it. Never both hand-written. |
| UI mockup | `05-ui-mockup.html` | Required for any feature adding or changing a screen. One self-contained static HTML file: every permitted actor's view, every state (empty, loading, validation error, denied, conflict, success), realistic data volume, destructive-action confirmation, **390px layout first, then desktop**, galaxy theme baked in. Synthetic data only. |
| UI spec | `05-ui.md` | Maps mockup screens to Blade/Livewire components, plus deviations. New patterns go into `UI_STANDARDS.md` first. |

**Mockup approval gate:** the product owner approves the mockup (DRAFT → APPROVED,
recorded in `05-ui.md`) **before** `06-plan.md` is written. After approval the
mockup is frozen: a UI change during build means editing the mockup and getting
re-approval first.

**Conflict check before leaving this state:** the UI asks nothing the contract
doesn't provide · every restricted operation has an authorization rule ·
payments have idempotency · location exposure matches the radius contract ·
new rules are written as matrix cells for the testing strategy.

### State 4 — Plan (`06-plan.md`)

**Atomic tickets, backend-out** (migration → model/policy/service →
routes/components/events → views → integration). Each ticket: files, inputs,
dependencies, forbidden edits, and **acceptance criteria = its Verdict (named
tests)**. A ticket is done only when its verdict is green in isolation **and**
the standing gates pass. Do not start the next ticket on a red gate.

### State 5 — Execution

Builders execute tickets in order, obeying the UI and testing standards.
Sub-agent prompts are **self-contained**: ticket text, referenced artifact
sections, paths to the standards, brownfield reminders for touched data, and
"tests must be green." Sub-agents don't read this protocol; the orchestrator
injects what they need.

### State 6 — Freeze

**One home per fact.** Fold accepted schema deltas into the domain model ·
add new components to `UI_STANDARDS.md` and the pattern library · add new
security rules as matrix or architecture tests · record build decisions in
`decisions.md` · add `dev-journal/domain-knowledge/` entries · leave a
`dev-journal/progress/` entry · confirm the post-merge smoke test passed.

---

## The Commandments

1. **Don't guess.** [O] items are questions, not answers.
2. **Design ≠ labor.** The builder never changes schema without a spec delta.
3. **Fixtures before logic.** No code handles data that has no fixture.
4. **Atomic tickets.** Each completable and testable in isolation.
5. **No completion without a green Verdict.** Never weaken an assertion to get green.
6. **Authorization is part of every spec.** A feature touching restricted data
   ships its rules and matrix cells in the Council artifacts.
7. **Search before building framework features** — verify the documented,
   version-matched API (Laravel 12, Livewire v3, Filament v4), don't trust memory.
8. **Respect production.** Backward-compatible migrations, stated rollback,
   mandatory smoke test.

## Lightweight mode

Small changes: a three-line brief (goal + claim + verdict), the work, and the
green test or screenshot. Everything else runs the full states.

## Living rule

Refinements are recorded here with a date. The protocol serves the system docs —
if a rule ever conflicts with a non-negotiable in `AGENTS.md`, `AGENTS.md` wins.
