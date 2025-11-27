<?php

namespace App\Notifications;

use App\Models\Comment;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class CommentOnYourContentNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public Comment $comment;

    /**
     * Create a new notification instance.
     */
    public function __construct(Comment $comment)
    {
        $this->comment = $comment;
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
        $commentable = $this->comment->commentable;
        $contentType = class_basename($commentable);
        $contentTitle = $commentable->title ?? 'your content';

        return [
            'type' => 'comment_on_content',
            'comment_id' => $this->comment->id,
            'commenter_id' => $this->comment->user_id,
            'commenter_name' => $this->comment->user->username,
            'content_type' => $contentType,
            'content_id' => $commentable->id,
            'content_title' => $contentTitle,
            'comment_preview' => substr($this->comment->content, 0, 100),
            'message' => "{$this->comment->user->username} commented on your {$contentType}: {$contentTitle}",
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
