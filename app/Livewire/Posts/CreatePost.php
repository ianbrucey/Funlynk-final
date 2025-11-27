<?php

namespace App\Livewire\Posts;

use App\Models\Post;
use App\Services\PostService;
use Livewire\Component;
use MatanYadaev\EloquentSpatial\Objects\Point;

class CreatePost extends Component
{
    // Form fields
    public $title = '';
    public $description = '';
    public $location_name = '';
    public $latitude = '';
    public $longitude = '';
    public $time_hint = '';
    public $mood = '';
    public $selectedTags = [];
    public $newTag = '';
    public $ttl_hours = 48;

    protected PostService $postService;

    public function boot(PostService $postService)
    {
        $this->postService = $postService;
    }

    protected function rules()
    {
        return [
            'title' => 'required|min:3|max:255',
            'description' => 'nullable|max:500',
            'location_name' => 'required|min:3',
            'latitude' => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180',
            'time_hint' => 'nullable|max:100',
            'mood' => 'nullable|in:creative,social,active,chill,adventurous',
            'ttl_hours' => 'required|integer|min:24|max:72',
        ];
    }

    protected function messages()
    {
        return [
            'title.required' => 'Please give your post a title',
            'title.min' => 'Title must be at least 3 characters',
            'description.max' => 'Description must be less than 500 characters',
            'location_name.required' => 'Please specify a location',
            'latitude.required' => 'Please provide location coordinates',
            'longitude.required' => 'Please provide location coordinates',
            'ttl_hours.min' => 'Post must last at least 24 hours',
            'ttl_hours.max' => 'Post cannot last more than 72 hours',
        ];
    }

    public function createPost()
    {
        $this->validate();

        try {
            // Prepare tags array
            $tags = array_column($this->selectedTags, 'name');

            // Create post using PostService
            $post = $this->postService->createPost([
                'user_id' => auth()->id(),
                'title' => $this->title,
                'description' => $this->description,
                'location_name' => $this->location_name,
                'latitude' => (float) $this->latitude,
                'longitude' => (float) $this->longitude,
                'time_hint' => $this->time_hint ?: null,
                'mood' => $this->mood ?: null,
                'tags' => $tags,
                'ttl_hours' => $this->ttl_hours,
            ]);

            session()->flash('success', 'Post created successfully!');

            return redirect()->route('feed.nearby');
        } catch (\Exception $e) {
            session()->flash('error', 'Error creating post: ' . $e->getMessage());
        }
    }

    public function setLocationData($name, $lat, $lng)
    {
        $this->location_name = $name;
        $this->latitude = $lat ? (float) $lat : null;
        $this->longitude = $lng ? (float) $lng : null;
    }

    public function addTag()
    {
        if (empty($this->newTag)) {
            return;
        }

        if (count($this->selectedTags) >= 5) {
            $this->addError('selectedTags', 'Maximum 5 tags allowed for posts.');
            return;
        }

        $tagName = trim($this->newTag);

        // Check if tag already selected
        foreach ($this->selectedTags as $tag) {
            if (strcasecmp($tag['name'], $tagName) === 0) {
                $this->reset('newTag');
                return;
            }
        }

        // Find or create tag
        $tag = \App\Models\Tag::firstOrCreate(['name' => $tagName]);
        if ($tag) {
            $this->selectedTags[] = [
                'id' => $tag->id,
                'name' => $tag->name
            ];
            $this->newTag = '';
        }
    }

    public function removeTag($index)
    {
        unset($this->selectedTags[$index]);
        $this->selectedTags = array_values($this->selectedTags);
    }

    public function render()
    {
        return view('livewire.posts.create-post')
            ->layout('layouts.app');
    }
}
