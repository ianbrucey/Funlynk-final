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
    // Onboarding route (no middleware - must be accessible to incomplete users)
    Route::get('/onboarding', \App\Livewire\Onboarding\OnboardingWizard::class)->name('onboarding');

    // Routes that require completed onboarding
    Route::middleware('onboarding.complete')->group(function () {
        // Redirect /dashboard to nearby feed for backwards compatibility
        Route::redirect('/dashboard', '/feed/nearby');
        Route::get('/profile', ShowProfile::class)->name('profile.show');
        Route::get('/u/{username}', ShowProfile::class)->name('profile.view');

        // Post Routes
        Route::get('/posts/create', \App\Livewire\Posts\CreatePost::class)->name('posts.create');
        Route::get('/posts/{post}', \App\Livewire\Posts\PostDetail::class)->name('posts.show');

        // Activity Routes
        Route::get('/activities', function () {
            return 'Activities Index Placeholder'; // Placeholder for now
        })->name('activities.index');
        Route::get('/activities/create', \App\Livewire\Activities\CreateActivity::class)->name('activities.create');
        Route::get('/activities/{activity}', \App\Livewire\Activities\ActivityDetail::class)->name('activities.show');
        Route::get('/activities/{activity}/edit', \App\Livewire\Activities\EditActivity::class)->name('activities.edit');
        Route::get('/activities/{activity}/checkout', \App\Livewire\Payments\CheckoutForm::class)->name('activities.checkout');

        // Discovery Routes
        Route::get('/feed/nearby', \App\Livewire\Discovery\NearbyFeed::class)->name('feed.nearby');
        Route::get('/feed/for-you', \App\Livewire\Discovery\ForYouFeed::class)->name('feed.for-you');
        Route::get('/map', \App\Livewire\Discovery\MapView::class)->name('map.view');

        // Search redirects to home feed with query param
        Route::get('/search', function (\Illuminate\Http\Request $request) {
            $query = $request->query('q', '');

            return redirect()->route('feed.nearby', $query ? ['q' => $query] : []);
        })->name('search');
        Route::get('/search/users', \App\Livewire\Search\SearchUsers::class)->name('search.users');

        // Notification Routes
        Route::get('/notifications', function () {
            return 'Notifications Page - Agent A will implement this';
        })->name('notifications.index');

        // Stripe Connect Routes
        Route::get('/host/stripe-onboarding', \App\Livewire\Payments\StripeOnboarding::class)->name('stripe.onboarding');
        Route::get('/host/stripe-return', \App\Livewire\Payments\StripeOnboarding::class)->name('stripe.onboarding.return');
        Route::get('/host/stripe-refresh', \App\Livewire\Payments\StripeOnboarding::class)->name('stripe.onboarding.refresh');
    });

    // Profile edit route (outside onboarding middleware - accessible to incomplete users)
    Route::get('/profile/edit', EditProfile::class)->name('profile.edit');

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
Route::get('/chat-demo', function () { return view('chat-demo'); });
