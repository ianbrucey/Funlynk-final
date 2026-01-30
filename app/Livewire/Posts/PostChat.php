<?php

namespace App\Livewire\Posts;

use App\Models\Post;
use Livewire\Component;

class PostChat extends Component
{
    public Post $post;
    public $isGroupPost = false;
    public $group = null;

    public function mount(Post $post)
    {
        $this->post = $post->load('user', 'reactions', 'group');

        // Check if this is a group post
        $this->isGroupPost = $this->post->group_id !== null;
        $this->group = $this->post->group;
    }

    public function render()
    {
        return view('livewire.posts.post-chat')
            ->layout('layouts.app');
    }
}
