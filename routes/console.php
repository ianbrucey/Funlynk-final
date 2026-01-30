<?php

use App\Jobs\ExpireRefundWindows;
use App\Jobs\GenerateRecurringEvents;
use App\Jobs\SendEventReminders;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Schedule refund window expiration check every hour
Schedule::job(new ExpireRefundWindows)->hourly();

// Send event reminders 30 minutes before events (runs every 10 minutes)
Schedule::job(new SendEventReminders(30))->everyTenMinutes();

// Generate recurring events weekly on Sundays at midnight
Schedule::job(new GenerateRecurringEvents)->weekly()->sundays()->at('00:00');
