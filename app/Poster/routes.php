<?php

use App\Poster\Http\Controllers\ConnectController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Poster module routes
|--------------------------------------------------------------------------
| Admin-only OAuth connect flow for the Poster's own platform accounts.
| Guarded by `auth` plus the `poster-admin` gate (email allowlist — the
| same mechanism Funlynk uses for Horizon; see PosterServiceProvider).
| Guests are redirected to login; authenticated non-admins get a 403.
*/

Route::middleware(['web', 'auth', 'can:poster-admin'])
    ->prefix('poster/connect')
    ->name('poster.connect.')
    ->group(function () {
        Route::get('/meta', [ConnectController::class, 'meta'])->name('meta');
        Route::get('/meta/callback', [ConnectController::class, 'metaCallback'])->name('meta.callback');
        Route::get('/tiktok', [ConnectController::class, 'tiktok'])->name('tiktok');
        Route::get('/tiktok/callback', [ConnectController::class, 'tiktokCallback'])->name('tiktok.callback');
    });
