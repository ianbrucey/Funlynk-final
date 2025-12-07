# Comment Notification Flow

## Notification Rules

### Rule 1: Content Owner Always Notified
```
Alice creates Post: "Looking for tennis partners"
Bob comments: "I'm down!"
→ Alice gets notification: "Bob commented on your post"

Alice creates Event: "Tennis at 5pm"
Charlie comments: "Can I bring a friend?"
→ Alice gets notification: "Charlie commented on your event"
```

### Rule 2: Reply Author Notified
```
Alice creates Post: "Looking for tennis partners"
Bob comments: "I'm down!"
Charlie replies to Bob: "What time works for you?"
→ Bob gets notification: "Charlie replied to your comment"
→ Alice gets notification: "Charlie commented on your post"
```

### Rule 3: Other Commenters NOT Notified (Prevents Spam)
```
Alice creates Post: "Looking for tennis partners"
Bob comments: "I'm down!"
Charlie comments: "Me too!"
David comments: "Count me in!"
Eve comments: "I'll join!"

→ Bob does NOT get notified about Charlie, David, or Eve
→ Charlie does NOT get notified about David or Eve
→ David does NOT get notified about Eve
→ Only Alice (content owner) gets all 4 notifications
```

### Rule 4: @Mentions Notified (Future Enhancement)
```
Alice creates Post: "Looking for tennis partners"
Bob comments: "@Charlie you should join us!"
→ Charlie gets notification: "Bob mentioned you in a comment"
→ Alice gets notification: "Bob commented on your post"
```

## Notification Types

### 1. CommentOnYourContentNotification
**Trigger**: Someone comments on your Post or Activity  
**Recipients**: Content owner (Post creator or Activity host)  
**Message**: "{User} commented on your {post/event}"  
**Action**: Navigate to detail page, scroll to comment

### 2. ReplyToYourCommentNotification
**Trigger**: Someone replies to your comment  
**Recipients**: Original comment author  
**Message**: "{User} replied to your comment"  
**Action**: Navigate to detail page, scroll to reply

### 3. CommentMentionNotification (Future)
**Trigger**: Someone @mentions you in a comment  
**Recipients**: Mentioned user  
**Message**: "{User} mentioned you in a comment"  
**Action**: Navigate to detail page, scroll to mention

## Opt-out Settings

Users can disable notifications in their settings:

```php
// user_notification_preferences table
[
    'user_id' => 1,
    'comments_on_my_content' => true,  // Can disable
    'replies_to_my_comments' => true,  // Can disable
    'comment_mentions' => true,        // Can disable
]
```

## Implementation

### In CommentService::createComment()
```php
public function createComment($data)
{
    $comment = Comment::create($data);
    
    // 1. Notify content owner (if not commenting on own content)
    if ($comment->commentable->owner_id !== auth()->id()) {
        $comment->commentable->owner->notify(
            new CommentOnYourContentNotification($comment)
        );
    }
    
    // 2. Notify parent comment author (if this is a reply)
    if ($comment->parent_id) {
        $parentComment = Comment::find($comment->parent_id);
        if ($parentComment->user_id !== auth()->id()) {
            $parentComment->user->notify(
                new ReplyToYourCommentNotification($comment)
            );
        }
    }
    
    // 3. Parse @mentions and notify (future)
    $this->notifyMentionedUsers($comment);
    
    return $comment;
}
```

## Real-time Broadcast

When a comment is created, broadcast to all viewers:

```php
// In CommentCreated event
public function broadcastOn(): array
{
    return [
        new Channel("comments.{$this->comment->commentable_type}.{$this->comment->commentable_id}"),
    ];
}
```

All users viewing the Post/Activity detail page will see the new comment instantly via Laravel Echo.

## Example Scenarios

### Scenario 1: Simple Comment Thread
```
Alice posts "Looking for tennis partners"
Bob comments "I'm down!"
Charlie comments "Me too!"

Notifications:
- Alice: "Bob commented on your post"
- Alice: "Charlie commented on your post"
- Bob: (none)
- Charlie: (none)
```

### Scenario 2: Reply Chain
```
Alice posts "Looking for tennis partners"
Bob comments "I'm down!"
Charlie replies to Bob: "What time?"
David replies to Charlie: "5pm works"

Notifications:
- Alice: "Bob commented on your post"
- Alice: "Charlie commented on your post"
- Alice: "David commented on your post"
- Bob: "Charlie replied to your comment"
- Charlie: "David replied to your comment"
```

### Scenario 3: Deep Threading
```
Alice posts "Looking for tennis partners"
Bob comments "I'm down!"
  └─ Charlie replies to Bob: "Me too!"
      └─ David replies to Charlie: "Great!"
          └─ Eve replies to David: "When?"

Notifications:
- Alice: 4 notifications (Bob, Charlie, David, Eve commented)
- Bob: 1 notification (Charlie replied)
- Charlie: 1 notification (David replied)
- David: 1 notification (Eve replied)
```

---

**Key Principle**: Only notify when directly relevant to you (your content, your comment, or you're mentioned). Don't spam users with every comment on a popular post.

