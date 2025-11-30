<?php

namespace App\Mail;

use App\Models\Activity;
use App\Models\Post;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class PostConvertedToEventMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public User $recipient,
        public Post $post,
        public Activity $activity,
        public User $host,
    ) {}

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        $hostName = $this->host->display_name ?? $this->host->username;
        return new Envelope(
            to: [new Address($this->recipient->email, $this->recipient->display_name ?? $this->recipient->username)],
            subject: "🎉 {$hostName} created an event from '{$this->post->title}'",
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.post-converted-to-event',
            with: [
                'recipient' => $this->recipient,
                'post' => $this->post,
                'activity' => $this->activity,
                'host' => $this->host,
                'eventUrl' => route('activities.show', $this->activity->id),
                'rsvpUrl' => route('activities.show', $this->activity->id) . '#rsvp',
            ],
        );
    }

    /**
     * Get the attachments for the message.
     */
    public function attachments(): array
    {
        return [];
    }
}
