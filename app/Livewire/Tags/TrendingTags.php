<?php

namespace App\Livewire\Tags;

use App\Services\TagService;
use Livewire\Component;

class TrendingTags extends Component
{
    public $limit = 10;
    public $days = 7;
    public $showUsageCount = true;
    public $clickable = true;

    protected TagService $tagService;

    public function boot(TagService $tagService)
    {
        $this->tagService = $tagService;
    }

    public function mount($limit = 10, $days = 7, $showUsageCount = true, $clickable = true)
    {
        $this->limit = $limit;
        $this->days = $days;
        $this->showUsageCount = $showUsageCount;
        $this->clickable = $clickable;
    }

    public function tagClicked($tagId)
    {
        if ($this->clickable) {
            $this->dispatch('tagSelected', $tagId);
        }
    }

    public function render()
    {
        $trendingTags = $this->tagService->getTrendingTags($this->limit, $this->days);

        return view('livewire.tags.trending-tags', [
            'trendingTags' => $trendingTags,
        ]);
    }
}
