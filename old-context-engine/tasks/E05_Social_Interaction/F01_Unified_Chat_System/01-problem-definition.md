# 01 Problem Definition

## The Problem
The current "Comment" model is too static for FunLynk's use case. FunLynk is about *doing things now* (spontaneous activities). Users need to coordinate logistics ("I'm 5 mins away", "Where are we meeting?"), not just leave static remarks. A traditional comment section feels disconnected and slow.

Additionally, maintaining separate systems for "Comments" and "Private Messages" creates technical debt and inconsistent UI/UX.

## The Solution
We will implement a **Unified Chat System**.
- **Unified Backend**: A single `Conversation` and `Message` architecture that handles both public post discussions and private DMs.
- **Unified Frontend**: A single, polished Chat UI component.
- **Context-Aware**:
    - When attached to a Post, it acts as a "Group Chat" for that post.
    - When standalone, it acts as a DM.

## Key Decisions
1.  **Abandon `Comment` Model**: We will move away from the `Comment` terminology and schema in favor of `Conversation` and `Message`.
2.  **Implicit Roster**: For Post chats, users are "added" to the conversation when they interact (send a message, RSVP).
3.  **Real-time First**: The system is designed for WebSocket updates from day one.

## Questions for User
- Should we migrate existing comments (if any) or just start fresh?
- Do we need "Threaded" replies in a Chat context, or just "Quote Reply"? (Recommendation: Quote Reply)
- How do we handle "Muting" notifications for busy posts?
