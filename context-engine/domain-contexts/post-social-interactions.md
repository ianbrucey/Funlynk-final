# Post Social Interactions Documentation

## Overview
This document outlines the architecture and data flow for the social interaction buttons found on the `post-card-compact` component within the Nearby Feed. These interactions drive the core "Posts vs Events" dual model by measuring engagement and facilitating viral discovery.

**Location**: `resources/views/components/post-card-compact.blade.php`

---

## 1. "I'm Down" Button

### Purpose
Allows users to express interest in a spontaneous post. This is the primary signal for the **Post-to-Event Conversion** logic (E03/E04).

### Data Flow
1.  **User Action**: Click "I'm Down" button on post card.
2.  **Frontend**: Triggers Livewire method `reactToPost` on the parent component (`NearbyFeed`).
3.  **Livewire**: `NearbyFeed::reactToPost($postId, 'im_down')` delegates to `PostService`.
4.  **Service**: `PostService::toggleReaction` handles the business logic.
5.  **Database**:
    *   Checks for existing `PostReaction`.
    *   **If exists**: Deletes record (toggle off).
    *   **If new**: Creates `PostReaction` record.
    *   Updates `reaction_count` on `posts` table (denormalized).
6.  **Events**: Dispatches `App\Events\PostReacted`.
7.  **Feedback**:
    *   Livewire dispatches `post-reacted` browser event.
    *   `NearbyFeed` reloads items to reflect new counts/state.

### Key Code Artifacts

**Component View** (`post-card-compact.blade.php`):
```blade
<button
    wire:click.stop="reactToPost('{{ $post->id }}', 'im_down')"
    class="...">
    <span>{{ $userHasReacted ? '✓' : '👍' }} I'm down</span>
</button>
```

**Livewire Component** (`app/Livewire/Discovery/NearbyFeed.php`):
```php
public function reactToPost($postId, $reactionType)
{
    // ... logging ...
    $result = app(\App\Services\PostService::class)->toggleReaction($postId, $reactionType);
    $this->dispatch('post-reacted', ...);
    $this->loadItems(); // Refresh UI
}
```

**Service Logic** (`app/Services/PostService.php`):
```php
public function toggleReaction(string $postId, string $reactionType, ?User $user = null): array
{
    // Transactional toggle logic
    // Updates post.reaction_count
    // Checks conversion eligibility (5+ reactions)
    // Fires PostReacted event
}
```

### Integration Points
*   **E04 (Discovery)**: The `PostReacted` event carries `eligibility` data (e.g., `eligible` for conversion if count >= 5). This triggers the conversion prompt flow.

---

## 2. "Invite Friends" Button

### Purpose
Facilitates viral growth by allowing users to privately invite mutual followers to check out a post.

### Data Flow
1.  **User Action**: Click "Invite" button on post card.
2.  **Frontend**: Dispatches Alpine/Livewire event `openInviteModal` with `postId`.
3.  **Modal Component**: `App\Livewire\Posts\InviteFriendsModal` listens for event.
    *   Sets `postId`.
    *   Loads friends (users with mutual follow status).
    *   Sets `show = true` to display modal.
4.  **User Interaction**: User selects friends and clicks "Invite".
5.  **Livewire**: `InviteFriendsModal::inviteFriends()` delegates to `PostService`.
6.  **Service**: `PostService::inviteFriendsToPost` handles logic.
7.  **Database**:
    *   Creates `PostInvitation` records for each selected friend.
8.  **Events**: Dispatches `App\Events\PostInvitationSent` for each invitation.
9.  **Feedback**:
    *   Modal closes.
    *   Success flash message displayed.

### Key Code Artifacts

**Component View** (`post-card-compact.blade.php`):
```blade
<button
    wire:click.stop="$dispatch('openInviteModal', { postId: '{{ $post->id }}' })"
    class="...">
    <span>📨 Invite</span>
</button>
```

**Modal Component** (`app/Livewire/Posts/InviteFriendsModal.php`):
```php
protected $listeners = ['openInviteModal'];

public function openInviteModal(string $postId): void
{
    $this->postId = $postId;
    $this->loadFriends(); // Loads mutuals
    $this->show = true;
}

public function inviteFriends(): void
{
    app(\App\Services\PostService::class)->inviteFriendsToPost(
        $this->postId,
        $this->selectedFriends,
        auth()->user()
    );
    // ... flash success & reset ...
}
```

**Service Logic** (`app/Services/PostService.php`):
```php
public function inviteFriendsToPost(string $postId, array $friendIds, ?User $inviter = null): Collection
{
    // Creates PostInvitation records
    // Fires PostInvitationSent event for notifications
}
```

### Database Schema
*   **Table**: `post_invitations`
*   **Model**: `App\Models\PostInvitation`
*   **Relationships**: `post_id`, `inviter_id`, `invitee_id`, `status` ('pending', 'viewed', etc.)

---

## Architecture Notes

*   **Service Pattern**: Both interactions heavily rely on `PostService` to keep controllers thin and business logic reusable.
*   **Event-Driven**: Both actions trigger Laravel Events (`PostReacted`, `PostInvitationSent`) which likely handle side effects like Notifications (E01) and Activity Feeds (E05).
*   **Optimistic UI**: The "I'm Down" button relies on `loadItems()` to refresh state, which might feel slightly delayed compared to a purely optimistic frontend update.
*   **Modal Isolation**: The Invite logic is encapsulated in a separate Livewire component (`InviteFriendsModal`), keeping the main feed component cleaner.
