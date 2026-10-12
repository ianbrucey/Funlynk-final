# C-05 — First cards visible < 1s on Slow 4G — NOT MEASURED

**Date:** 2026-10-12
**Claim:** Browser check at 390px with Chrome "Slow 4G" throttling against
Laravel Cloud: first card visible < 1 s; recording/trace in `07-evidence/`.

## Gap

This claim is **not verified**. The build environment has no reachable
Laravel Cloud / staging URL — `docs/LARAVEL_CLOUD_ENV_SETUP.md` documents the
backing services (Postgres/Redis/Meilisearch/MinIO on the Hetzner box) but not
a deployed, publicly reachable app URL, and `.env`/`.env.example` both point
`APP_URL` at `http://localhost`. No credentials or domain for a live
deployment were available to the agent performing this build.

Per `06-plan.md` Ticket 5: *"if no staging deploy exists yet, record that gap
explicitly rather than guessing a pass."* This file is that record.

## What *is* verified, as a partial substitute

- `#[Lazy]` + `placeholder()` is implemented and tested
  (`test_feed_page_renders_shell_with_skeleton`, C-04): the initial response
  renders the skeleton shell and runs **no** feed query; items load on the
  follow-up Livewire request. This is the mechanism C-05 depends on — the
  page shell is no longer blocked on the feed query — but the *time budget*
  itself (< 1 s to first card on real Slow 4G against the real deploy) has
  not been measured.
- C-01 (server feed data timing) is measured and green: p95 41.93 ms at
  10,000 posts, far inside the 200 ms budget — so the server side of the
  budget is not the risk; the unmeasured part is network + asset weight on
  an actual Slow 4G connection to the real deployment.

## Required follow-up before this claim can close

1. Confirm the Laravel Cloud (or other) deployment URL for `feat/001-timeline-performance`
   once merged, or stand up a preview environment.
2. Run the browser check at 390px with Chrome DevTools "Slow 4G" network
   throttling against that URL; capture a trace/recording in `07-evidence/`.
3. If it fails the < 1 s budget, open a follow-up ticket — do not weaken C-05
   to pass.

## Decision record

See `decisions.md` → 001-D12.
