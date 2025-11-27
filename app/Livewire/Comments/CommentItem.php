<?php

namespace App\Livewire\Comments;

use App\Models\Comment;
use App\Services\CommentService;
use Livewire\Attributes\On;
use Livewire\Component;

class CommentItem extends Component
{
    public Comment $comment;
    public bool $showReplyForm = false;
    public bool $canReply = true;

    public function mount(Comment $comment)
    {
        $this->comment = $comment->load('user', 'replies.user', 'reactions');
        
        $commentService = app(CommentService::class);
        $this->canReply = $commentService->canReply($comment);
    }

    public function toggleReply()
    {
        if (!$this->canReply) {
            $this->dispatch('notify', [
                'message' => 'Maximum reply depth reached.',
                'type' => 'warning',
            ]);
            return;
        }
        
        $this->showReplyForm = !$this->showReplyForm;
    }

    #[On('reply-cancelled')]
    public function hideReplyForm()
    {
        $this->showReplyForm = false;
    }

    public function delete()
    {
        // Check authorization (will be handled by policy in T05)
        if ($this->comment->user_id !== auth()->id()) {
            $this->dispatch('notify', [
                'message' => 'You can only delete your own comments.',
                'type' => 'error',
            ]);
            return;
        }

        try {
            $commentService = app(CommentService::class);
            $commentService->deleteComment($this->comment);
            
            $this->dispatch('comment-deleted');
            $this->dispatch('notify', [
                'message' => 'Comment deleted successfully.',
                'type' => 'success',
            ]);
        } catch (\Exception $e) {
            $this->dispatch('notify', [
                'message' => 'Failed to delete comment.',
                'type' => 'error',
            ]);
        }
    }

    public function render()
    {
        return view('livewire.comments.comment-item');
    }
}
