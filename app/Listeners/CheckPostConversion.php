<?php

namespace App\Listeners;

use App\Events\PostReacted;
use App\Jobs\CheckPostConversionEligibility;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class CheckPostConversion
{
    /**
     * Create the event listener.
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     */
    public function handle(PostReacted $event): void
    {
        // Only check conversion for "im_down" reactions
        if ($event->reaction->reaction_type !== 'im_down') {
            return;
        }

        // Dispatch job to check conversion eligibility
        CheckPostConversionEligibility::dispatch($event->post->id);
    }
}
