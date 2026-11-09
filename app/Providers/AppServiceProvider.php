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
        if (! $this->app->environment('local')) {
            \Illuminate\Support\Facades\URL::forceScheme('https');
        }

        // Spec 019: who counts as a listings admin (email allowlist,
        // the same mechanism as the Horizon and Poster gates).
        \Illuminate\Support\Facades\Gate::define('listings-admin', function ($user = null) {
            return app(\App\Policies\ActivityPolicy::class)->isListingsAdmin($user);
        });

        // Note: Event listeners are auto-discovered in Laravel 12
        // No manual registration needed - listeners in app/Listeners/ are automatically wired
    }
}
