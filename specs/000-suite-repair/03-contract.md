# 000 — Test suite repair — Contract

**Status:** APPROVED
**Date:** 2026-10-08

## Summary

**N/A — no new public contracts.** This spec adds no routes, services, or
component APIs, with one exception:

### Exception — K5 route restoration

If execution confirms no existing route serves the public event page, the
restored route must satisfy the three existing references exactly:

| Item | Requirement |
|---|---|
| Route name | `events.public` |
| Parameter | activity slug |
| Access | guest-accessible (public event page) |
| Consumers | `SocialShareService` (share URLs), `social-meta.blade.php` (og:url), `PublicEventViewTest` |

### Standing contracts this spec must not break

- `PostService::convertToEvent()` signature and return (`Activity`) — unchanged;
  only the persistence payload inside `ActivityConversionService` changes (K3).
- `ConversionEligibilityService` result shape (`should_prompt`, `reason`,
  `threshold`, `reaction_count`) — unchanged; only threshold *values* may
  change, and only per the K6 owner decision.
- Notification payload keys consumed by existing components — K7 restores the
  `message` key the components/tests already expect, or documents the rename
  in `decisions.md` if the rename was intentional.
