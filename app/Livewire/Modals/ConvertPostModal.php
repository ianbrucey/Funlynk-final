<?php

namespace App\Livewire\Modals;

use App\Models\Post;
use App\Models\Tag;
use Carbon\Carbon;
use Livewire\Component;
use Livewire\WithFileUploads;

class ConvertPostModal extends Component
{
    use WithFileUploads;

    public bool $show = false;

    public ?string $postId = null;

    public ?Post $post = null;

    // Form fields (pre-filled from post)
    public string $title = '';

    public string $description = '';

    public string $location_name = '';

    public $location_coordinates = null;

    public array $selectedTags = [];

    // New event fields
    public string $start_time = '';

    public string $end_time = '';

    public int $max_attendees = 10;

    public float $price = 0;

    public $image = null;

    // Preview data
    public int $interestedCount = 0;

    public int $invitedCount = 0;

    public bool $showPreview = false;

    protected $listeners = ['open-conversion-modal' => 'open'];

    public function open(string $postId)
    {
        $this->postId = $postId;
        $this->post = Post::with(['reactions', 'invitations'])->findOrFail($postId);

        // Authorization check
        if ($this->post->user_id !== auth()->id()) {
            $this->dispatch('notify', [
                'type' => 'error',
                'message' => 'Unauthorized',
            ]);

            return;
        }

        // Pre-fill form
        $this->preFillForm();

        // Load preview data
        $this->loadPreviewData();

        $this->show = true;
    }

    protected function preFillForm()
    {
        $this->title = $this->post->title;
        $this->description = $this->post->description ?? '';
        $this->location_name = $this->post->location_name;
        $this->location_coordinates = $this->post->location_coordinates;
        $this->selectedTags = $this->post->tags->pluck('id')->toArray();

        // Smart defaults for event fields
        if ($this->post->approximate_time) {
            $this->start_time = Carbon::parse($this->post->approximate_time)->format('Y-m-d\TH:i');
            $this->end_time = Carbon::parse($this->post->approximate_time)->addHours(2)->format('Y-m-d\TH:i');
        } else {
            $this->start_time = now()->addDays(1)->setHour(18)->setMinute(0)->format('Y-m-d\TH:i');
            $this->end_time = now()->addDays(1)->setHour(20)->setMinute(0)->format('Y-m-d\TH:i');
        }

        // Suggested capacity: reactions * 1.5, min 10
        $this->max_attendees = max((int) ceil($this->post->reaction_count * 1.5), 10);
    }

    protected function loadPreviewData()
    {
        // Will be implemented by Agent A
        // $preview = app(ActivityConversionService::class)->previewConversion($this->post, []);
        // $this->interestedCount = $preview['interested_users_count'];
        // $this->invitedCount = $preview['invited_users_count'];

        // Temporary: count directly
        $this->interestedCount = $this->post->reactions()->where('reaction_type', 'im_down')->count();
        $this->invitedCount = $this->post->invitations()->count();
    }

    public function togglePreview()
    {
        $this->showPreview = ! $this->showPreview;
    }

    public function getImagePreviewProperty()
    {
        if ($this->image) {
            return $this->image->temporaryUrl();
        }

        return null;
    }

    public function submit()
    {
        $this->validate([
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'location_name' => 'required|string',
            'start_time' => 'required|date|after:now',
            'end_time' => 'required|date|after:start_time',
            'max_attendees' => 'required|integer|min:1',
            'price' => 'required|numeric|min:0',
            'image' => 'nullable|image|max:2048',
        ]);

        try {
            $eventData = [
                'title' => $this->title,
                'description' => $this->description,
                'location_name' => $this->location_name,
                'location_coordinates' => $this->location_coordinates,
                'start_time' => $this->start_time,
                'end_time' => $this->end_time,
                'max_attendees' => $this->max_attendees,
                'price' => $this->price,
                'tags' => $this->selectedTags,
            ];

            // Handle image upload
            if ($this->image) {
                $eventData['image_path'] = $this->image->store('activities', 'public');
            }

            // Will be implemented by Agent A
            // $activity = app(PostService::class)->convertToEvent($this->postId, $eventData);

            $this->dispatch('notify', [
                'type' => 'success',
                'message' => 'Post converted to event successfully!',
            ]);

            // $this->dispatch('post-converted', activityId: $activity->id);

            $this->close();

            // Redirect to event page
            // return redirect()->route('activities.show', $activity->id);

        } catch (\Exception $e) {
            $this->dispatch('notify', [
                'type' => 'error',
                'message' => $e->getMessage(),
            ]);
        }
    }

    public function close()
    {
        $this->show = false;
        $this->reset();
    }

    public function render()
    {
        $availableTags = Tag::all();

        return view('livewire.modals.convert-post-modal', [
            'availableTags' => $availableTags,
        ]);
    }
}
