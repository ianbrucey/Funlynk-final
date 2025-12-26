<?php

namespace App\Livewire\Posts;

use App\Models\Post;
use App\Services\PostService;
use Illuminate\Support\Facades\Log;
use Livewire\Component;

class ReactionButton extends Component
{
    public Post $post;

    public string $reactionType = 'im_down';

    public string $label = "I'm down";

    public string $icon = '👍';

    public string $checkedIcon = '✓';

    public string $size = 'md'; // sm, md, lg

    public bool $fullWidth = false;

    public bool $isOwner = false;

    // Reactive state
    public bool $hasReacted = false;

    public int $reactionCount = 0;

    public function mount(Post $post): void
    {
        $this->post = $post;
        $this->refreshState();
    }

    protected function refreshState(): void
    {
        $this->hasReacted = $this->post->reactions
            ->where('user_id', auth()->id())
            ->where('reaction_type', $this->reactionType)
            ->isNotEmpty();

        $this->reactionCount = $this->post->reactions
            ->where('reaction_type', $this->reactionType)
            ->count();
    }

    public function react(): void
    {
        if ($this->isOwner) {
            return;
        }

        try {
            $result = app(PostService::class)->toggleReaction(
                $this->post->id,
                $this->reactionType
            );

            // Update local state immediately (optimistic update)
            if ($result['action'] === 'added') {
                $this->hasReacted = true;
                $this->reactionCount++;
            } else {
                $this->hasReacted = false;
                $this->reactionCount = max(0, $this->reactionCount - 1);
            }

            // Dispatch event for parent components that might need to know
            $this->dispatch('post-reacted',
                postId: $this->post->id,
                reactionType: $this->reactionType,
                action: $result['action']
            );

            Log::info('ReactionButton: reaction toggled', [
                'postId' => $this->post->id,
                'action' => $result['action'],
                'newCount' => $this->reactionCount,
            ]);
        } catch (\Exception $e) {
            Log::error('ReactionButton: failed to toggle reaction', [
                'error' => $e->getMessage(),
            ]);

            // Refresh state from database on error
            $this->post->refresh();
            $this->post->load('reactions');
            $this->refreshState();
        }
    }

    public function render()
    {
        return view('livewire.posts.reaction-button');
    }
}
