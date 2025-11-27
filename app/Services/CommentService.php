<?php

namespace App\Services;

use App\Events\CommentCreated;
use App\Models\Comment;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class CommentService
{
    /**
     * Maximum allowed threading depth.
     */
    const MAX_DEPTH = 10;

    /**
     * Create a new comment on a commentable entity (Post or Activity).
     *
     * @param Model $commentable The entity to comment on (Post or Activity)
     * @param User $user The user creating the comment
     * @param string $content The comment content
     * @param Comment|null $parent The parent comment if this is a reply
     * @return Comment
     * @throws \Exception
     */
    public function createComment(
        Model $commentable,
        User $user,
        string $content,
        ?Comment $parent = null
    ): Comment {
        // Validate content
        $content = trim($content);
        if (empty($content)) {
            throw new \Exception('Comment content cannot be empty.');
        }

        if (strlen($content) > 500) {
            throw new \Exception('Comment content cannot exceed 500 characters.');
        }

        // Calculate depth for threading
        $depth = 0;
        if ($parent) {
            // Validate parent belongs to same commentable
            if ($parent->commentable_type !== get_class($commentable) ||
                $parent->commentable_id !== $commentable->id) {
                throw new \Exception('Parent comment does not belong to this entity.');
            }

            // Check max depth
            if ($parent->depth >= self::MAX_DEPTH) {
                throw new \Exception('Maximum reply depth reached.');
            }

            $depth = $parent->depth + 1;
        }

        // Create the comment
        $comment = Comment::create([
            'commentable_type' => get_class($commentable),
            'commentable_id' => $commentable->id,
            'user_id' => $user->id,
            'parent_comment_id' => $parent?->id,
            'depth' => $depth,
            'content' => $content,
            'is_edited' => false,
            'is_deleted' => false,
        ]);

        // Parse and handle @mentions
        $this->handleMentions($comment);

        // Broadcast the comment creation event
        event(new CommentCreated($comment));

        return $comment->load('user');
    }

    /**
     * Update an existing comment.
     *
     * @param Comment $comment
     * @param string $content
     * @return Comment
     * @throws \Exception
     */
    public function updateComment(Comment $comment, string $content): Comment
    {
        $content = trim($content);
        if (empty($content)) {
            throw new \Exception('Comment content cannot be empty.');
        }

        if (strlen($content) > 500) {
            throw new \Exception('Comment content cannot exceed 500 characters.');
        }

        $comment->update([
            'content' => $content,
            'is_edited' => true,
        ]);

        // Re-parse mentions in case they changed
        $this->handleMentions($comment);

        return $comment->fresh();
    }

    /**
     * Soft delete a comment.
     *
     * @param Comment $comment
     * @return bool
     */
    public function deleteComment(Comment $comment): bool
    {
        return $comment->delete();
    }

    /**
     * Get all comments for a commentable entity with threading.
     *
     * @param Model $commentable
     * @param int $perPage
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    public function getCommentsForEntity(Model $commentable, int $perPage = 20)
    {
        return Comment::where('commentable_type', get_class($commentable))
            ->where('commentable_id', $commentable->id)
            ->whereNull('parent_comment_id') // Only top-level comments
            ->with([
                'user',
                'replies' => function ($query) {
                    $query->with('user', 'replies.user', 'replies.replies.user')
                        ->orderBy('created_at', 'asc');
                },
                'reactions',
            ])
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);
    }

    /**
     * Parse @mentions in comment content and return mentioned usernames.
     *
     * @param Comment $comment
     * @return array
     */
    protected function handleMentions(Comment $comment): array
    {
        // Parse @username mentions (alphanumeric + underscore)
        preg_match_all('/@([\w]+)/', $comment->content, $matches);
        $usernames = array_unique($matches[1]);

        if (empty($usernames)) {
            return [];
        }

        // Find mentioned users
        $mentionedUsers = User::whereIn('username', $usernames)->get();

        // Store mentioned users (will be used by notifications in T06)
        // For now, just return them
        return $mentionedUsers->toArray();
    }

    /**
     * Get comment count for a commentable entity.
     *
     * @param Model $commentable
     * @return int
     */
    public function getCommentCount(Model $commentable): int
    {
        return Comment::where('commentable_type', get_class($commentable))
            ->where('commentable_id', $commentable->id)
            ->count();
    }

    /**
     * Validate that a comment can be replied to.
     *
     * @param Comment $comment
     * @return bool
     */
    public function canReply(Comment $comment): bool
    {
        return $comment->depth < self::MAX_DEPTH;
    }
}
