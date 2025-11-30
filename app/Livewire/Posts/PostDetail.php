<?php

namespace App\Livewire\Posts;

use App\Models\Post;
use App\Services\PostService;
use Illuminate\Support\Facades\Log;
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

    public function convertToEvent()
    {
        try {
            // Authorization check
            if ($this->post->user_id !== auth()->id()) {
                $this->dispatch('notify', [
                    'type' => 'error',
                    'message' => 'Unauthorized: Only post owner can convert',
                ]);

                return;
            }

            // Check eligibility
            if (! $this->post->isEligibleForConversion() || $this->post->status !== 'active') {
                $this->dispatch('notify', [
                    'type' => 'error',
                    'message' => 'Post is not eligible for conversion',
                ]);

                return;
            }

            // Prepare event data with smart defaults
            $eventData = [
                'title' => $this->post->title,
                'description' => $this->post->description ?: $this->post->title,
                'location_name' => $this->post->location_name,
                'location_coordinates' => $this->post->location_coordinates,
                'start_time' => $this->getSmartStartTime(),
                'end_time' => $this->getSmartEndTime(),
                'max_attendees' => max((int) ceil($this->post->reaction_count * 1.5), 10),
                'price' => 0, // Default to free
            ];

            // Convert post to event
            $activity = $this->postService->convertToEvent($this->post->id, $eventData, auth()->user());

            // Log for debugging
            Log::info('Post converted successfully', [
                'post_id' => $this->post->id,
                'activity_id' => $activity->id,
                'redirect_url' => route('activities.show', $activity->id),
            ]);

            // Redirect to event page with success message
            session()->flash('success', '🎉 Post converted to event successfully!');

            return redirect()->route('activities.show', $activity->id);
        } catch (\Exception $e) {
            Log::error('Failed to convert post', [
                'post_id' => $this->post->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            $this->dispatch('notify', [
                'type' => 'error',
                'message' => 'Failed to convert post: '.$e->getMessage(),
            ]);
        }
    }

    protected function getSmartStartTime(): string
    {
        if ($this->post->approximate_time) {
            $startTime = \Carbon\Carbon::parse($this->post->approximate_time);
            // If the time is in the past, move it to tomorrow at the same time
            if ($startTime->isPast()) {
                $startTime = now()->addDay()->setHour($startTime->hour)->setMinute($startTime->minute);
            }

            return $startTime->toIso8601String();
        }

        // Default: tomorrow at 6 PM
        return now()->addDays(1)->setHour(18)->setMinute(0)->toIso8601String();
    }

    protected function getSmartEndTime(): string
    {
        $startTime = \Carbon\Carbon::parse($this->getSmartStartTime());

        return $startTime->copy()->addHours(2)->toIso8601String();
    }

    public function render()
    {
        return view('livewire.posts.post-detail')
            ->layout('layouts.app');
    }
}
