# Groups audit — 2026-10-18

Scope: group creation, group page, group posts, group events, recurring events.
Method: read PHP only (services, models, policy, Livewire components). Blade views were **not** read and nothing was run against the app. Item 1 below is unconfirmed until the views are checked.

## How groups work today

**Data model**
- `Group` has memberships with role `admin` or `member`. The creator gets `admin` on creation.
- Privacy is `public` (join directly) or `private` (join requests).
- `post_permission` and `event_permission` decide whether `everyone` or only admins can post / create events.
- A group has one chat conversation, tags, posts, and events (events are `Activity` rows with `group_id`).

**Group posts**
- `CreateGroupPost` checks `Group::canCreatePost`, then `GroupContentService::createGroupPost` creates a `Post` with `group_id` and a 24h / 48h / 1 week expiry, then fires `GroupPostCreated`.
- Title is the first 50 characters of the text. Location is name-only (no coordinates).

**Group events**
- `CreateGroupEvent` -> `createGroupEvent` makes an `Activity` of type `group_event`, `is_public` false.

**Recurring events**
- A `RecurringSchedule` is a template (weekly days, daily, or day of month, plus start time). Each generated date is an *occurrence*.
- `RecurringScheduleService` creates real `Activity` rows for the next `generate_weeks_ahead` weeks (default 4), tagged with `recurring_schedule_id` and `recurrence_date`.
- `GenerateRecurringEvents` job tops up schedules weekly, Sundays 00:00.
- Cancelling one occurrence sets that activity's status to `cancelled`.

**Timeline**
- `GroupContentService::getGroupTimeline` loads all posts and all events, merges, sorts in PHP, slices by page.

## Findings

### Authorization and correctness
1. **[Resolved 2026-10-19 — public access is by design]** Public groups are intentionally open so a group link can be shared with people outside the platform (sign-up then registers them with the group). Private group timelines now return 403 for non-members. Original note: **Timeline and show page may be open to non-members.** `GroupTimeline` (`/groups/{slug}/timeline`) has no membership or privacy check in PHP. `GroupShow` loads for any logged-in user, including private groups. Need to read the views to see what a non-member gets. Highest priority.
2. **`GroupTimeline::deleteItem` and `likeItem` are placeholders.** They flash a message but delete/like nothing. `deleteItem` also reads `$item->type`, which models may not have.
3. **Inconsistent admin checks.** `GroupShow` treats the creator as admin even without an admin membership row. `GroupPolicy` and `Group::isAdmin` count membership rows only. Most Livewire components skip the policy and call `isAdmin` / `canCreatePost` directly.
4. **`GroupShow::editGroup` and `deleteGroup` are stubs.** `deleteGroup` deletes but redirects nowhere. Settings has its own page, so these look dead.
5. **Slug collisions.** `createGroup` uses `str($name)->slug()` with no uniqueness handling; `slug` is unique, so a duplicate name causes a DB error.
6. **Last-admin guard is partial.** `removeMember` blocks removing the last admin; `updateMemberRole` can demote the last admin.

### Recurring events
7. **First week can be skipped; edits don't propagate.** `last_generated_until` is set to the window end even if generation fails partway. `updateSchedule` does not regenerate or fix existing events, so old occurrences keep the old time.
8. **Monthly schedules miss short months.** `day_of_month = 31` never matches Feb, Apr, etc.
9. **Time zones unchecked.** `now()` and stored times use the app time zone. Not verified how the zone is set.
10. **Weekly job only runs Sundays.** A short window could run out of events before the next run. Confirm the 4-week minimum is enforced.

### Performance
11. **Timeline loads everything.** All posts and events (with `rsvps.user`), expired posts included, sorted and sliced in PHP. `GroupShow::mount` also eager-loads all members, posts, activities. Conflicts with "timeline must feel instant"; the feed already has a cursor-pagination pattern to reuse.
12. **`GroupTimeline::$items` is public Livewire state holding Eloquent models.** Serialised on every request. Spec 001 moved the feed to plain-array payloads for this reason.

### Product gaps
13. Group posts cannot convert to events or be geo-discoverable; group events are never public. Strangers cannot discover a group's events.
14. Recurring-schedule UI is admin-only. Members can RSVP to individual events but not to a series.

## Proposed order (proposal, not a decision)
1. Authorization gaps (1, 3, 6).
2. Correctness bugs (5, 7, 8).
3. Timeline: cursor pagination + plain-array items (11, 12).
4. Admin toolkit (fundraising, receipts, role/equipment assignment) as its own spec — see shelf item **E1a** in `2026-10-18-brain-dump-triage.md`.

## Next step
Read the group Blade views and test item 1 against the running app.
