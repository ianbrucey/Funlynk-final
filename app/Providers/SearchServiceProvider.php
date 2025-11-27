<?php

namespace App\Providers;

use App\Contracts\SearchServiceInterface;
use App\Services\DatabaseSearchService;
use App\Services\MeilisearchSearchService;
use Illuminate\Support\ServiceProvider;

class SearchServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        // Bind SearchServiceInterface to the configured driver
        $this->app->bind(SearchServiceInterface::class, function ($app) {
            $driver = config('search.driver', 'database');

            return match ($driver) {
                'meilisearch' => $app->make(MeilisearchSearchService::class),
                'database' => $app->make(DatabaseSearchService::class),
                default => $app->make(DatabaseSearchService::class),
            };
        });
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        //
    }
}
