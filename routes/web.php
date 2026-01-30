<?php

use App\Http\Controllers\Api\UsernameController;
use App\Http\Controllers\Auth\SocialLoginController;
use App\Livewire\Auth\ForgotPassword;
use App\Livewire\Auth\Login as LoginForm;
use App\Livewire\Auth\Register as RegisterForm;
use App\Livewire\Auth\ResetPassword;
use App\Livewire\Dashboard\UserDashboard;
use App\Livewire\Groups\CreateGroup;
use App\Livewire\Groups\GroupSettings;
use App\Livewire\Groups\GroupShow;
use App\Livewire\Groups\GroupsIndex;
use App\Livewire\Profile\EditProfile;
use App\Livewire\Profile\ShowProfile;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

Route::view('/', 'welcome')->name('home');

// Legal pages
Route::view('/terms-of-service', 'legal.terms')->name('terms');
Route::view('/privacy', 'legal.privacy')->name('privacy');

// Sample design views
Route::view('/samples/gemini', 'samples.gemini-style')->name('samples.gemini');
Route::view('/samples/claude', 'samples.claude-style')->name('samples.claude');
Route::get('/groups/mockup-landing', \App\Livewire\Groups\PublicGroupLanding::class)->name('groups.mockup-landing');
Route::get('/groups/mockup-landing-jazz', \App\Livewire\Groups\PublicGroupLandingJazz::class)->name('groups.mockup-landing-jazz');

// API Routes
Route::post('/api/check-username', [UsernameController::class, 'checkAvailability'])
    ->middleware('throttle:60,1')
    ->name('api.check-username');

Route::middleware('guest')->group(function () {
    Route::get('/register', RegisterForm::class)->name('register');
    Route::get('/login', LoginForm::class)->name('login');
    Route::get('/forgot-password', ForgotPassword::class)->name('password.request');
    Route::get('/reset-password/{token}', ResetPassword::class)->name('password.reset');
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
        Route::get('/posts/{post}/chat', \App\Livewire\Posts\PostChat::class)->name('posts.chat');

        // Event Dashboard Routes (specific routes BEFORE dynamic {activity})
        Route::get('/events', \App\Livewire\Events\EventDashboard::class)->name('events.dashboard');
        Route::get('/events/create', \App\Livewire\Activities\CreateActivity::class)->name('events.create');
        Route::get('/events/{activity}/edit', \App\Livewire\Activities\EditActivity::class)->name('events.edit');
        Route::get('/events/{activity}/checkout', \App\Livewire\Payments\CheckoutForm::class)->name('events.checkout');

        // Check-In Routes
        Route::get('/tickets', \App\Livewire\Tickets\MyTickets::class)->name('tickets.index');
        Route::get('/events/{activity}/my-ticket', \App\Livewire\CheckIn\MyTicket::class)->name('events.my-ticket');
        Route::get('/events/{activity}/attendees', \App\Livewire\CheckIn\HostAttendeeManager::class)->name('events.attendees');
        Route::get('/events/{activity}/scan', \App\Livewire\CheckIn\QrScanner::class)->name('events.scan');

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
        Route::get('/notifications', \App\Livewire\Notifications\NotificationList::class)->name('notifications.index');

        // Direct Messages Routes
        Route::get('/messages', \App\Livewire\DirectMessages\MessagesPage::class)->name('messages.index');
        Route::get('/messages/requests', \App\Livewire\DirectMessages\MessagesPage::class)->name('messages.requests');
        Route::get('/messages/{conversation}', \App\Livewire\DirectMessages\MessagesPage::class)->name('messages.show');

        // Settings Routes
        Route::get('/settings/notifications', \App\Livewire\Settings\NotificationPreferences::class)->name('settings.notifications');

        // Stripe Connect Routes
        Route::get('/host/stripe-onboarding', \App\Livewire\Payments\StripeOnboarding::class)->name('stripe.onboarding');
        Route::get('/host/stripe-return', \App\Livewire\Payments\StripeOnboarding::class)->name('stripe.onboarding.return');
        Route::get('/host/stripe-refresh', \App\Livewire\Payments\StripeOnboarding::class)->name('stripe.onboarding.refresh');

        // Group Routes (auth required)
        Route::get('/groups', GroupsIndex::class)->name('groups.index');
        Route::get('/groups/create', CreateGroup::class)->name('groups.create');
        Route::get('/groups/{group:slug}/members', \App\Livewire\Groups\GroupMembers::class)->name('groups.members');
        Route::get('/groups/{group:slug}/timeline', \App\Livewire\Groups\GroupTimeline::class)->name('groups.timeline');
        Route::get('/groups/{group:slug}/settings', GroupSettings::class)->name('groups.settings');
        Route::get('/groups/{group:slug}/requests', \App\Livewire\Groups\JoinRequestsList::class)->name('groups.requests');
        Route::redirect('/dashboard', '/feed/nearby')->name('dashboard');
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

        // Event-aware OAuth redirect (captures activity context)
        Route::get('{provider}/redirect/event/{activity}', 'redirectWithEvent')
            ->name('social.redirect.event')
            ->whereIn('provider', ['google']);

        Route::get('{provider}/callback', 'callback')
            ->name('social.callback')
            ->whereIn('provider', ['google', 'facebook']);
    });
Route::get('/chat-demo', function () {
    return view('chat-demo');
});

// Reverb test page
Route::middleware('auth')->get('/test-toast', function () {
    return view('test-toast');
})->name('test.toast');

// Public Group Routes
Route::get('/g/{group:slug}', \App\Livewire\Groups\PublicGroupLanding::class)->name('groups.public');
// Group detail route - handles auth redirect internally
Route::get('/groups/{group:slug}', GroupShow::class)->name('groups.show');

// Public Event Routes (accessible without login) - UUID-based
// MUST be last to avoid catching specific routes like /events/create
Route::get('/events/{activity}', \App\Livewire\Activities\ActivityDetail::class)
    ->name('events.show')
    ->middleware(['web', 'capture.intent', 'throttle:60,1']);

