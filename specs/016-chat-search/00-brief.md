# 016 — Chat search and jump to message — Strategic brief

**Status:** BUILT (owner took the proposed defaults on 2026-10-26)
**Author:** Augment Agent (orchestrator draft)
**Date:** 2026-10-26

> The spec number 016 is a placeholder; the decided roadmap (BUILD_PROTOCOL §7) ends at 005.
> Origin: owner question 2026-10-26 about a year-old group chat. Follows PR #39 (cheaper history paging).

---

## Goal

In a long chat, anyone in it can find an old message by words and jump straight to it, without tapping "load earlier" dozens of times.

## Archaeology (what exists today, read from code)

- `ChatComponent` serves post, event, group and direct chats. It shows the latest 50 messages; "load earlier" adds 50 older ones (PR #39). Search was a non-goal of spec 012.
- Notification links and pins use `?m=<messageId>` and a `jump()` function. `jump()` can only scroll to a message that is already loaded. An old message outside the loaded window does nothing.
- `Message` is not Scout-searchable. Posts, users and activities use Scout and Meilisearch.
- `messages` has an index on `(conversation_id, created_at, id)` (PR #39).
- `ChatPolicy`-style access is `authorize('view', $conversation)`: only participants of the conversation read its messages.

## Proposed behaviour [P]

- A **search icon** in the chat header opens a search bar. Typing 2 or more characters lists matches in that one chat, newest first: sender, date, and the text with the match highlighted. 20 per page with "Show more".
- **Tapping a result jumps to the message.** If it is outside the loaded window, the chat loads the page of messages around it, scrolls to it and highlights it briefly. A "Back to latest" button returns to the newest messages.
- The same jump works for `?m=` links, pinned messages and reply quotes that point at old messages.
- Search covers message text only. Deleted messages and system messages are excluded.
- **Engine:** a Postgres case-insensitive match (`ILIKE`) limited to the one conversation. It is not Meilisearch. Reason: chats are private, and a second index would need its own copy of access rules and a sync job. One conversation holds thousands of rows, not millions, and the existing index narrows it first.
- Empty states: "Type at least 2 characters", "No messages match 'xyz'", and a loading state.

## Claims

| ID | Capability | Verdict (named binary test) | Blueprint anchor |
|---|---|---|---|
| C-01 | Search returns only matching messages from this conversation, newest first | `test_search_scoped_to_conversation` | Authorization |
| C-02 | A non-participant cannot search a conversation | `test_search_requires_participation` | Authorization |
| C-03 | Matching is case-insensitive, treats `%` `_` and `\` as plain characters, and ignores queries under 2 characters | `test_search_matching_rules` | Validation |
| C-04 | Deleted and system messages are never returned | `test_search_excludes_deleted_and_system` | Authorization |
| C-05 | Results page 20 at a time without duplicates | `test_search_pages_results` | Performance |
| C-06 | Jumping to an old message loads the window around it and includes it | `test_jump_loads_window_around_old_message` | Real-time |
| C-07 | A jump target from another conversation is refused | `test_jump_rejects_foreign_message` | Authorization |
| C-08 | "Back to latest" restores the newest window | `test_back_to_latest` | UX |
| C-09 | Search runs a constant number of queries per request | `test_search_query_count_constant` | Performance |

## Blueprint anchors

- [D] Authorization is server-side only: every search and jump re-checks `view` on the conversation.
- [D] Mobile-first at 390px; 44px targets.
- [D] Messages attach to a conversation; nothing here creates orphaned content.
- [D] 2026-10-26 (owner: "go with your proposals") Postgres `ILIKE` rather than Meilisearch (see Engine). → **Stop condition:** if the owner wants fuzzy or typo-tolerant search, this becomes a separate decision about indexing private content.
- [D] 2026-10-26 (owner: "go with your proposals") **Should results include the sender's name as a search term** (for example "from Dana")? Default: no, message text only.
- [D] 2026-10-26 (owner: "go with your proposals") **Should direct messages be searchable?** Default: yes, same rule (participants only).

## Production impact

- No schema change. Read-only queries on the existing table and index. Rollback: hide the icon.

## Security and privacy classification

- **Actors:** conversation participant. Everyone else is refused.
- **Data touched:** message text the actor can already read.
- **Authorization rules:** `view` on the conversation, checked on every search and jump request. A message id from another conversation never resolves.
- **Leak risk:** highlighted snippets must be escaped, never rendered as raw HTML.

## Pre-mortem

- **Likely failure:** a slow `ILIKE` scan in a very large chat. Mitigation: the conversation filter uses the index first, results are capped at 20 per page, and we can add a trigram index later without changing behaviour.
- **Assumption:** no single chat reaches millions of messages in the pilot.
- **Unknown:** whether people search by sender or date more than by words.

## Non-goals

- Cross-chat or global message search, fuzzy matching, attachments, filters by date or sender, search inside notifications.

## Approval gate

- [x] Orchestrator verdicts written (claims table complete)
- [ ] Builder has reviewed and added risks to the pre-mortem
- [ ] Governing [D]/[P]/[O] items linked; [O] stop conditions stated
- [x] Production impact + rollback plan stated
- [x] Security and privacy classification complete
- [ ] Product owner approved — **approver:** <name> · **date:** <YYYY-MM-DD>
