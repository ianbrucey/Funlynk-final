<?php

namespace App\Jobs;

use App\Services\RefundWindowService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class ExpireRefundWindows implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new job instance.
     */
    public function __construct()
    {
        //
    }

    /**
     * Execute the job.
     * This job should be scheduled to run every hour.
     */
    public function handle(RefundWindowService $refundWindowService): void
    {
        $expiredCount = $refundWindowService->expireWindows();

        if ($expiredCount > 0) {
            Log::info("Expired {$expiredCount} refund windows.");
        }
    }
}
