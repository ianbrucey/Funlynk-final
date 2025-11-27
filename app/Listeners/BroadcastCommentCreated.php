<?php

namespace App\Listeners;

use App\Events\CommentCreated;
use App\Models\User;
use App\Notifications\CommentMentionNotification;
use App\Notifications\CommentOnYourContentNotification;
use App\Notifications\ReplyToYourCommentNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class BroadcastCommentCreated implements ShouldQueue
{
    use InteractsWithQueue;

    /**
     * Handle the event.
     */
    public function handle(CommentCreated $event): void
    {
        $comment = $event->comment->load('user', 'commentable', 'parent.user');
        $commenter = $comment->user;
        $commentable = $comment->commentable;

        // 1. Notify content owner (Post/Activity creator)
        $contentOwner = null;
        if (isset($commentable->user_id)) {
            $contentOwner = User::find($commentable->user_id);
        } elseif (isset($commentable->host_id)) {
            $contentOwner = User::find($commentable->host_id);
        }

        // Don't notify if commenter is the content owner
        if ($contentOwner && $contentOwner->id !== $commenter->id) {
            $contentOwner->notify(new CommentOnYourContentNotification($comment));
        }

        // 2. Notify parent comment author (for replies)
        if ($comment->parent && $comment->parent->user) {
            $parentAuthor = $comment->parent->user;
            
            // Don't notify if replier is the parent author or content owner
            if ($parentAuthor->id !== $commenter->id && $parentAuthor->id !== $contentOwner?->id) {
                $parentAuthor->notify(new ReplyToYourCommentNotification($comment));
            }
        }

        // 3. Notify @mentioned users
        $this->notifyMentionedUsers($comment, $commenter);
    }

    /**
     * Parse @mentions and notify mentioned users.
     */
    protected function notifyMentionedUsers($comment, $commenter): void
    {
        // Parse @username mentions
        preg_match_all('/@([\w]+)/', $comment->content, $matches);
        $usernames = array_unique($matches[1]);

        if (empty($usernames)) {
            return;
        }

        // Find mentioned users
        $mentionedUsers = User::whereIn('username', $usernames)->get();

        foreach ($mentionedUsers as $user) {
            // Don't notify if mentioned user is the commenter
            if ($user->id !== $commenter->id) {
                $user->notify(new CommentMentionNotification($comment));
            }
        }
    }
}
