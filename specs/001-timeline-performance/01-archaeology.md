# <NNN> — <Feature name> — Repository archaeology

**Status:** DRAFT (→ APPROVED)
**Author:** <builder or orchestrator name>
**Date:** <YYYY-MM-DD>
**Brief:** `00-brief.md` (must be APPROVED before this is written)

> Answer "what already exists?" before proposing anything new. Read the code —
> do not guess from memory. Check the version-matched framework docs for any
> API you plan to use (Laravel 12, Livewire v3, Filament v4); record the
> versions from the actual lockfiles.

---

## Findings

| Thing (code, migration, test, doc, config) | Location | Verdict | Constraint / note |
|---|---|---|---|
| <e.g. `Post` model> | `app/Models/Post.php` | REUSE | <use as-is; no changes> |
| <e.g. `PaymentService::charge()`> | `app/Services/PaymentService.php` | EXTEND | <add idempotency key; keep interface BC> |
| <e.g. feed virtualizer> | — | NEW | <no existing virtualization found> |
| <e.g. legacy `DistanceHelper::miles()>` | `app/Support/DistanceHelper.php` | CONFLICT | <hand-rolled distance math — must not be used; do not refactor here> |

**Verdict meanings:**
- **REUSE** — use as-is; the feature may call it but not change it.
- **EXTEND** — build on it; changes must keep backward compatibility and be listed in Files to touch.
- **NEW** — does not exist; will be created by this feature.
- **CONFLICT** — exists but contradicts this feature's contract or an architecture law; do not use, do not silently refactor — either scope the fix into the brief or leave it alone and note it.

## Architectural doors this feature must use

<Name the standing boundaries, e.g.:>
- All authorization goes through policies/gates server-side — components never gate data.
- All Stripe calls go through the payment service — controllers/Livewire never touch Stripe.
- All location queries go through the PostGIS spatial scopes.
- <...>

## Files to touch

| File | Action (create/edit) | Owner (worker) | Reason |
|---|---|---|---|
| <path> | <create/edit> | <worker name> | <why this file, what changes> |

<RULE: parallel workers must have disjoint file ownership. Shared files
(routes, service-provider bindings, shared layouts, core enums, migration
ordering) belong to the orchestrator unless explicitly delegated.>

## Protected files

<Paths this feature must NOT edit, and why — e.g. `routes/web.php` (orchestrator-owned),
`app/Policies/*` (owned by the security feature), migrations from other features.>

## What production does here today

<Routes, jobs, queues, and scheduled tasks this feature touches — so the deploy
has no surprises.>

## Baseline

<Record the actual state BEFORE this feature's work begins:>

- Tests: <e.g. `php artisan test` — 142 passed, 0 failed>
- Pint: <e.g. `vendor/bin/pint --test` — clean>
- PHPStan: <e.g. level 7 — no errors, or "not yet configured">
- Asset build: <e.g. `npm run build` — success>
- Framework/package versions checked: <e.g. laravel/framework 12.x, livewire 3.6 from composer.lock>
