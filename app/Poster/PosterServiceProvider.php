<?php

namespace App\Poster;

use App\Models\User;
use App\Poster\Console\Commands\IntakeDriveCommand;
use App\Poster\Contracts\DriveClient;
use App\Poster\Services\Drive\CliDriveClient;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

/**
 * Wiring for the Poster module (app/Poster) — the social-publishing engine
 * ported from the standalone funlink-poster app on 2026-11-02.
 *
 * Everything the module needs from the host app is registered here — the
 * DriveClient binding, the poster:intake console command, the admin gate,
 * and the Phase 2 OAuth connect routes/views — so the module stays
 * self-contained and no other Funlynk feature changes.
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

        // Poster admin = the app's existing admin mechanism. Funlynk has no
        // is_admin flag; Horizon access is gated by an email allowlist
        // (HorizonServiceProvider::gate). The Poster connect routes reuse
        // that exact pattern: a Gate backed by an email allowlist.
        Gate::define('poster-admin', function (User $user): bool {
            $allowlist = array_map(
                fn (mixed $email): string => strtolower(trim((string) $email)),
                (array) config('poster.admin_emails', [])
            );

            return in_array(strtolower($user->email), $allowlist, true);
        });

        $this->loadRoutesFrom(__DIR__.'/routes.php');
        $this->loadViewsFrom(__DIR__.'/Views', 'poster');
    }
}
