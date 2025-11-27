<?php

namespace App\Livewire\Comments;

use App\Models\Comment;
use App\Services\CommentService;
use Illuminate\Database\Eloquent\Model;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithPagination;

class CommentSection extends Component
{
    use WithPagination;

    public Model $commentable;
    public string $commentableType;
    public string $commentableId;
    public int $totalComments = 0;

    public function mount(string $commentableType, string $commentableId)
    {
        $this->commentableType = $commentableType;
        $this->commentableId = $commentableId;
        
        // Resolve the commentable model
        $this->commentable = app($commentableType)->findOrFail($commentableId);
        
        $this->refreshCommentCount();
    }

    public function refreshCommentCount()
    {
        $commentService = app(CommentService::class);
        $this->totalComments = $commentService->getCommentCount($this->commentable);
    }

    #[On('comment-created')]
    public function refreshComments()
    {
        $this->refreshCommentCount();
        $this->resetPage();
    }

    #[On('comment-deleted')]
    public function handleCommentDeleted()
    {
        $this->refreshCommentCount();
    }

    public function render()
    {
        $commentService = app(CommentService::class);
        $comments = $commentService->getCommentsForEntity($this->commentable, 20);

        return view('livewire.comments.comment-section', [
            'comments' => $comments,
        ]);
    }
}
