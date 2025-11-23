<?php

namespace App\Jobs;

use App\Models\Tag;
use App\Services\TagService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;

class UpdateTagAnalytics implements ShouldQueue
{
    use Queueable;

    public ?string $tagId;

    /**
     * Create a new job instance.
     * 
     * @param string|null $tagId If provided, only update this tag. Otherwise update all tags.
     */
    public function __construct(?string $tagId = null)
    {
        $this->tagId = $tagId;
    }

    /**
     * Execute the job.
     */
    public function handle(TagService $tagService): void
    {
        if ($this->tagId) {
            // Update specific tag
            $tag = Tag::find($this->tagId);
            
            if ($tag) {
                $usageCount = DB::table('activity_tag')
                    ->where('tag_id', $tag->id)
                    ->count();
                
                $tag->update(['usage_count' => $usageCount]);
            }
        } else {
            // Update all tags
            $tagService->recalculateUsageCounts();
        }

        // Clear trending cache
        \Illuminate\Support\Facades\Cache::forget('tags:trending');
    }
}
