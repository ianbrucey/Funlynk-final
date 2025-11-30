<?php

namespace App\Livewire\Posts;

use App\Models\Post;
use App\Services\PostService;
use Livewire\Component;

class PostDetail extends Component
{
    public Post $post;

    protected PostService $postService;

    public function boot(PostService $postService)
    {
        $this->postService = $postService;
    }

    public function mount(Post $post)
    {
        $this->post = $post->load('user', 'reactions');
    }

    public function reactToPost(string $postId, string $reactionType)
    {
        // Use PostService to handle reaction toggle
        // This ensures reaction_count is updated and events are dispatched
        $this->postService->toggleReaction($postId, $reactionType, auth()->user());

        // Refresh post to get updated reaction_count and reactions
        $this->post->refresh();
        $this->post->load('reactions');
    }

    public function render()
    {
        return view('livewire.posts.post-detail')
            ->layout('layouts.app');
    }
}
