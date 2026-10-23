# 008 — Follow a host, get notified of their new events — Strategic brief

**Status:** APPROVED 2026-10-23 (built; see Build notes)
**Author:** Augment Agent (orchestrator draft)
**Date:** 2026-10-23

> The spec number 008 is a placeholder; the decided roadmap (BUILD_PROTOCOL §7) ends at 005.
> Tracker item: F1 in `dev-journal/progress/2026-10-22-work-tracker.md`.

---

## Goal

A user who follows a host gets an in-app notification when that host publishes an event
the user is allowed to see, so following has a visible effect.

## Decisions (product owner, 2026-10-22)

- **[D]** Following a host notifies the follower when the host publishes an event (not "rank higher in the feed").
- **[D]** The follow button names the person ("Follow {name}") and a pop-up explains what following does. Those two are UI work (items F2/F3) and come after this spec ships, so the pop-up never promises something that does not exist.

## Archaeology (what exists today, read from code)

- `Follow` rows are written by `FollowButton`, `ShowProfile` and `SearchUsers`. Nothing reads them to notify anyone.
- Notifications are sent with `$user->notify(new GroupNotification(...))` through `CustomDatabaseChannel`, queued, from listeners registered in `EventServiceProvider`. `SendGroupNotification::handleGroupEventCreated` already notifies **group members** when a group event is created.
- `User.notification_preference` (`all`, `in_app_only`, `email_only`, `none`) is honoured by `NotifyInterestedUsers`. `NotificationPreferences` settings page exists.
- Events are created in four places: `CreateActivity` (standalone), `GroupContentService::createGroupEvent`, `RecurringScheduleService` (generates many occurrences at once), and `ActivityConversionService` (post converted to event).

## Claims

| ID | Capability | Verdict (named binary test) | Blueprint anchor |
|---|---|---|---|
| C-01 | A follower gets one in-app notification when the host publishes a standalone event | `test_follower_notified_when_host_publishes_event` | Events, Notifications |
| C-02 | A non-follower gets nothing | `test_non_follower_not_notified` | Notifications |
| C-03 | An event the follower cannot see (private-group event, follower not a member) never notifies | `test_private_group_event_does_not_notify_non_member_follower` | Authorization |
| C-04 | A follower who is also a member of the group gets one notification, not two | `test_group_member_follower_not_double_notified` | Notifications |
| C-05 | A follower with `notification_preference = none` gets nothing | `test_preference_none_suppresses_follow_notification` | Notifications |
| C-06 | A recurring schedule notifies at most once per schedule, not once per generated session | `test_recurring_schedule_sends_single_notification` | Notifications |
| C-07 | A post converted to an event notifies the host's followers once | `test_converted_event_notifies_followers` | Posts to Events |
| C-08 | The notification links to the event and names the host | `test_follow_notification_payload_links_event` | Notifications |
| C-09 | A host with many followers does not slow the publish request (work is queued and chunked) | `test_follow_notification_is_queued` | Performance |

## Blueprint anchors

- [D] Authorization is server-side only. Recipients are chosen by the same visibility rule as discovery (`Activity::discoverable()` plus the event policy), never in the view.
- [D] Every notification attaches to a post, event or group: this one attaches to the event.
- [D] 2026-10-23 **Recurring schedules:** notify once when the schedule is created ("started a recurring event"), linking the first session.
- [D] 2026-10-23 **Per-host mute:** not in this spec; the global preference applies (tracker F5 stays open).
- [D] 2026-10-23 **Email:** in-app only for the first version. Users with notification_preference `none` or `email_only` get no in-app follow notification.
- [P] Notification type string `followed-host-event`.

## Production impact

- No schema change expected (uses existing notifications table and `CustomDatabaseChannel`).
- The app has live users. Existing follows start producing notifications for events published after deploy; there is no backfill and no notification for past events.
- Rollback: unregister the listener. No data is lost.
- Hot path: publishing an event must not wait on notification fan-out. Dispatch one queued job that chunks followers.

## Security and privacy classification

- **Actors:** host, follower, group member, non-member.
- **Data touched:** follows, events, notifications.
- **Authorization rules:** recipient list = followers who may view the event. Private-group event: members only (already covered by the group notification), so followers who are not members are excluded.
- **Location exposure:** the notification carries title and host name only, no coordinates or address.
- **Payment touchpoints:** none.

## Evidence and fixtures

Hosts: one with 3 followers (one also a group member, one with preference `none`), one with none.
Events: standalone public, private-group, public-group, recurring schedule, converted post.
Adversarial: follower unfollows before the queued job runs; host blocks or deletes the event before send.

## Pre-mortem

- **Likely failure:** group members who follow the host are notified twice (group listener plus this one).
- **Assumption:** `Follow` rows are reliable. Earlier counter drift was fixed in PR #18 but old data may be uneven.
- **Unknown:** whether the queue worker runs in production for listeners (Horizon is in the stack).
- **Mitigation:** C-04 and C-09 are release gates; the job re-checks the follow still exists when it runs.

## Non-goals

- Follow-button label, pop-up and feed-card button (F2/F3/F4).
- Per-host mute and email delivery.
- A "Following" feed or ranking boost (FD1/FD2).
- Notifying about posts (events only).

## Approval gate

- [x] Orchestrator verdicts written (claims table complete)
- [x] Builder has reviewed and added risks to the pre-mortem
- [x] Governing [D]/[P]/[O] items linked; [O] stop conditions stated
- [x] Production impact + rollback plan stated
- [x] Security and privacy classification complete
- [x] Product owner approved — **approver:** product owner · **date:** 2026-10-23 ("go with your proposals")

## Build notes (2026-10-23)

- `App\Jobs\NotifyFollowersOfNewEvent` (queued, chunked, after commit) is dispatched from `CreateActivity`, `GroupContentService::createGroupEvent`, `RecurringScheduleService::createSchedule` (first session only) and `ActivityConversionService::createFromPost` (skips users who tapped "I'm down", who get the conversion notification).
- Notification type `followed_host_event`; clicking opens the event (bell and list).
- Recipients: followers who pass `ActivityPolicy::view`, minus group members and the host; the job re-reads follows when it runs.
- Tests: `tests/Feature/FollowNotificationTest.php` (12). Disabling the policy check makes the private-group test fail.
- Not verified: a running queue worker in production, and a browser check of the bell.
