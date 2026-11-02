<?php

namespace App\Poster;

use App\Poster\Console\Commands\IntakeDriveCommand;
use App\Poster\Contracts\DriveClient;
use App\Poster\Services\Drive\CliDriveClient;
use Illuminate\Support\ServiceProvider;

/**
 * Wiring for the Poster module (app/Poster) — the social-publishing engine
 * ported from the standalone funlink-poster app on 2026-11-02.
 *
 * Everything the module needs from the host app is registered here — the
 * DriveClient binding and the poster:intake console command — so the module
 * stays self-contained and no other Funlynk feature changes.
 */
class PosterServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(DriveClient::class, fn () => CliDriveClient::fromConfig());
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                IntakeDriveCommand::class,
            ]);
        }
    }
}
