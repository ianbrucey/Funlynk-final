# FunLink build protocol

**Status:** [D] Decided 2026-10-08 — the standing build protocol for FunLink.
**Purpose:** Agent-first build discipline for a **live** application: requirements
become approved, testable, conflict-resistant work without breaking production.

## Decision summary

FunLink adopts this sequence:

> system decisions → approved feature brief → repository archaeology →
> schema/contract/fixtures/UI artifacts → atomic tickets → isolated execution →
> independent review → green gates → merge (production deploy) → smoke test →
> freeze and record lessons

The most important rule is: **agents do not begin from a conversation or a
feature name. They begin from an approved contract with binary verdicts.**

Because FunLink is live, every state carries one extra question the greenfield
version of this protocol never needed: *"what happens to production and its
data if this ships?"*

---

## 1. Protocol foundations

### Adopt unchanged

- **No brief, no work.** Every meaningful feature starts with an approved strategic brief.
- **Claims have binary verdicts.** Each capability is paired with a named test or other yes/no proof.
- **Archaeology before design.** Agents classify existing code as REUSE, EXTEND, NEW, or CONFLICT before proposing implementation.
- **Contracts before code.** Routes, service interfaces, data fields, authorization requirements, error behavior, events, and outputs are settled before tickets.
- **Fixtures before logic.** Test scenarios exist before implementation so agents build against the same facts.
- **Backend-out tickets.** Schema and domain seams precede transport, UI, and integration work.
- **Tests ship with each ticket.** A feature is not complete because it runs; it is complete when its verdicts are green.
- **Architecture laws are executable.** Critical boundaries are enforced by tests, not prose alone.
- **One home per fact.** Decisions, schema, contracts, and lessons each have a canonical location.
- **Complex bug fixes require a guard.** Every significant fix ends with a regression test, architecture test, or recorded rule.
- **Parallel work requires disjoint ownership.** Shared files and integration points belong to an orchestrator.

### Brownfield rules (live app — non-negotiable)

- **Migrations are backward-compatible.** Additive first; renames and drops ship
  with a data/backfill plan in the brief. No migration may orphan production data.
- **No breaking contract changes without a migration path.** If an API, event,
  or broadcast payload changes shape, the brief states who consumes the old
  shape and how the cutover happens.
- **Hot-path refactors ship behind a rollout plan.** Timeline, feed, and checkout
  refactors state their rollout (flag, percentage, or atomic cutover) in the
  brief — never as drive-by rewrites.
- **Production data is never a fixture.** Fixtures are synthetic. If a bug only
  reproduces with production-shaped data, the archaeology doc describes the
  shape and the fixture synthesizes it.
- **Merge = production deploy.** Post-merge smoke test is mandatory:
  login → feed → post → event → checkout. If the smoke test fails, the merge
  is reverted, not patched forward under pressure.

### Adapt for the FunLink domain

Authorization and data governance use FunLink-specific controls:

- user roles and group membership (member, host, admin);
- post visibility windows (ephemeral posts expire; expiry is enforced server-side);
- event capacity, ticketing state, and refund windows;
- payment state machines (Stripe intents, transfers, refunds) — money movement
  is idempotent and auditable;
- location privacy: discovery radii are contracts (posts ~5–10km, events ~25–50km);
  exact coordinates are never exposed beyond what the feature's contract allows;
- report/moderation state for posts, events, users, and messages;
- real-time channel authorization (private/presence channels authorize per user,
  per conversation or group).

### Do not copy

- entities, roles, fixtures, UI branding, or roadmaps from other projects;
- assumptions that the galaxy theme can be dropped for a "cleaner" look —
  the theme is standing law;
- a package or pattern merely because another project uses it;
- any feature-specific decision without confirming it against
  `docs/PRODUCT_BLUEPRINT.md` and the architecture decisions.

---

## 2. Two planning layers

### System layer: standing law

The repository holds a small set of authoritative documents under `docs/`:

```text
docs/
  PRODUCT_BLUEPRINT.md
  DOMAIN_MODEL.md            [P] — to be written; models verified 2026-10-08
  SECURITY.md                [P] — to be written; policies audited 2026-10-08
  UI_STANDARDS.md            [D] — galaxy theme, glass morphism, mobile-first
  BUILD_PROTOCOL.md          [D] — this document
  PLANNING_PROTOCOL.md       [D] — per-feature lifecycle
  ROADMAP.md                 [P] — refactor program order; owner to approve
```

