<?php

namespace App\Livewire\Tags;

use App\Models\Tag;
use App\Services\TagService;
use Livewire\Component;

class TagAutocomplete extends Component
{
    public $search = '';
    public $selectedTags = [];
    public $suggestions = [];
    public $showSuggestions = false;
    public $maxTags = 10;
    
    protected TagService $tagService;

    public function boot(TagService $tagService)
    {
        $this->tagService = $tagService;
    }

    public function mount($selectedTags = [])
    {
        $this->selectedTags = is_array($selectedTags) ? $selectedTags : [];
    }

    public function updatedSearch()
    {
        if (strlen($this->search) >= 2) {
            $this->suggestions = $this->tagService->getSuggestions($this->search, 10)->toArray();
            $this->showSuggestions = true;
        } else {
            $this->suggestions = [];
            $this->showSuggestions = false;
        }
    }

    public function selectTag($tagId)
    {
        $tag = Tag::find($tagId);
        
        if ($tag && !in_array($tag->id, array_column($this->selectedTags, 'id'))) {
            if (count($this->selectedTags) < $this->maxTags) {
                $this->selectedTags[] = [
                    'id' => $tag->id,
                    'name' => $tag->name,
                    'category' => $tag->category ?? null,
                ];
                
                $this->dispatch('tagsUpdated', $this->selectedTags);
            }
        }
        
        $this->search = '';
        $this->suggestions = [];
        $this->showSuggestions = false;
    }

    public function createAndSelectTag()
    {
        if (strlen($this->search) < 2) {
            return;
        }

        // Validate tag
        $validation = $this->tagService->validateTag($this->search);
        
        if (!$validation['valid']) {
            session()->flash('error', $validation['message']);
            return;
        }

        // Create new tag
        $tag = $this->tagService->createTag($this->search);
        
        // Select the newly created tag
        $this->selectTag($tag->id);
        
        session()->flash('success', 'Tag created successfully!');
    }

    public function removeTag($index)
    {
        if (isset($this->selectedTags[$index])) {
            unset($this->selectedTags[$index]);
            $this->selectedTags = array_values($this->selectedTags); // Re-index array
            
            $this->dispatch('tagsUpdated', $this->selectedTags);
        }
    }

    public function clearAll()
    {
        $this->selectedTags = [];
        $this->dispatch('tagsUpdated', $this->selectedTags);
    }

    public function hideSuggestions()
    {
        // Delay hiding to allow click events to fire
        $this->showSuggestions = false;
    }

    public function render()
    {
        return view('livewire.tags.tag-autocomplete');
    }
}
