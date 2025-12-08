<?php

use App\Jobs\ExpireRefundWindows;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Schedule refund window expiration check every hour
Schedule::job(new ExpireRefundWindows)->hourly();
