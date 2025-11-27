<?php

namespace App\Livewire\Comments;

use App\Models\Comment;
use App\Services\CommentService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\RateLimiter;
use Livewire\Component;

class CommentForm extends Component
{
    public Model $commentable;
    public ?Comment $parent = null;
    public string $content = '';
    public bool $showForm = true;

    public function mount(Model $commentable, ?Comment $parent = null)
    {
        $this->commentable = $commentable;
        $this->parent = $parent;
    }

    public function submit()
    {
        // Rate limiting check
        $key = 'comment-creation:' . auth()->id();
        
        if (RateLimiter::tooManyAttempts($key, 5)) {
            $seconds = RateLimiter::availableIn($key);
            $this->addError('content', "Too many comments. Please wait {$seconds} seconds.");
            return;
        }

        // Validate
        $this->validate([
            'content' => 'required|string|min:1|max:500',
        ]);

        try {
            $commentService = app(CommentService::class);
            
            $comment = $commentService->createComment(
                $this->commentable,
                auth()->user(),
                $this->content,
                $this->parent
            );

            // Hit rate limiter
            RateLimiter::hit($key, 60); // 60 seconds

            // Clear form
            $this->content = '';
            $this->reset('content');

            // Emit event to refresh comments
            $this->dispatch('comment-created');

            // Show success message
            session()->flash('comment-success', 'Comment posted successfully!');
            
        } catch (\Exception $e) {
            $this->addError('content', $e->getMessage());
        }
    }

    public function cancel()
    {
        $this->content = '';
        $this->showForm = false;
        $this->dispatch('reply-cancelled');
    }

    public function render()
    {
        return view('livewire.comments.comment-form');
    }
}
