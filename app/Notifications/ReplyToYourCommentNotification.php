<?php

namespace App\Notifications;

use App\Models\Comment;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class ReplyToYourCommentNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public Comment $reply;

    /**
     * Create a new notification instance.
     */
    public function __construct(Comment $reply)
    {
        $this->reply = $reply;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        $commentable = $this->reply->commentable;
        $contentType = class_basename($commentable);

        return [
            'type' => 'comment_reply',
            'reply_id' => $this->reply->id,
            'replier_id' => $this->reply->user_id,
            'replier_name' => $this->reply->user->username,
            'parent_comment_id' => $this->reply->parent_comment_id,
            'content_type' => $contentType,
            'content_id' => $commentable->id,
            'reply_preview' => substr($this->reply->content, 0, 100),
            'message' => "{$this->reply->user->username} replied to your comment",
            'url' => $this->getContentUrl($commentable),
        ];
    }

    /**
     * Get the URL for the commentable content.
     */
    protected function getContentUrl($commentable): string
    {
        if ($commentable instanceof \App\Models\Post) {
            return route('posts.show', $commentable->id);
        }
        
        if ($commentable instanceof \App\Models\Activity) {
            return route('activities.show', $commentable->id);
        }

        return route('home');
    }
}
