<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Services\SocialAccountService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Laravel\Socialite\Facades\Socialite;
use Symfony\Component\HttpFoundation\Response;

class SocialLoginController extends Controller
{
    public function __construct(private readonly SocialAccountService $socialAccountService) {}

    public function redirect(string $provider): RedirectResponse
    {
        $this->ensureSupportedProvider($provider);

        return Socialite::driver($provider)->redirect();
    }

    public function callback(string $provider): RedirectResponse
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

            return redirect()->intended(route('dashboard'))
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
