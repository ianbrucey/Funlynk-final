<?php

namespace App\Livewire\Profile;

use App\Models\User;
use App\Services\PostService;
use Livewire\Component;
use Livewire\WithPagination;

class InterestedTab extends Component
{
    use WithPagination;

    public User $user;

    public string $filter = 'active';

    public function mount(User $user)
    {
        $this->user = $user;
    }

    public function setFilter(string $filter)
    {
        $this->filter = $filter;
        $this->resetPage();
    }

    public function removeInterest(string $postId)
    {
        abort_unless(auth()->id() === $this->user->id, 403);

        // Call PostService to remove reaction
        app(PostService::class)->toggleReaction($postId, 'im_down');

        $this->dispatch('post-interest-removed');
        $this->dispatch('notify', [
            'type' => 'success',
            'message' => 'Interest removed successfully',
        ]);
    }

    public function render()
    {
        $viewer = auth()->user();
        $isOwner = $viewer !== null && $viewer->id === $this->user->id;
        abort_unless($this->user->allowsProfileContentFor($viewer), 403);

        $posts = $this->user->getInterestedPosts($this->filter, publicOnly: ! $isOwner);

        return view('livewire.profile.interested-tab', [
            'posts' => $posts,
        ]);
    }
}