Each decision is tagged:

- **[D] Decided** — agents may build against it.
- **[P] Proposed** — available for review, not implementation authority.
- **[O] Open** — work that depends on it stops or explicitly defers it.

### Feature layer: one folder per capability

```text
specs/<NNN>-<feature-slug>/
  00-brief.md
  01-archaeology.md
  02-schema-delta.md
  03-contract.md
  04-fixtures.json
  05-ui-mockup.html
  05-ui.md
  06-plan.md
  decisions.md
  07-evidence/
  08-retrospective.md
```

Not every feature changes every layer. A no-schema feature marks the schema
delta "none" with reasons. A backend-only feature marks UI artifacts "not
applicable." Files remain present so omissions are explicit rather than accidental.

---

## 3. Feature lifecycle

### State 1 — Strategic brief

The orchestrator drafts `00-brief.md`. The builder reviews it and adds risks;
the product owner approves it.

Required sections:

1. **Goal:** one sentence describing the user outcome.
2. **Claims:** capability, verdict, and blueprint anchor.
3. **Governing decisions:** links to system documents and relevant [D]/[P]/[O] items.
4. **Production impact:** what changes for live users and their data; rollback plan.
5. **Security classification:** affected actors, data classes, and required audit points.
6. **Evidence:** fixtures, known examples, and source requirements.
7. **Pre-mortem:** likely failure, assumption, unknown, and mitigation.
8. **Non-goals:** explicit exclusions.
9. **Approval:** status, approver, and date.

A builder never writes the verdict that will judge its own work. The planner owns
verdicts; the builder may challenge them before approval.

### State 2 — Repository archaeology

`01-archaeology.md` answers:

- What already exists?
- What is reusable, extendable, new, or conflicting?
- Which architectural doors must the feature use?
- Which files will change, and who owns them?
- What is the baseline test, style, static-analysis, and build status?
- Which version-matched framework documentation was checked?
- **What does production do here today?** (routes, jobs, and queues the
  feature touches — no surprises at deploy time.)

The artifact ends with a **files-to-touch table** and a list of protected files
the feature must not edit.

### State 3 — Council artifacts

These are produced in order because each constrains the next.

#### Schema delta

`02-schema-delta.md` states tables, fields, indexes, foreign keys, constraints,
and migration order against the domain model. Every change states its
backward-compatibility story: additive, backfilled, or breaking (with plan).

#### Contract

`03-contract.md` defines:

- domain service methods and DTOs;
- routes, requests, Livewire properties/actions, events, and jobs;
- input validation and the expected error for every invalid input;
- authorization rule for every operation;
- payment operations: idempotency keys, state transitions, failure/rollback behavior;
- real-time events: channel names, authorization, payload shape;
- location behavior: what coordinates enter, what radius applies, what leaves;
- data returned to each actor and data that must never appear;
- external integration failure behavior (Stripe, S3, Meilisearch, Reverb).

Views and components may request only what the contract exposes.

#### Fixtures

`04-fixtures.json` contains one canonical scenario for normal operation plus
adversarial cases. Fixtures must include unauthorized users, expired posts,
sold-out events, failed payments, malformed inputs, and out-of-radius locations
when relevant.

Use one definition only: either the JSON is loaded by test helpers/seeders or
the code fixture is canonical and the spec points to it. Do not maintain two
hand-written copies.

#### UI mockup and UI specification

A feature that adds or changes a screen requires a self-contained
`05-ui-mockup.html` showing:

- each permitted actor's view;
- empty, loading, validation, denied, conflict, and success states;
- realistic data volume (a full timeline, not three rows);
- destructive-action confirmation;
- **390px mobile layout first**, then desktop;
- the galaxy theme — the mockup must look like FunLink, not a wireframe;
- synthetic data only.

`05-ui.md` maps mockup sections to Blade/Livewire components and records any
approved deviation. The product owner approves the mockup before ticket
planning. After approval, UI changes require an updated mockup and renewed
approval.

### State 4 — Implementation plan

