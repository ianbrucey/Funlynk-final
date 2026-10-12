# Post → Event conversion failed for every post (2026-10-09)

Spec: `specs/000-suite-repair/` (K3 + execution findings, decisions 000-D03, D06, D07).

## Symptom
Every post-to-event conversion threw `QueryException` and rolled back.
Masked in production by the broken test suite (61 failures, Pest not gating CI).

## Root causes (stacked — each hid the next)
1. `ActivityConversionService::createFromPost()` wrote `activities.is_paid`,
   dropped by migration `2026_01_30_144455` (replaced by `payment_type`).
2. It passed `Str::uuid()` (a `Uuid` object) as `id`, so the returned model's
   `id` was not a string.
3. It wrote `description = NULL` for posts without a description
   (`activities.description` is NOT NULL; `posts.description` is nullable).
4. A non-numeric price raised `TypeError` (`string * int`), which is not an
   `\Exception` and escaped callers' handlers.
5. `MigratePostInvitations` (sync listener, runs inside the conversion
   transaction) wrote `post_invitations.status = 'migrated'`, rejected by
   `post_invitations_status_check`.
6. The same listener created notifications without NOT NULL `title`,
   `message`, `delivery_method`, and read nonexistent `activities.price`
   (so `is_free` was always true).

## Fix
- Service: `payment_type` (`online`/`free`), `(string) Str::uuid()`,
  description falls back to `''`, non-numeric price → `InvalidArgumentException`.
- Migration `2026_10_09_200347_add_migrated_status_to_post_invitations_table`
  (additive; owner-approved) adds `migrated` to the CHECK constraint.
- Listener: notification `title`/`message`/delivery fields; price from
  `price_cents`, `is_free` from `payment_type`.

## Recurrence guard
- `tests/Feature/Services/ActivityConversionServiceTest.php`
- `tests/Feature/Services/PostServiceConversionExecutionTest.php`
- `tests/Feature/Events/PostConversionEventsTest.php`
- Pest is now a hard CI gate (`.github/workflows/tests.yml`).

## Lesson
A column drop must grep every writer (`is_paid`) — the `Activity` model's
`getIsPaidAttribute()` accessor kept reads working and hid the broken write.
