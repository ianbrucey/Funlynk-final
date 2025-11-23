<?php

use App\Http\Controllers\Api\UsernameController;
use App\Http\Controllers\Auth\SocialLoginController;
use App\Livewire\Auth\Login as LoginForm;
use App\Livewire\Auth\Register as RegisterForm;
use App\Livewire\Profile\EditProfile;
use App\Livewire\Profile\ShowProfile;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

Route::view('/', 'welcome')->name('home');

// Sample design views
Route::view('/samples/gemini', 'samples.gemini-style')->name('samples.gemini');
Route::view('/samples/claude', 'samples.claude-style')->name('samples.claude');

// API Routes
Route::post('/api/check-username', [UsernameController::class, 'checkAvailability'])
    ->middleware('throttle:60,1')
    ->name('api.check-username');

Route::middleware('guest')->group(function () {
    Route::get('/register', RegisterForm::class)->name('register');
    Route::get('/login', LoginForm::class)->name('login');
});

Route::middleware('auth')->group(function () {
    Route::get('/dashboard', \App\Livewire\Dashboard::class)->name('dashboard');
    Route::get('/profile', ShowProfile::class)->name('profile.show');
    Route::get('/profile/edit', EditProfile::class)->name('profile.edit');
    Route::get('/u/{username}', ShowProfile::class)->name('profile.view');

    // Activity Routes
    Route::get('/activities', function () {
        return 'Activities Index Placeholder'; // Placeholder for now
    })->name('activities.index');
    Route::get('/activities/create', \App\Livewire\Activities\CreateActivity::class)->name('activities.create');
    Route::get('/activities/{activity}', \App\Livewire\Activities\ActivityDetail::class)->name('activities.show');
    Route::get('/activities/{activity}/edit', \App\Livewire\Activities\EditActivity::class)->name('activities.edit');
    Route::get('/activities/{activity}/checkout', \App\Livewire\Payments\CheckoutForm::class)->name('activities.checkout');

    // Stripe Connect Routes
    Route::get('/host/stripe-onboarding', \App\Livewire\Payments\StripeOnboarding::class)->name('stripe.onboarding');
    Route::get('/host/stripe-return', \App\Livewire\Payments\StripeOnboarding::class)->name('stripe.onboarding.return');
    Route::get('/host/stripe-refresh', \App\Livewire\Payments\StripeOnboarding::class)->name('stripe.onboarding.refresh');

    Route::post('/logout', function (Request $request) {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    })->name('logout');
});

Route::controller(SocialLoginController::class)
    ->prefix('auth')
    ->group(function () {
        Route::get('{provider}/redirect', 'redirect')
            ->name('social.redirect')
            ->whereIn('provider', ['google', 'facebook']);

        Route::get('{provider}/callback', 'callback')
            ->name('social.callback')
            ->whereIn('provider', ['google', 'facebook']);
    });