`06-plan.md` divides the work into atomic, backend-out tickets:

> baseline → migration → model/policy/service → routes/components/events →
> views → integration → evidence/freeze

Each ticket names files, inputs, dependencies, acceptance tests, and forbidden
edits. A ticket is complete only when its own verdict and the standing
architecture/style gates are green.

Prefer a few independently testable tickets over many tiny tickets that cannot
stand alone.

### State 5 — Execution

Builders execute tickets in order. Each agent prompt must be self-contained and
include:

- the ticket text;
- relevant contract and fixture sections;
- applicable standards;
- file ownership and forbidden files;
- required tests and commands;
- the brownfield reminders for touched data (production impact, rollback);
- the instruction to stop on an unresolved [O] item.

Agents do not receive vague assignments such as "make the timeline faster."

### State 6 — Review, merge, and production deploy

- Work occurs in `feat/<slug>` branches.
- The builder does not approve its own merge.
- Gates run green: Pest, Pint, asset build (and PHPStan/CI once they exist).
- Merge occurs only after required checks and review are green.
- Merge to `main` deploys to production via Laravel Cloud.
- **Post-merge smoke test is mandatory:** login → feed → post → event →
  checkout. On failure: revert first, diagnose second.
- Human review checks layout, workflow, and anything whose quality is not
  reducible to assertions (the galaxy theme has to *feel* right).

### State 7 — Freeze

At Freeze:

- accepted schema changes move into `DOMAIN_MODEL.md`;
- new UI primitives move into `UI_STANDARDS.md` (and the pattern library);
- new security rules become architecture or matrix tests;
- decisions made during execution go into `decisions.md`;
- complex lessons go into the dev journal;
- `07-evidence/` contains verdict output and approved mockups;
- `08-retrospective.md` records what should change before the next feature.

One fact must have one canonical home. Freeze should link rather than duplicate.

---

## 4. Standing gates

### Authorization matrix

Authorization coverage is a dataset-driven test over:

> actor × group membership × post/event state × action

Each cell asserts allow or deny, the expected HTTP/Livewire behavior without
existence leakage, and the absence of restricted strings in rendered output.

### Architecture laws (proposed — finalize before locking as tests)

1. **Server-side authorization:** one policy layer decides; components never
   gate data client-side.
2. **Payments:** application code calls Stripe only through the payment service;
   money movement is idempotent.
3. **Location:** distance and radius queries go only through the spatial scopes;
   exact coordinates never leave the server beyond the contract.
4. **Storage:** file uploads go through the storage service to S3; no local
   disk paths in production code.
5. **Real-time:** broadcasts go through authorized channels only; payloads
   contain no more than the contract allows.
6. **Routes:** authenticated-by-default, with explicit public exceptions.
7. **Expiry:** ephemeral-post expiry is enforced server-side (query scope +
   scheduled cleanup), never trusted to the client.
8. **Secrets and PII:** no log, queue payload, broadcast, or error message
   carries secrets, tokens, or precise user locations unless the contract
   explicitly permits it.

Names are proposed; the security document must finalize interfaces before tests
lock them.

### Quality gates

For every ticket:

```bash
php artisan test
vendor/bin/pint --test
```

For every feature and before merge:

```bash
php artisan test
vendor/bin/pint --test
npm run build
```

A failing test is fixed at the cause. An assertion is never weakened merely to
obtain green status; if a governing decision changed, update the decision first
and then the test.

**[P]** PHPStan level 7 and CI (Pint → PHPStan → Pest on PostgreSQL) are the
first tooling tickets. Until they land, the builder pastes terminal output of
the three commands above as merge evidence.

---

## 5. Testing strategy

### Layer 1 — Pure unit tests

Policy decisions, expiry math, radius logic, payment state transitions,
conversion thresholds, refund-window calculations.

### Layer 2 — Service and integration tests

Payment intents and webhooks, storage uploads, broadcast delivery, search
indexing, notifications, post-to-event conversion, scheduled expiry.

### Layer 3 — HTTP feature tests

Every route: happy path, validation, guest behavior, authorization denial,
cross-user denial, expired-content behavior, and restricted-string leak checks.

### Layer 4 — Livewire/component tests

