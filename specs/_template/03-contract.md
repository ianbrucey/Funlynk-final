# <NNN> — <Feature name> — Contract

**Status:** DRAFT (→ APPROVED)
**Date:** <YYYY-MM-DD>

> The contract is the buildable truth. Views and components may request only
> what this contract exposes. If implementation reveals a contract error, stop:
> update and reapprove this artifact before continuing.

---

## Domain services

| Method | Inputs | Outputs | Errors |
|---|---|---|---|
| <e.g. `FeedService::nearby(User $user, Point $at, int $radiusM)`> | <...> | <...> | <e.g. `InvalidRadiusException` → 422> |

## Routes

| Method | Path | Component/Controller | Auth |
|---|---|---|---|
| <e.g. GET> | </feed/nearby> | <NearbyFeed> | <auth> |

## Livewire components

| Component | Properties | Actions | Events emitted/listened |
|---|---|---|---|
| <...> | <...> | <...> | <...> |

## Validation

Every input lists its rules and the exact error for each invalid input.

| Input | Rules | Invalid → error |
|---|---|---|
| <...> | <...> | <...> |

## Authorization

**Every operation names its rule.** No operation without one.

| Operation | Actor | Rule (policy/gate) | Denied behavior |
|---|---|---|---|
| <e.g. delete post> | <post owner> | <PostPolicy::delete> | <403, no existence leak> |

## Payments (if touched)

| Operation | Idempotency | State transitions | Failure / rollback |
|---|---|---|---|
| <...> | <e.g. idempotency key per checkout session> | <...> | <...> |

## Real-time (if touched)

| Event | Channel | Authorized for | Payload |
|---|---|---|---|
| <...> | <e.g. private-chat.{id}> | <...> | <only contract fields> |

## Location (if touched)

- Coordinates in: <...>
- Radius applied: <...>
- Coordinates out: <e.g. rounded/never — exact points never leave the server>

## External integrations

| Integration | Failure behavior |
|---|---|
| <e.g. Stripe> | <...> |
| <e.g. Meilisearch> | <degrade to DB search, log, alert> |
