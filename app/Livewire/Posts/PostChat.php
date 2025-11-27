<?php

namespace App\Livewire\Posts;

use App\Models\Post;
use Livewire\Component;

class PostChat extends Component
{
    public Post $post;

    public function mount(Post $post)
    {
        $this->post = $post->load('user', 'reactions');
    }

    public function render()
    {
        return view('livewire.posts.post-chat')
            ->layout('layouts.app');
    }
}
