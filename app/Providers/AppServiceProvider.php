<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Register Post-to-Event Conversion event listeners
        \Illuminate\Support\Facades\Event::listen(
            \App\Events\PostConversionPrompted::class,
            \App\Listeners\SendConversionPromptNotification::class
        );

        // Register listener for PostConversionSuggested (fired by CheckPostConversionEligibility job at soft threshold)
        \Illuminate\Support\Facades\Event::listen(
            \App\Events\PostConversionSuggested::class,
            \App\Listeners\SendConversionPromptNotification::class
        );

        // Register listener for PostAutoConverted (fired by CheckPostConversionEligibility job at strong threshold)
        \Illuminate\Support\Facades\Event::listen(
            \App\Events\PostAutoConverted::class,
            \App\Listeners\SendConversionPromptNotification::class
        );

        \Illuminate\Support\Facades\Event::listen(
            \App\Events\PostConvertedToEvent::class,
            [
                \App\Listeners\NotifyInterestedUsers::class,
                \App\Listeners\MigratePostInvitations::class,
            ]
        );
    }
}
