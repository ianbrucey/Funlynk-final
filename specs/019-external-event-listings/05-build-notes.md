# 019 — Build notes

Built 2026-11-08 on `feat/019-external-event-listings`, following
`00-brief.md` and `01-archaeology-and-plan.md` as written. No brief
decision had to change; every claim test passes as named.

## Baseline and result

- Untouched branch reproduced the Nov 6 baseline exactly:
  417 passed / 2 skipped / 0 failed.
- Final suite on the finished branch: 432 passed / 2 skipped /
  0 failed (417 + the 15 new claim tests). Pint clean on touched
  files; PHPStan level 7 clean with the baseline untouched.
- The migration was also run against the dev database (real schema)
  and the kind/host CHECK constraint was verified both ways with raw
  inserts: hosted without a host is rejected, external without a
  host is accepted.

## Bug found by the claim tests (not in the archaeology)

`FeedService::paginateMerged` passed every event's `host_id` into
`FollowService::followedIds`, mapping null to `""`. Postgres rejects
`""` as a UUID, so the first feed page containing a listing crashed
the whole timeline. The archaeology counted `->host` reads but this
was a `host_id` value flowing into a UUID `whereIn`. Fixed by
filtering null host ids before the follow lookup. This is exactly
the failure mode the brief's pre-mortem predicted, one layer deeper.

## Choices worth knowing

- **Admin definition**: listings admin uses the app's existing
  pattern, an email allowlist gate (`config/listings.php`,
  env `LISTINGS_ADMIN_EMAILS`, same defaults as Horizon/Poster).
  The policy method `ActivityPolicy::isListingsAdmin` is the single
  source; a `listings-admin` Gate wraps it for Filament.
- **Interest vs. guest interests**: the new `activity_interests`
  table is signed-in user interest on listings only.
  `Activity::interests()` / `EventInterest` (guest engagement on
  hosted events) is untouched; the new relation is
  `interestedUsers()` with rows in `listingInterests()`.
- **Price chip**: a listing card shows a price only when
  `price_cents` is set, per the brief's "no price chip unless the
  price is known". A free listing shows no chip; the import format
  doc tells researchers to put an unknown price in the description
  instead of guessing.
- **For You feed**: it shares the same visibility helper as the
  nearby feed, so the kill switch and the listing expiry clock apply
  there too. Cap and recurring collapse apply to the merged
  nearby/following pagination, as planned.
- **Search**: drafts were already unsearchable via
  `shouldBeSearchable()` (status must be published); listings also
  return false there while `LISTINGS_ENABLED` is off.
- **C-04 test note**: the cap test creates posts before listings so
  the listings are the newest items; with the reverse order the
  listings simply fall off page one by recency, which is correct
  feed behavior and says nothing about the cap.
- **Public slug page**: a published listing at `/e/{slug}` redirects
  to the canonical detail page (`/events/{id}`), so there is exactly
  one listing detail surface. Unpublished listings 404 there, same
  as everywhere else.

## Not done (by design, per the brief)

Claiming, ticketing/RSVP/chat for listings, source images, and
auto-publishing remain out of v1. Nothing was merged to main and
nothing was pushed; the branch is ready for owner review.
