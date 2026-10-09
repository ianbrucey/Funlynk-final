<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Activity;
use App\Services\ContextPreservationService;
use App\Services\RsvpService;
use App\Services\SocialAccountService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Laravel\Socialite\Facades\Socialite;
use MatanYadaev\EloquentSpatial\Objects\Point;
use Symfony\Component\HttpFoundation\Response;

class SocialLoginController extends Controller
{
    public function __construct(private readonly SocialAccountService $socialAccountService) {}

    public function redirect(string $provider): RedirectResponse
    {
        $this->ensureSupportedProvider($provider);

        return Socialite::driver($provider)->redirect();
    }

    /**
     * Redirect to OAuth provider with event context
     */
    public function redirectWithEvent(string $provider, Activity $activity): RedirectResponse
    {
        $this->ensureSupportedProvider($provider);

        // Store activity context in session for callback
        session(['oauth_event_context' => [
            'activity_id' => $activity->id,
            'activity_location' => [
                'name' => $activity->location_name,
                'lat' => $activity->location_coordinates?->latitude,
                'lng' => $activity->location_coordinates?->longitude,
            ],
            'is_paid' => $activity->is_paid,
        ]]);

        return Socialite::driver($provider)->redirect();
    }

    public function callback(string $provider, ContextPreservationService $contextService, RsvpService $rsvpService): RedirectResponse
    {
        $this->ensureSupportedProvider($provider);

        try {
            $currentUser = Auth::user();
            $providerUser = Socialite::driver($provider)->user();

            $user = $this->socialAccountService->findOrCreateUser($providerUser, $provider, $currentUser);

            if (! $currentUser) {
                Auth::login($user, true);
                request()->session()->regenerate();
            }

            // Check for event context from OAuth flow
            $eventContext = session('oauth_event_context');
            if ($eventContext) {
                $activity = Activity::find($eventContext['activity_id']);

                if ($activity) {
                    // If user hasn't completed onboarding, copy location from event
                    if (! $user->hasCompletedOnboarding() && $eventContext['activity_location']['lat']) {
                        $user->update([
                            'location_name' => $eventContext['activity_location']['name'],
                            'location_coordinates' => new Point(
                                $eventContext['activity_location']['lat'],
                                $eventContext['activity_location']['lng']
                            ),
                            'onboarding_completed_at' => now(),
                        ]);
                    }

                    // Auto-RSVP for free events or redirect to checkout
                    session()->forget('oauth_event_context');
                    if (! $eventContext['is_paid']) {
                        try {
                            $rsvpService->createRsvp($activity, $user, ['status' => 'attending']);

                            return redirect()->route('events.show', $activity)
                                ->with('success', 'You\'re all set! You\'ve successfully joined this event.');
                        } catch (\Exception $e) {
                            return redirect()->route('events.show', $activity)
                                ->with('error', 'Could not complete RSVP. Please try again.');
                        }
                    } else {
                        // Paid event - full page redirect for Stripe.js
                        return redirect()->route('events.checkout', $activity);
                    }
                }
            }

            // Regular OAuth flow (no event context)
            return redirect()->intended(route('feed.nearby'))
                ->with('status', __('Connected :provider account successfully.', [
                    'provider' => ucfirst($provider),
                ]));
        } catch (\Throwable $exception) {
            Log::error('Social login failed', [
                'provider' => $provider,
                'message' => $exception->getMessage(),
            ]);

            return redirect()->route('login')->withErrors([
                'social' => __('Unable to sign in using :provider. Please try again.', [
                    'provider' => ucfirst($provider),
                ]),
            ]);
        }
    }

    protected function ensureSupportedProvider(string $provider): void
    {
        abort_unless(in_array($provider, ['google', 'facebook'], true), Response::HTTP_NOT_FOUND);
    }
}