Stateful forms, chat, feeds, RSVP flows, ticket display, permission-sensitive
rendering, galaxy-theme layout presence.

### Layer 5 — Browser journeys

Keep a small stable set: register → create post → react → convert to event →
RSVP → checkout → ticket → QR check-in; create group → post in group → group
chat; host onboarding → payout.

### Layer 6 — Architecture tests

Executable enforcement of the doors listed above.

### Layer 7 — Schema tests

Indexes (including spatial), foreign keys, unique constraints, and migration
compatibility — every migration must run cleanly against a production-shaped
schema.

---

## 6. Agent swarm protocol

Parallel execution is allowed only when file ownership and contracts are disjoint.

### Required controls

1. **One orchestrator:** owns planning, shared interfaces, integration, and final verdicts.
2. **One branch per feature:** never place two builders in the same working tree.
3. **Approved contracts first:** parallel work starts only after shared interfaces are frozen.
4. **Ownership map:** every wave lists agent-owned directories and orchestrator-owned files.
5. **Shared files are protected:** route registration, service-provider bindings, shared layouts, core enums, and migration ordering belong to the orchestrator unless explicitly delegated.
6. **Integration tickets are explicit:** a "droppable" component is not silently wired into another agent's page.
7. **Independent review:** the builder does not certify its own merge.
8. **Gates are the referee:** local success does not override merged-branch gates.
9. **No opportunistic cleanup:** baseline debt is either scoped into the brief or left alone.
10. **Stop on contract drift:** if implementation reveals a contract error, update and reapprove the relevant artifact before continuing.
11. **Live app, live caution:** parallel waves never touch the same migration sequence or the same hot path in one wave.

### Example ownership map

| Worker | Owns | Must not edit |
|---|---|---|
| Timeline | feed queries, post components | payments, checkout |
| Events | event domain, RSVP, tickets | feed queries, chat |
| Social | groups, chat, DMs, reactions | payments, event capacity |
| Infrastructure | CI, deploy scripts, tooling | domain behavior |
| Orchestrator | shared routes, bindings, integration | feature internals |

The actual first wave should remain smaller than this example until the base
repository, namespaces, and architecture tests are stable.

---

## 7. Recommended first build sequence

### Phase 0 — Foundation and tooling (this drop)

Protocol docs, spec template, dev journal. Then the first tooling tickets:
PHPStan level 7 config + baseline, CI workflow (Pint → PHPStan → Pest on
PostgreSQL), `DOMAIN_MODEL.md`, `SECURITY.md`.

**Exit verdict:** a trivial branch passes all gates and the smoke test.

### Phase 1 — Timeline performance refactor

The feed is the front door and currently feels bulky and slow. Full spec:
archaeology of the current rendering pipeline, performance budget, approved
HTML mockup, backend-out tickets (query → component → view), rollout plan.

**Exit verdict:** the timeline meets its budget on production-shaped data with
all verdicts green.

### Phase 2 — Page-by-page UI consistency pass

Apply the approved galaxy/mobile-first standards across the remaining screens,
one spec per surface, reusing the timeline's proven components.

### Phase 3 — Payments and trust hardening

Idempotency audit, refund windows, webhook resilience, host payout flows —
spec'd and tested to the architecture laws.

Phase order is [P] — the product owner approves the roadmap before Phase 1
briefing begins.

---

## 8. Approval checklist before the refactor program starts

- [ ] Product blueprint v0.1 reviewed (this drop).
- [ ] PHPStan level 7 baseline green.
- [ ] CI workflow operational (Pint → PHPStan → Pest on PostgreSQL).
- [ ] `DOMAIN_MODEL.md` and `SECURITY.md` written and approved.
- [ ] Post-merge smoke test procedure confirmed against production.
- [ ] First feature brief (timeline refactor) has verdicts written by the
      orchestrator and approved by the product owner.

## 9. Development journal

Use this journal structure:

```text
dev-journal/
  bug-fixes/
  domain-knowledge/
  progress/
```

- **Bug fixes:** symptom → root cause → fix → guard. No guard, no completed fix.
- **Domain knowledge:** topic-named, durable findings not already in formal docs.
- **Progress:** done → in progress → next → decisions and open threads.

Formal decisions stay in the system and feature documents; the journal links to
them rather than replacing them.
