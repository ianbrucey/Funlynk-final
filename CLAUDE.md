# CLAUDE.md — FunLink

Agent handoff for this repository. Read this first, then the docs in the table below.
This file and `AGENTS.md` are exact mirrors — update both or neither.

## What FunLink is

FunLink (FL) is a **live** Laravel 12 social discovery app: spontaneous, niche
activity discovery built on an ephemeral **Posts → structured Events** dual model.

- **Posts**: ephemeral (24–48h), spontaneous, ~5–10km discovery radius, lightweight
  reactions ("I'm down", "Join me"). Posts have group chat instead of comment sections.
- **Events** (Activities): structured, persistent, ~25–50km radius, RSVPs, ticketed
  payments via Stripe. Hosts onboard through Stripe; attendees check in with QR tickets.
- **Conversion**: high-engagement posts convert into structured events
  (`originated_from_post_id` links the event back to its post).
- **Groups**: user-created groups with their own posts and group chat.
- **Discovery**: nearby feed, for-you feed, map view, user/post search —
  all location-aware through PostGIS.
- **Real-time**: Laravel Reverb powers chat, notifications, and live updates.

The app is **live in production** (deployed via Laravel Cloud). Every merge to
`main` is a production deploy. The dev-server checkout at `/opt/funlynk`
(PostGIS database `funlynk` on the shared dev server) is for development and
inspection — it is not the production environment.

## Read these first (all in `docs/`)

| Doc | Why |
| --- | --- |
| `PRODUCT_BLUEPRINT.md` | **The contract.** The product definition; system-level standing law. |
| `BUILD_PROTOCOL.md` | Agent build discipline: planning layers, feature lifecycle, gates, swarm controls. [D] is law; [O] stops work. |
| `PLANNING_PROTOCOL.md` | How features move: brief → archaeology → council artifacts → plan → execution → freeze. |
| `UI_STANDARDS.md` | UI/UX standards: galaxy theme, glass morphism, mobile-first. Law for every screen. |

## Non-negotiables (get these wrong and nothing else matters)

- **Posts and Events are the central objects.** Every reaction, RSVP, ticket,
  transaction, chat message, and notification attaches to a post, an event, or a
  group. There is no orphaned social content.
- **Authorization is enforced ONLY server-side.** Policies and gates decide;
  nothing client-side ever gates data. Every operation on restricted content
  names its authorization rule.
- **Payments go through the payment service layer only.** Controllers and
  Livewire components never call Stripe directly. Money movement is auditable
  and idempotent.
- **Location queries go through the spatial layer only.** PostGIS scopes via
  `matanyadaev/laravel-eloquent-spatial`; no hand-rolled distance math, no
  location data leaked beyond the feature's radius contract.
- **Every page wears the galaxy theme.** Dark space aesthetic, glass morphism,
  the `<x-galaxy-layout>` shell — no exceptions. See `UI_STANDARDS.md`.
- **Mobile-first is a gate, not a suggestion.** Every screen is designed and
  verified at 390px width first; desktop is the enhancement.
- **The timeline must feel instant.** The feed is the product's front door —
  bulky, slow page rendering is a defect, not a style choice.

## Decision tags

Every standing doc tags claims **[D]** decided / **[P]** proposed / **[O]** open.
- **[D]** is law — buildable.
- **[P]** is reviewable, not implementation authority.
- **[O]** is an open question. **Never guess on [O]** — record it as an open
  question; work depending on it stops or explicitly defers it.
- Record every decision with its date.

## Stack (decided)

Laravel 12 · PHP 8.2+ · PostgreSQL + PostGIS · Filament v4 (admin) ·
Livewire v3 · Blade + Tailwind + DaisyUI · Pest v4 · Pint ·
Laravel Reverb (real-time) · Laravel Octane · Laravel Scout + Meilisearch ·
Stripe · S3-compatible storage.
Database queues via Horizon.

## Quality gates

For every ticket: `php artisan test` green · `vendor/bin/pint --test` clean.

For every feature and before merge: full `php artisan test` · Pint ·
`npm run build` · the architecture test suite green.

- **[P]** PHPStan level 7: config + baseline is the first tooling ticket.
  Until it lands, Pint + Pest green is the standing gate.
- **[P]** CI (`.github/workflows/tests.yml`: Pint → PHPStan → Pest on
  PostgreSQL). Until it exists, gates run locally and the builder pastes
  terminal output as evidence.

A failing test is fixed at the cause. **An assertion is never weakened merely
to obtain green** — if a governing decision changed, update the decision doc
first, then the test.

## Working rules

- **Features follow `docs/PLANNING_PROTOCOL.md`.** No code before the brief is approved.
- **UI changes need an approved mockup first.** Mockups are raw single-file HTML
  (`05-ui-mockup.html` in the spec folder), galaxy theme baked in, 390px and
  desktop layouts, synthetic data only. The product owner approves before tickets
  are planned; post-approval UI changes need a mockup update + re-approval.
- **Tests ship with every feature.** `php artisan test` green before a task is done.
- **Search official version-matched docs before building complex framework features.**
  For anything beyond plain CRUD (policies, queues, broadcasting, Scout,
  Reverb, Stripe Connect, Octane), read the version-matched docs first and cite
  the URL. Never code framework APIs from memory.
- **This is a live app.** Migrations must be backward-compatible. No breaking
  schema or contract change ships without a data/backfill plan. Refactors of
  hot paths (the timeline) ship behind the spec's rollout plan, not as drive-bys.
- **Keep the dev journal** (`dev-journal/`): `bug-fixes/` (symptom → root cause →
  fix → the recurrence guard), `domain-knowledge/` (topic-named),
  `progress/` (done / in progress / next / decisions). Scan it before starting work.
- Work happens in `feat/<slug>` branches. The builder never approves its own merge.
  `main` deploys to production — treat every merge as a production deploy:
  smoke-test the critical path (login → feed → post → event → checkout) after merge.

## Legacy agent system (do not extend)

`context-engine/`, `agent-swarm/`, `dev-logs/`, `GEMINI.md`, `WARP.md`, and the
`development-standards/` pattern library predate this protocol. They contain
real project knowledge (galaxy theme details, epic docs, code templates) —
**read them for context, do not extend them with new process.** New patterns
and primitives go into `development-standards/` templates or `UI_STANDARDS.md`
at Freeze, per `BUILD_PROTOCOL.md`. Consolidation or archival of the legacy
dirs is a future decision, not drive-by cleanup.
