<?php

namespace App\Livewire\Posts;

use App\Models\Post;
use Livewire\Component;

class PostDetail extends Component
{
    public Post $post;

    public function mount(Post $post)
    {
        $this->post = $post->load('user', 'reactions');
    }

    public function reactToPost(string $postId, string $reactionType)
    {
        $post = Post::findOrFail($postId);

        $existingReaction = $post->reactions()
            ->where('user_id', auth()->id())
            ->where('reaction_type', $reactionType)
            ->first();

        if ($existingReaction) {
            $existingReaction->delete();
        } else {
            $post->reactions()->create([
                'user_id' => auth()->id(),
                'reaction_type' => $reactionType,
            ]);
        }

        $this->post->refresh();
    }

    public function render()
    {
        return view('livewire.posts.post-detail')
            ->layout('layouts.app');
    }
}
